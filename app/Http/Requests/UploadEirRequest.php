<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

class UploadEirRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');

        return $booking instanceof Booking
            && $this->user()?->can('uploadEir', $booking);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'eir' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,gif', 'max:20480'],
        ];
    }
}
