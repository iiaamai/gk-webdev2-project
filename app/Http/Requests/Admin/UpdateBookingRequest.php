<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ValidatesBookingFields;
use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingRequest extends FormRequest
{
    use ValidatesBookingFields;

    public function authorize(): bool
    {
        $booking = $this->route('booking');

        return $booking instanceof Booking
            && $this->user()?->can('update', $booking);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->bookingFieldRules(requireFutureDatetime: false), [
            'customer_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'customer')],
            'driver_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'driver'),
                $this->validateDriverMatchesPricing($this->route('booking')),
            ],
        ]);
    }
}
