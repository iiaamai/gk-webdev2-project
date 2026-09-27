<?php

namespace App\Http\Requests\Concerns;

use App\Models\Booking;
use App\Models\User;
use Closure;
use Illuminate\Validation\Rule;

trait ValidatesBookingFields
{
    protected function validateDriverMatchesPricing(?Booking $legacyBooking = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($legacyBooking): void {
            if ($value === null || $value === '') {
                return;
            }

            $driver = User::query()
                ->where('role', 'driver')
                ->with('assignedVehicle')
                ->find($value);

            if ($driver === null) {
                return;
            }

            $pricingId = $this->input('pricing_id');

            if ($driver->assignedVehicle === null || $driver->assignedVehicle->pricing_id === null) {
                $fail('The selected driver has no assigned fleet vehicle.');

                return;
            }

            if ((int) $driver->assignedVehicle->pricing_id === (int) $pricingId) {
                return;
            }

            if ($legacyBooking instanceof Booking && (int) $value === (int) $legacyBooking->driver_id) {
                return;
            }

            $fail('The selected driver does not match the booking vehicle type.');
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function bookingFieldRules(bool $requireFutureDatetime = false): array
    {
        $datetimeRules = ['required', 'date'];
        if ($requireFutureDatetime) {
            $datetimeRules[] = 'after:now';
        }

        return [
            'pricing_id' => ['required', 'integer', Rule::exists('pricings', 'id')],
            'booking_datetime' => $datetimeRules,
            'pickup_address' => ['required', 'string', 'max:500'],
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_address' => ['required', 'string', 'max:500'],
            'dropoff_lat' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_lng' => ['required', 'numeric', 'between:-180,180'],
            'cargo_desc' => ['nullable', 'string', 'max:2000'],
            'additional_requirements' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
