<div class="space-y-3">
    <x-ui.section-heading
        icon="clipboard-list"
        title="Trip snapshot"
        description="Fill trip details below. Summary updates after the booking is created."
    />

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-md border border-border bg-surface-inset p-3">
            <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Customer</p>
            <p class="mt-1 font-medium text-text">—</p>
            <p class="text-xs text-text-muted">Select a customer in trip details</p>
        </div>
        <div class="rounded-md border border-border bg-surface-inset p-3">
            <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Status</p>
            <p class="mt-1"><x-ui.badge tone="neutral">pending</x-ui.badge></p>
        </div>
        <div class="rounded-md border border-border bg-surface-inset p-3">
            <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Payout (read-only)</p>
            <p class="mt-1 text-lg font-semibold text-text">—</p>
        </div>
        <div class="rounded-md border border-border bg-surface-inset p-3">
            <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Gatepass</p>
            <p class="mt-1 text-text-muted">Not uploaded</p>
        </div>
        <div class="rounded-md border border-border bg-surface-inset p-3 sm:col-span-2">
            <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Preferred pickup</p>
            <p class="mt-1 font-medium text-text">—</p>
            <p class="mt-1 text-xs text-text-muted">Vehicle type: —</p>
        </div>
    </div>
</div>
