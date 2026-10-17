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

    [$pad, $imgClass] = match ($size) {
        'sm' => ['p-1', 'h-5 w-5 object-contain'],
        'md' => ['p-1', 'h-6 w-6 object-contain'],
        'lg' => ['p-1.5', 'h-8 w-8 object-contain'],
        'accent' => ['', 'h-16 w-16 max-h-20 max-w-20 object-contain sm:h-20 sm:w-20'],
        'xl' => ['p-2', 'h-20 w-20 max-h-24 max-w-24 object-contain sm:h-24 sm:w-24'],
        default => ['p-1', 'h-6 w-6 object-contain'],
    };
@endphp

@if ($onDark)
    <span {{ $attributes->class(['inline-flex shrink-0 items-center justify-center rounded-full bg-brand-white', $pad]) }}>
        <img src="{{ $src }}" alt="{{ $alt }}" class="{{ $imgClass }}">
    </span>
@else
    <img
        {{ $attributes->class([$imgClass]) }}
        src="{{ $src }}"
        alt="{{ $alt }}"
    >
@endif
