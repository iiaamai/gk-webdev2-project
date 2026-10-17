<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\Setting;
use App\Models\Vehicle;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateBooking
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Booking $booking, array $data): Booking
    {
        $before = $booking->only([
            'pricing_id',
            'booking_datetime',
            'pickup_address',
            'pickup_lat',
            'pickup_lng',
            'dropoff_address',
            'dropoff_lat',
            'dropoff_lng',
            'cargo_desc',
            'additional_requirements',
            'driver_id',
            'customer_id',
        ]);

        $fresh = DB::transaction(function () use ($booking, $data): Booking {
            $dropoffChanged = $this->dropoffChanged($booking, $data);
            $pricingId = (int) $data['pricing_id'];

            if ($pricingId !== (int) $booking->pricing_id) {
                $pricing = Pricing::query()->find($pricingId);

                if ($pricing === null) {
                    throw ValidationException::withMessages([
                        'pricing_id' => 'The selected pricing is invalid.',
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
            }

            $booking->update([
                'pricing_id' => $pricingId,
                'booking_datetime' => $data['booking_datetime'],
                'pickup_address' => $data['pickup_address'],
                'pickup_lat' => $data['pickup_lat'],
                'pickup_lng' => $data['pickup_lng'],
                'dropoff_address' => $data['dropoff_address'],
                'dropoff_lat' => $data['dropoff_lat'],
                'dropoff_lng' => $data['dropoff_lng'],
                'cargo_desc' => $data['cargo_desc'] ?? null,
                'additional_requirements' => $data['additional_requirements'] ?? null,
                'driver_id' => array_key_exists('driver_id', $data)
                    ? ($data['driver_id'] ?: null)
                    : $booking->driver_id,
            ]);

            if (isset($data['customer_id'])) {
                $booking->update(['customer_id' => $data['customer_id']]);
            }

            if ($dropoffChanged) {
                $this->applyDropoffChangeFee($booking);
            }

            return $booking->fresh();
        });

        $this->activityLogger->log(
            action: 'booking.updated',
            subject: $fresh,
            description: "Booking {$fresh->booking_number} details updated.",
            properties: [
                'before' => $before,
                'after' => $fresh->only(array_keys($before)),
            ],
        );

        return $fresh;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function dropoffChanged(Booking $booking, array $data): bool
    {
        if (trim((string) $booking->dropoff_address) !== trim((string) ($data['dropoff_address'] ?? ''))) {
            return true;
        }

        return round((float) $booking->dropoff_lat, 6) !== round((float) ($data['dropoff_lat'] ?? 0), 6)
            || round((float) $booking->dropoff_lng, 6) !== round((float) ($data['dropoff_lng'] ?? 0), 6);
    }

    private function applyDropoffChangeFee(Booking $booking): void
    {
        $fee = round((float) Setting::getValue('dropoff_change_fee', '0'), 2);

        if ($fee <= 0) {
            return;
        }

        $invoice = $booking->invoice;

        if ($invoice === null) {
            return;
        }

        $note = 'Drop-off change fee: ₱'.number_format($fee, 2);
        $invoice->amount = round((float) $invoice->amount + $fee, 2);
        $invoice->notes = trim(implode("\n", array_filter([$invoice->notes, $note])));

        if ($invoice->status === InvoiceStatus::Paid) {
            $invoice->status = InvoiceStatus::Unpaid;
            $invoice->paid_at = null;
        }

        $invoice->save();
    }
}
