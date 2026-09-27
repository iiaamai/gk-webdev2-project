<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
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
}
