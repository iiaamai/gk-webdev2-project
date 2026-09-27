@props([
    'title' => 'Nothing here yet',
    'icon' => 'inbox',
])

<div {{ $attributes->class(['flex flex-col items-center justify-center rounded-lg border border-dashed border-border bg-surface-elevated px-6 py-12 text-center']) }}>
    <x-ui.icon :name="$icon" size="size-10" class="text-text-subtle" />
    <h2 class="mt-4 text-base font-medium text-text">{{ $title }}</h2>
    @isset($description)
        <p class="mt-1 max-w-sm text-sm text-text-muted">{{ $description }}</p>
    @endisset
    @isset($actions)
        <div class="mt-4">{{ $actions }}</div>
    @endisset
</div>
