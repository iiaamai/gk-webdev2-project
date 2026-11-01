<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

class ApproveMultiTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');

        return $booking !== null && $this->user()?->can('approveMultiTrip', $booking);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'round_trips' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
