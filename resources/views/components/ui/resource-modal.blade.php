@props([
    'createUrl' => null,
    'createTitle' => 'Create',
    'editUrl' => null,
    'editTitle' => 'Edit',
])

<div
    x-data="resourceModal()"
    x-init="initFromDataset()"
    data-open-create-url="{{ $createUrl }}"
    data-open-create-title="{{ $createTitle }}"
    data-open-edit-url="{{ $editUrl }}"
    data-open-edit-title="{{ $editTitle }}"
    class="contents"
>
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto bg-neutral-950/50 p-4 sm:p-6"
        @keydown.escape.window="close()"
    >
        <div
            class="absolute inset-0"
            @click="close()"
        ></div>

        <div
            role="dialog"
            aria-modal="true"
            class="relative z-10 mt-8 w-full max-w-2xl rounded-lg border border-border bg-surface-elevated shadow-lg sm:mt-16"
            @click.stop
        >
            <div class="flex items-center justify-between border-b border-border px-4 py-3">
                <h2 class="text-base font-semibold text-text" x-text="title"></h2>
                <button
                    type="button"
                    class="rounded-md p-1.5 text-text-muted hover:bg-surface-inset"
                    @click="close()"
                    aria-label="Close"
                >
                    <x-ui.icon name="x" size="size-4" />
                </button>
            </div>

            <div class="max-h-[min(70vh,36rem)] overflow-y-auto px-4 py-4">
                <template x-if="loading">
                    <p class="text-sm text-text-muted">Loading…</p>
                </template>
                <div x-show="!loading" x-html="html"></div>
            </div>
        </div>
    </div>
</div>
