@props([
    'tone' => 'neutral',
])

@php
    $classes = match ($tone) {
        'success' => 'bg-success-subtle text-success',
        'danger' => 'bg-danger-subtle text-danger',
        'warning' => 'bg-warning-subtle text-warning',
        'info' => 'bg-info-subtle text-info',
        'primary' => 'bg-primary-subtle text-primary-emphasis',
        default => 'bg-neutral-100 text-neutral-700',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium', $classes]) }}>
    {{ $slot }}
</span>
