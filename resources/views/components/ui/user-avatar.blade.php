@props([
    'user' => null,
    'name' => '',
    'role' => null,
    'size' => 'lg',
])

@php
    $displayName = $user?->name ?? $name;
    $displayRole = $user?->role ?? $role;
    $parts = preg_split('/\s+/', trim($displayName)) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    if ($initials === '') {
        $initials = '?';
    }

    $roleValue = $displayRole instanceof \App\Enums\UserRole
        ? $displayRole->value
        : (string) ($displayRole ?? 'customer');

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

    $showPhoto = $user !== null
        && $user->hasAvatar()
        && auth()->user()?->can('viewAvatar', $user);
@endphp

@if ($showPhoto)
    <img
        src="{{ route('users.avatar', $user) }}"
        alt="{{ $displayName }}"
        {{ $attributes->class([
            'inline-block shrink-0 rounded-full object-cover',
            $sizeClass,
        ]) }}
    >
@else
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
@endif
