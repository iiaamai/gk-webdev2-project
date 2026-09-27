@props([
    'href',
    'icon' => null,
    'active' => false,
    'variant' => 'sidebar',
])

@php
    $classes = $variant === 'portal'
        ? ($active
            ? 'bg-primary-subtle text-primary-emphasis'
            : 'text-text-muted hover:bg-surface-inset hover:text-text')
        : ($active
            ? 'bg-neutral-800 text-sidebar-text-active'
            : 'text-sidebar-text hover:bg-neutral-800/80 hover:text-sidebar-text-active');
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
        $classes,
    ]) }}
    @if ($active) aria-current="page" @endif
>
    @if ($icon)
        <x-ui.icon :name="$icon" size="size-4" />
    @endif
    <span>{{ $slot }}</span>
</a>
