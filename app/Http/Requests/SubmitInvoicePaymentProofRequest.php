<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

class SubmitInvoicePaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');

        if (! $booking instanceof Booking) {
            return false;
        }

        $invoice = $booking->invoice;

        return $invoice !== null
            && $this->user()?->can('submitPaymentProof', $invoice);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'proof' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,gif', 'max:20480'],
        ];
    }
}
