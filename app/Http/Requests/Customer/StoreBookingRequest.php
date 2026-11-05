<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\Concerns\ValidatesBookingFields;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

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
            'pickup_port_number' => ['required', 'string', 'max:100'],
            'pickup_container_number' => ['required', 'string', 'max:100'],
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->mergeCargoTonsToKg();
    }
}
