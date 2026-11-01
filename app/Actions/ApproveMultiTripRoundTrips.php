<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\MultiTripOfficeStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\BookingMultiTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveMultiTripRoundTrips
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(User $officeUser, Booking $booking, int $officeRoundTrips): Booking
    {
        if (! $officeUser->isStaff() && ! $officeUser->isSystemAdmin()) {
            throw ValidationException::withMessages([
                'user' => 'Only office staff can approve multi-trip requests.',
            ]);
        }

        if (! BookingMultiTrip::isMultiTripPendingOffice($booking)) {
            throw ValidationException::withMessages([
                'booking' => 'There is no pending multi-trip request for this booking.',
            ]);
        }

        if ($officeRoundTrips < 1 || $officeRoundTrips > 99) {
            throw ValidationException::withMessages([
                'round_trips' => 'Enter between 1 and 99 round trips.',
            ]);
        }

        $booking = DB::transaction(function () use ($booking, $officeRoundTrips): Booking {
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $booking->loadMissing(['pricing', 'invoice']);

            $matchesCustomer = $officeRoundTrips === (int) $booking->multi_trip_requested_rounds;

            $booking->update([
                'multi_trip_office_rounds' => $officeRoundTrips,
                'multi_trip_office_status' => MultiTripOfficeStatus::Approved,
                'multi_trip_customer_confirmed_at' => $matchesCustomer ? now('Asia/Manila') : null,
                'multi_trip_driver_accepted_at' => null,
            ]);

            $pricing = $booking->pricing;
            if ($pricing !== null && $booking->invoice !== null) {
                $booking->invoice->update([
                    'amount' => BookingMultiTrip::invoiceAmountAfterMultiTripApprove($booking, $pricing, $officeRoundTrips),
                    'status' => InvoiceStatus::Unpaid,
                    'paid_at' => null,
                ]);
            }

            return $booking->fresh(['invoice', 'pricing']);
        });

        $this->activityLogger->log(
            action: 'booking.multi_trip_approved',
            subject: $booking,
            description: "Office approved {$officeRoundTrips} round trip(s) for {$booking->booking_number}.",
            properties: ['round_trips' => $officeRoundTrips],
            user: $officeUser,
        );

        return $booking;
    }
}
