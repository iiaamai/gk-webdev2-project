<?php

namespace App\Http\Requests\Concerns;

use App\Models\Booking;
use App\Models\User;
use App\Support\MetricWeight;
use App\Support\ServiceArea;
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
            'pickup_lat' => ['required', 'numeric', 'between:-90,90', $this->serviceAreaRule('pickup_lng')],
            'pickup_lng' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_address' => ['required', 'string', 'max:500'],
            'dropoff_lat' => ['required', 'numeric', 'between:-90,90', $this->serviceAreaRule('dropoff_lng')],
            'dropoff_lng' => ['required', 'numeric', 'between:-180,180'],
            ...$this->cargoFieldRules(),
            'cargo_desc' => ['nullable', 'string', 'max:2000'],
            'additional_requirements' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function serviceAreaRule(string $companionLngField): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($companionLngField): void {
            $lng = $this->input($companionLngField);

            if (! is_numeric($value) || ! is_numeric($lng)) {
                return;
            }

            if (! ServiceArea::contains((float) $value, (float) $lng)) {
                $fail(ServiceArea::outOfAreaMessage());
            }
        };
    }

    /**
     * @return array<string, list<string>>
     */
    protected function cargoFieldRules(): array
    {
        return [
            'cargo_tons' => ['required_without:cargo_kg', 'nullable', 'numeric', 'min:0.001', 'max:999.999'],
            'cargo_kg' => ['required_without:cargo_tons', 'nullable', 'integer', 'min:1', 'max:999999'],
        ];
    }

    protected function mergeCargoTonsToKg(): void
    {
        if ($this->filled('cargo_tons')) {
            $this->merge([
                'cargo_kg' => MetricWeight::tonsToKg((float) $this->input('cargo_tons')),
            ]);
        }
    }
}
