{{-- Alpine parent should expose previewUrl / previewName and bind file input @change. --}}
<template x-if="previewUrl">
    <div class="mt-2 flex items-center gap-3 rounded-md border border-border bg-surface-inset p-2">
        <img :src="previewUrl" alt="" class="h-14 w-14 shrink-0 rounded border border-border object-cover">
        <p class="min-w-0 truncate text-sm text-text" x-text="previewName"></p>
    </div>
</template>
