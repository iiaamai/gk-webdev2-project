<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentProofStatus;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->isSystemAdmin();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isSystemAdmin() || $user->isStaff()) {
            return true;
        }

        return $user->isCustomer()
            && $invoice->booking->customer_id === $user->id;
    }

    public function markAsPaid(User $user, Invoice $invoice): bool
    {
        if (! $user->isStaff() && ! $user->isSystemAdmin()) {
            return false;
        }

        return $invoice->status === InvoiceStatus::Unpaid;
    }

    public function markAsUnpaid(User $user, Invoice $invoice): bool
    {
        if (! $user->isSystemAdmin()) {
            return false;
        }

        return $invoice->status === InvoiceStatus::Paid;
    }

    public function viewPaymentProof(User $user, Invoice $invoice): bool
    {
        if (! $invoice->hasPaymentProof()) {
            return false;
        }

        if ($user->isSystemAdmin() || $user->isStaff()) {
            return true;
        }

        return $user->isCustomer()
            && $invoice->booking->customer_id === $user->id;
    }

    public function submitPaymentProof(User $user, Invoice $invoice): bool
    {
        if (! $user->isCustomer() || $invoice->booking->customer_id !== $user->id) {
            return false;
        }

        if ($invoice->status !== InvoiceStatus::Unpaid) {
            return false;
        }

        return $invoice->payment_proof_status !== PaymentProofStatus::Pending;
    }

    public function rejectPaymentProof(User $user, Invoice $invoice): bool
    {
        if (! $user->isStaff() && ! $user->isSystemAdmin()) {
            return false;
        }

        return $invoice->status === InvoiceStatus::Unpaid
            && $invoice->hasPendingPaymentProof();
    }
}
