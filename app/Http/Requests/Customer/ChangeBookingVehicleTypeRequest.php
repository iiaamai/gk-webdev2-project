<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ChangeBookingVehicleTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');

        return $booking !== null && $this->user()?->can('changeVehicleType', $booking);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pricing_id' => ['required', 'integer', 'exists:pricings,id'],
        ];
    }
}
