@if ($booking->invoice)
    @can('view', $booking->invoice)
        <div class="space-y-3 text-sm">
            <p class="font-semibold text-text">Invoice (Pay Later)</p>
            <dl class="grid grid-cols-[6.5rem_1fr] gap-x-3 gap-y-2">
                <dt class="text-text-muted">Amount</dt>
                <dd class="font-medium text-text">₱{{ number_format((float) $booking->invoice->amount, 2) }}</dd>
                <dt class="text-text-muted">Status</dt>
                <dd>
                    @php
                        $invoiceTone = $booking->invoice->status->value === 'paid' ? 'success' : 'warning';
                    @endphp
                    <x-ui.badge :tone="$invoiceTone">{{ $booking->invoice->status->value }}</x-ui.badge>
                </dd>
                @if ($booking->invoice->issued_at)
                    <dt class="text-text-muted">Issued</dt>
                    <dd class="text-text">{{ $booking->invoice->issued_at->timezone('Asia/Manila')->format('Y-m-d H:i') }}</dd>
                @endif
                @if ($booking->invoice->paid_at)
                    <dt class="text-text-muted">Paid</dt>
                    <dd class="text-text">{{ $booking->invoice->paid_at->timezone('Asia/Manila')->format('Y-m-d H:i') }}</dd>
                @endif
                @if ($booking->invoice->notes)
                    <dt class="text-text-muted">Notes</dt>
                    <dd class="text-text">{{ $booking->invoice->notes }}</dd>
                @endif
            </dl>
        </div>
    @endcan

    @can('markAsPaid', $booking->invoice)
        @if (! empty($markPaidAction ?? null))
            <div
                x-data="{ open: false }"
                class="mt-6 space-y-4 border-t border-border pt-4"
            >
                <x-ui.section-heading
                    icon="file-up"
                    title="Mark invoice paid"
                    description="Record that payment was received (Pay Later)."
                />
                <form
                    x-ref="markPaidForm"
                    method="post"
                    action="{{ $markPaidAction }}"
                    class="space-y-4"
                >
                    @csrf
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
                    description="This records that payment was received (Pay Later)."
                    confirm-label="Mark as paid"
                    cancel-label="Cancel"
                    form-ref="markPaidForm"
                />
            </div>
        @endif
    @endcan

    @can('markAsUnpaid', $booking->invoice)
        @if (! empty($markUnpaidAction ?? null))
            <div
                x-data="{ open: false }"
                class="mt-6 space-y-4 border-t border-border pt-4"
            >
                <x-ui.section-heading
                    icon="file-up"
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
                        <x-ui.textarea id="invoice_unpaid_notes" name="notes" rows="2">{{ old('notes', $booking->invoice->notes) }}</x-ui.textarea>
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
    @endcan
@else
    <p class="text-sm italic text-text-muted">No invoice on file for this booking.</p>
@endif
