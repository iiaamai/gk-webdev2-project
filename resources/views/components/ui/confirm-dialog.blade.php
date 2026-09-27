@props([
    'title',
    'description' => '',
    'confirmLabel' => 'Confirm',
    'cancelLabel' => 'Cancel',
    'confirmVariant' => 'primary',
    'formRef',
])

{{-- Parent must define Alpine `open` (boolean) and form x-ref matching `formRef`. --}}

<div
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-neutral-950/50 p-4"
    @keydown.escape.window="open = false"
>
    <div class="absolute inset-0" @click="open = false"></div>

    <div
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="confirm-dialog-title"
        class="relative z-10 w-full max-w-md rounded-lg border border-border bg-surface-elevated p-6 shadow-lg"
        @click.stop
    >
        <h2 id="confirm-dialog-title" class="text-base font-semibold text-text">{{ $title }}</h2>
        @if ($description !== '')
            <p class="mt-2 text-sm text-text-muted">{{ $description }}</p>
        @endif

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-ui.button type="button" variant="secondary" class="w-full justify-center sm:w-auto" @click="open = false">
                {{ $cancelLabel }}
            </x-ui.button>
            <x-ui.button
                type="button"
                :variant="$confirmVariant"
                class="w-full justify-center sm:w-auto"
                @click="$refs.{{ $formRef }}.requestSubmit(); open = false"
            >
                {{ $confirmLabel }}
            </x-ui.button>
        </div>
    </div>
</div>
