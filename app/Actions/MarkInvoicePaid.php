<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkInvoicePaid
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Invoice $invoice, array $data = []): Invoice
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            throw ValidationException::withMessages([
                'invoice' => 'This invoice is already marked as paid.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $data): Invoice {
            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'paid_at' => now('Asia/Manila'),
                'notes' => $data['notes'] ?? $invoice->notes,
            ]);

            return $invoice->fresh();
        });
    }
}
