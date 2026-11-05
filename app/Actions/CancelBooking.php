<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\BookingEmailNotifier;
use App\Services\BookingVehicleRelease;
use App\Support\CancellationRules;
use Illuminate\Support\Facades\DB;

class CancelBooking
{
    public function __construct(
        private readonly BookingVehicleRelease $bookingVehicleRelease,
        private readonly BookingEmailNotifier $bookingEmailNotifier,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(Booking $booking, ?User $cancelledBy = null, ?bool $applyLateCancelFee = null): Booking
    {
        $booking->loadMissing(['driver', 'pricing', 'invoice']);
        $assignedDriver = $booking->driver;
        $previousStatus = $booking->status;
        $shouldApplyLateFee = $applyLateCancelFee ?? CancellationRules::shouldApplyCancellationFee($booking, $cancelledBy);

        $booking = DB::transaction(function () use ($booking, $shouldApplyLateFee): Booking {
            $this->bookingVehicleRelease->releaseForBooking($booking);

            $booking->update(['status' => BookingStatus::Cancelled]);

            if ($shouldApplyLateFee) {
                $this->applyCancellationFee($booking);
            }

            return $booking->fresh(['invoice', 'pricing']);
        });

        $this->bookingEmailNotifier->statusChanged($booking, BookingStatus::Cancelled, $assignedDriver);

        $this->activityLogger->log(
            action: 'booking.status_changed',
            subject: $booking,
            description: "Booking {$booking->booking_number} cancelled.",
            properties: [
                'status' => BookingStatus::Cancelled->value,
                'previous_status' => $previousStatus->value,
                'late_cancel_fee' => $shouldApplyLateFee,
            ],
        );

        return $booking;
    }

    private function applyCancellationFee(Booking $booking): void
    {
        $fee = CancellationRules::feeAmount($booking);

        Invoice::query()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'amount' => $fee,
                'status' => InvoiceStatus::Unpaid,
                'issued_at' => now('Asia/Manila'),
                'paid_at' => null,
                'notes' => CancellationRules::feeNote(),
                'payment_proof_path' => null,
                'payment_proof_status' => null,
                'payment_proof_submitted_at' => null,
            ],
        );
    }
}
