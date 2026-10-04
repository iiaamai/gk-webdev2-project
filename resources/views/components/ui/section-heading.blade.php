@props([
    'icon',
    'title',
    'description' => null,
    'iconClass' => 'text-primary',
])

<div {{ $attributes }}>
    <div class="flex items-start gap-2">
        <x-ui.icon :name="$icon" size="size-4" @class(['mt-0.5 shrink-0', $iconClass]) />
        <div class="min-w-0">
            <h2 class="text-sm font-semibold text-text">{{ $title }}</h2>
            @if ($description)
                <p class="mt-1 text-sm text-text-muted">{{ $description }}</p>
            @endif
        </div>
    </div>
</div>
