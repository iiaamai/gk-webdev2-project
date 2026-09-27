<?php

namespace App\Http\Requests\Customer;

use App\Models\Booking;
use App\Models\Rating;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');

        return $booking instanceof Booking
            && $this->user()?->can('create', [Rating::class, $booking]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
