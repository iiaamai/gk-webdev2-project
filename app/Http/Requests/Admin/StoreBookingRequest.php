<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookingStatus;
use App\Http\Requests\Concerns\ValidatesBookingFields;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    use ValidatesBookingFields;

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
        return array_merge($this->bookingFieldRules(requireFutureDatetime: true), [
            'customer_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'customer')],
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'driver_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'driver'),
                $this->validateDriverMatchesPricing(),
            ],
            'gatepass' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,gif', 'max:20480'],
        ]);
    }
}
