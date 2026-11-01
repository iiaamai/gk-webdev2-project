<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ActivityLogger;
use App\Support\BookingMultiTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeCustomerBookingVehicleType
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(User $customer, Booking $booking, int $pricingId): Booking
    {
        if ($booking->customer_id !== $customer->id) {
            throw ValidationException::withMessages([
                'booking' => 'This booking does not belong to you.',
            ]);
        }

        if (! BookingMultiTrip::canChangeVehicleType($booking)) {
            throw ValidationException::withMessages([
                'pricing_id' => 'You can change the vehicle type once after the driver arrives at pickup.',
            ]);
        }

        if ($pricingId === (int) $booking->pricing_id) {
            throw ValidationException::withMessages([
                'pricing_id' => 'Choose a different vehicle type.',
            ]);
        }

        $pricing = Pricing::query()->find($pricingId);

        if ($pricing === null) {
            throw ValidationException::withMessages([
                'pricing_id' => 'The selected vehicle type is invalid.',
            ]);
        }

        $hasAvailableVehicle = Vehicle::query()
            ->where('pricing_id', $pricing->id)
            ->where('status', VehicleStatus::Available)
            ->exists();

        if (! $hasAvailableVehicle) {
            throw ValidationException::withMessages([
                'pricing_id' => 'No available fleet unit for this vehicle type right now.',
            ]);
        }

        $booking = DB::transaction(function () use ($booking, $pricing, $pricingId): Booking {
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $booking->loadMissing(['invoice', 'pricing']);

            $oldPricing = $booking->pricing;
            $fee = $oldPricing !== null
                ? round(BookingMultiTrip::baseInvoiceAmount($oldPricing) * ((float) Setting::getValue('wrong_truck_fee_percent', '10') / 100), 2)
                : 0.0;
            $roundTrips = BookingMultiTrip::effectiveRoundTrips($booking);
            $baseAmount = BookingMultiTrip::invoiceAmountForRoundTrips($pricing, $roundTrips);
            $newAmount = round($baseAmount + $fee, 2);

            $booking->update([
                'pricing_id' => $pricingId,
                'vehicle_type_changed_at' => now('Asia/Manila'),
            ]);

            if ($booking->invoice !== null) {
                $notes = $booking->invoice->notes;
                if ($fee > 0) {
                    $feeNote = 'Wrong truck type change fee: ₱'.number_format($fee, 2);
                    $notes = trim(implode("\n", array_filter([$notes, $feeNote])));
                }

                $booking->invoice->update([
                    'amount' => $newAmount,
                    'status' => InvoiceStatus::Unpaid,
                    'paid_at' => null,
                    'notes' => $notes !== '' ? $notes : null,
                ]);
            }

            return $booking->fresh(['invoice', 'pricing']);
        });

        $this->activityLogger->log(
            action: 'booking.vehicle_type_changed',
            subject: $booking,
            description: "Customer changed vehicle type on {$booking->booking_number}.",
            properties: ['pricing_id' => $pricingId],
            user: $customer,
        );

        return $booking;
    }
}
