<?php

namespace App\Http\Requests\Customer;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User
            && $this->user()->can('create', Booking::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pricing_id' => ['required', 'integer', Rule::exists('pricings', 'id')],
            'booking_datetime' => ['required', 'date', 'after:now'],
            'pickup_address' => ['required', 'string', 'max:500'],
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['required', 'numeric', 'between:-180,180'],
            'pickup_port_number' => ['required', 'string', 'max:100'],
            'pickup_container_number' => ['required', 'string', 'max:100'],
            'dropoff_address' => ['required', 'string', 'max:500'],
            'dropoff_lat' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_lng' => ['required', 'numeric', 'between:-180,180'],
            'cargo_desc' => ['nullable', 'string', 'max:2000'],
            'additional_requirements' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
