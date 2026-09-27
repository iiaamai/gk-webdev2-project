<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

class MarkInvoiceUnpaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');

        if (! $booking instanceof Booking) {
            return false;
        }

        $invoice = $booking->invoice;

        return $invoice !== null
            && $this->user()?->can('markAsUnpaid', $invoice);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
