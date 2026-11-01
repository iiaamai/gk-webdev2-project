<?php

namespace App\Http\Requests\Driver;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');

        return $booking instanceof Booking
            && $this->user()?->can('updateDeliveryStatus', $booking);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(BookingStatus::class),
                Rule::in([
                    BookingStatus::TransitToPickup->value,
                    BookingStatus::InTransit->value,
                    BookingStatus::Completed->value,
                ]),
            ],
        ];
    }
}
