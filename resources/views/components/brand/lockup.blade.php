@props([
    'surface' => 'light',
    'size' => 'md',
])

@php
    $onDark = $surface === 'dark';

    $imgClass = match ($size) {
        'sm' => 'h-12 w-auto object-contain',
        'md' => 'h-16 w-auto object-contain sm:h-20',
        'showcase' => 'h-auto w-full max-w-sm object-contain md:max-w-md',
        'showcase-compact' => 'h-auto w-full max-w-xs object-contain',
        default => 'h-16 w-auto object-contain sm:h-20',
    };
@endphp

@if ($onDark)
    <span {{ $attributes->class(['inline-flex shrink-0 items-center justify-center rounded-xl bg-brand-white p-2']) }}>
        <img
            src="{{ asset('images/gk-brand.png') }}"
            alt="GK Logistics — Connecting the world through logistics"
            class="{{ $imgClass }}"
        >
    </span>
@else
    <img
        {{ $attributes->class([$imgClass]) }}
        src="{{ asset('images/gk-brand.png') }}"
        alt="GK Logistics — Connecting the world through logistics"
    >
@endif
