@php
    $invoice = $booking->invoice;
    $canView = $invoice && (auth()->user()?->can('view', $invoice) ?? false);
    $canMarkPaid = $invoice && ! empty($markPaidAction ?? null) && (auth()->user()?->can('markAsPaid', $invoice) ?? false);
    $canMarkUnpaid = $invoice && ! empty($markUnpaidAction ?? null) && (auth()->user()?->can('markAsUnpaid', $invoice) ?? false);
    $invoiceTone = $invoice?->status->badgeTone() ?? 'neutral';
    $redirectTo = $redirectTo ?? null;
@endphp

@if ($invoice && $canView)
    <div class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <x-ui.section-heading
                icon="receipt"
                title="Invoice"
                description="Payment status for this booking."
            />
            <x-ui.badge :tone="$invoiceTone">{{ $invoice->status->label() }}</x-ui.badge>
        </div>

        <dl class="mt-1 grid gap-3 text-sm sm:grid-cols-2">
            <div class="rounded-md border border-border bg-surface-inset px-4 py-3 sm:col-span-2">
                <dt class="text-xs font-medium uppercase tracking-wide text-text-subtle">Amount</dt>
                <dd class="mt-1 text-2xl font-semibold text-text">₱{{ number_format((float) $invoice->amount, 2) }}</dd>
            </div>
            @if ($invoice->issued_at)
                <div>
                    <dt class="text-text-muted">Issued</dt>
                    <dd class="mt-1 text-text">{{ $invoice->issued_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</dd>
                </div>
            @endif
            @if ($invoice->paid_at)
                <div>
                    <dt class="text-text-muted">Paid</dt>
                    <dd class="mt-1 text-text">{{ $invoice->paid_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</dd>
                </div>
            @elseif ($invoice->status === \App\Enums\InvoiceStatus::Unpaid)
                <div>
                    <dt class="text-text-muted">Paid</dt>
                    <dd class="mt-1 text-text-muted">Not paid yet</dd>
                </div>
            @endif
            @if ($invoice->notes)
                <div class="sm:col-span-2">
                    <dt class="text-text-muted">Notes</dt>
                    <dd class="mt-1 text-text">{{ $invoice->notes }}</dd>
                </div>
            @endif
        </dl>

        @if ($canMarkPaid)
            <div
                x-data="{ open: false }"
                class="space-y-4 border-t border-border pt-4"
            >
                <x-ui.section-heading
                    icon="check"
                    title="Mark as paid"
                    description="Record that payment was received for this invoice."
                />
                <form
                    x-ref="markPaidForm"
                    method="post"
                    action="{{ $markPaidAction }}"
                    class="space-y-4"
                >
                    @csrf
                    @if ($redirectTo)
                        <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                    @endif
                    <div>
                        <x-ui.label for="invoice_notes">Notes (optional)</x-ui.label>
                        <x-ui.textarea id="invoice_notes" name="notes" rows="2">{{ old('notes') }}</x-ui.textarea>
                        <x-ui.field-error name="notes" />
                    </div>
                    <x-ui.button
                        type="button"
                        class="flex w-full justify-center sm:w-auto"
                        @click="open = true"
                    >
                        <x-ui.icon name="check" size="size-4" />
                        Mark as paid
                    </x-ui.button>
                </form>

                <x-ui.confirm-dialog
                    title="Mark this invoice as paid?"
                    description="The invoice status will change from unpaid to paid."
                    confirm-label="Mark as paid"
                    cancel-label="Cancel"
                    form-ref="markPaidForm"
                />
            </div>
        @endif

        @if ($canMarkUnpaid)
            <div
                x-data="{ open: false }"
                class="space-y-4 border-t border-border pt-4"
            >
                <x-ui.section-heading
                    icon="rotate-ccw"
                    title="Revert to unpaid"
                    description="Undo a mistaken paid status (admin only)."
                />
                <form
                    x-ref="markUnpaidForm"
                    method="post"
                    action="{{ $markUnpaidAction }}"
                    class="space-y-4"
                >
                    @csrf
                    <div>
                        <x-ui.label for="invoice_unpaid_notes">Notes (optional)</x-ui.label>
                        <x-ui.textarea id="invoice_unpaid_notes" name="notes" rows="2">{{ old('notes', $invoice->notes) }}</x-ui.textarea>
                        <x-ui.field-error name="notes" />
                    </div>
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        class="flex w-full justify-center sm:w-auto"
                        @click="open = true"
                    >
                        Revert to unpaid
                    </x-ui.button>
                </form>

                <x-ui.confirm-dialog
                    title="Revert this invoice to unpaid?"
                    description="The invoice will show as unpaid again and the paid date will be cleared."
                    confirm-label="Revert to unpaid"
                    cancel-label="Cancel"
                    confirm-variant="danger"
                    form-ref="markUnpaidForm"
                />
            </div>
        @endif
    </div>
@else
    <div class="space-y-3">
        <x-ui.section-heading
            icon="receipt"
            title="Invoice"
            description="Payment status for this booking."
        />
        <p class="text-sm text-text-muted">No invoice on file for this booking.</p>
    </div>
@endif
