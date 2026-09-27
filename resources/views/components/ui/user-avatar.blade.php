@props([
    'name' => '',
    'role' => null,
    'size' => 'lg',
])

@php
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    if ($initials === '') {
        $initials = '?';
    }

    $roleValue = $role instanceof \App\Enums\UserRole ? $role->value : (string) ($role ?? 'customer');

    $bgClass = match ($roleValue) {
        'system_admin' => 'bg-primary text-text-on-primary',
        'staff' => 'bg-info/20 text-info',
        'driver' => 'bg-warning/25 text-warning',
        default => 'bg-primary-tone-2 text-primary-shade-1',
    };

    $sizeClass = match ($size) {
        'sm' => 'size-10 text-xs',
        'md' => 'size-12 text-sm',
        default => 'size-16 text-lg',
    };
@endphp

<div
    {{ $attributes->class([
        'inline-flex shrink-0 items-center justify-center rounded-full font-semibold',
        $sizeClass,
        $bgClass,
    ]) }}
    aria-hidden="true"
>
    {{ $initials }}
</div>
