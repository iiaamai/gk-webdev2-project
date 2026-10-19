@props([
    'logo' => '2',
    'surface' => 'light',
    'size' => 'md',
    'alt' => '',
])

@php
    $onDark = $surface === 'dark';

    $src = $logo === '1'
        ? asset('images/gk-logo-1.png')
        : asset('images/gk-logo-2.png');

    $box = match ($size) {
        'sm' => 'size-8',
        'md' => 'size-9',
        'lg' => 'size-10',
        'accent' => 'size-20 sm:size-24',
        'xl' => 'size-24 sm:size-28',
        default => 'size-9',
    };
@endphp

<span {{ $attributes->class([
    'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full',
    $onDark ? 'bg-brand-white' : '',
    $box,
]) }}>
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        class="h-full w-full origin-center scale-[1.72] object-cover"
    >
</span>
