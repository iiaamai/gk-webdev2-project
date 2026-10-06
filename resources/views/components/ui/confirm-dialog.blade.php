@props([
    'title',
    'description' => '',
    'confirmLabel' => 'Confirm',
    'cancelLabel' => 'Cancel',
    'confirmVariant' => 'primary',
    'formRef',
])

@php
    $dialogId = 'confirm-dialog-'.str_replace('.', '', uniqid('', true));
    $titleId = $dialogId.'-title';
    $descId = $dialogId.'-desc';
    $refSuffix = preg_replace('/[^a-zA-Z0-9_]/', '_', $dialogId);
@endphp

{{-- Parent must define Alpine `open` (boolean) and form x-ref matching `formRef`. --}}

<div
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-neutral-950/50 p-4"
    @keydown.escape.window="if (open) { open = false }"
    x-effect="
        if (open) {
            $el._confirmPrevFocus = document.activeElement;
            $nextTick(() => $refs.cancel_{{ $refSuffix }}?.focus());
        } else if ($el._confirmPrevFocus instanceof HTMLElement) {
            $el._confirmPrevFocus.focus();
            $el._confirmPrevFocus = null;
        }
    "
>
    <div class="absolute inset-0" @click="open = false"></div>

    <div
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="{{ $titleId }}"
        @if ($description !== '')
            aria-describedby="{{ $descId }}"
        @endif
        class="relative z-10 w-full max-w-md rounded-lg border border-border bg-surface-elevated p-6 shadow-lg"
        @click.stop
        @keydown.tab="
            const focusable = [...$el.querySelectorAll('button')];
            if (focusable.length < 2) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if ($event.shiftKey && document.activeElement === first) {
                $event.preventDefault();
                last.focus();
            } else if (! $event.shiftKey && document.activeElement === last) {
                $event.preventDefault();
                first.focus();
            }
        "
    >
        <h2 id="{{ $titleId }}" class="text-base font-semibold text-text">{{ $title }}</h2>
        @if ($description !== '')
            <p id="{{ $descId }}" class="mt-2 text-sm text-text-muted">{{ $description }}</p>
        @endif

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-ui.button
                type="button"
                variant="secondary"
                class="w-full justify-center sm:w-auto"
                x-ref="cancel_{{ $refSuffix }}"
                @click="open = false"
            >
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
