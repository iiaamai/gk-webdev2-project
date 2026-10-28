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
use Illuminate\Support\Facades\DB;

class CancelBooking
{
    private const LATE_CANCEL_FEE_RATE = 0.25;

    public function __construct(
        private readonly BookingVehicleRelease $bookingVehicleRelease,
        private readonly BookingEmailNotifier $bookingEmailNotifier,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(Booking $booking, ?User $cancelledBy = null): Booking
    {
        $booking->loadMissing(['driver', 'pricing', 'invoice']);
        $assignedDriver = $booking->driver;
        $previousStatus = $booking->status;
        $applyLateCancelFee = $this->shouldApplyLateCancelFee($booking, $cancelledBy);

        $booking = DB::transaction(function () use ($booking, $applyLateCancelFee): Booking {
            $this->bookingVehicleRelease->releaseForBooking($booking);

            $booking->update(['status' => BookingStatus::Cancelled]);

            if ($applyLateCancelFee) {
                $this->applyLateCancelFee($booking);
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
                'late_cancel_fee' => $applyLateCancelFee,
            ],
        );

        return $booking;
    }

    private function shouldApplyLateCancelFee(Booking $booking, ?User $cancelledBy): bool
    {
        if ($cancelledBy === null || ! $cancelledBy->isSystemAdmin()) {
            return false;
        }

        return $booking->accepted_at !== null;
    }

    private function applyLateCancelFee(Booking $booking): void
    {
        $fare = (float) ($booking->pricing?->amount ?? 0);
        $fee = round($fare * self::LATE_CANCEL_FEE_RATE, 2);

        Invoice::query()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'amount' => $fee,
                'status' => InvoiceStatus::Unpaid,
                'issued_at' => now('Asia/Manila'),
                'paid_at' => null,
                'notes' => 'Cancellation fee (25%).',
                'payment_proof_path' => null,
                'payment_proof_status' => null,
                'payment_proof_submitted_at' => null,
            ],
        );
    }
}
