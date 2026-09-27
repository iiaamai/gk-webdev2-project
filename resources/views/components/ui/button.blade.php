@props([
    'variant' => 'primary',
    'type' => 'submit',
    'href' => null,
])

@php
    $classes = match ($variant) {
        'secondary' => 'bg-surface-elevated text-text border border-border hover:bg-surface-inset',
        'danger' => 'bg-danger text-white hover:bg-danger/90 border border-transparent',
        'ghost' => 'bg-transparent text-text-muted hover:bg-surface-inset border border-transparent',
        'sidebar' => 'bg-transparent text-sidebar-text hover:bg-neutral-800 hover:text-sidebar-text-active border border-transparent',
        default => 'bg-primary text-text-on-primary hover:bg-primary-shade-1 border border-transparent',
    };
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->class([
            'inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
            $classes,
        ]) }}
    >{{ $slot }}</a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->class([
            'inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:opacity-50',
            $classes,
        ]) }}
    >{{ $slot }}</button>
@endif
