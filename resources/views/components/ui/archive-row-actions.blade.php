@props([
    'restoreUrl',
    'destroyUrl',
    'restoreTitle',
    'restoreDescription' => 'The record will return to the active list.',
    'deleteTitle',
    'deleteDescription' => 'This cannot be undone. Related records must already be gone.',
])

<div class="flex flex-wrap items-center justify-end gap-2">
    <div x-data="{ open: false }">
        <form x-ref="restoreForm" method="post" action="{{ $restoreUrl }}">
            @csrf
            <x-ui.button type="button" variant="secondary" class="!py-1.5 !text-xs" @click="open = true">
                Restore
            </x-ui.button>
        </form>
        <x-ui.confirm-dialog
            :title="$restoreTitle"
            :description="$restoreDescription"
            confirm-label="Restore"
            cancel-label="Cancel"
            form-ref="restoreForm"
        />
    </div>
    <div x-data="{ open: false }">
        <form x-ref="deleteForm" method="post" action="{{ $destroyUrl }}">
            @csrf
            @method('DELETE')
            <x-ui.button type="button" variant="ghost" class="!py-1.5 !text-xs !text-danger" @click="open = true">
                Delete
            </x-ui.button>
        </form>
        <x-ui.confirm-dialog
            :title="$deleteTitle"
            :description="$deleteDescription"
            confirm-label="Delete permanently"
            cancel-label="Cancel"
            confirm-variant="danger"
            form-ref="deleteForm"
        />
    </div>
</div>
