@props([
    'logo' => '2',
    'surface' => 'light',
    'size' => 'md',
    'inset' => 'crop',
    'alt' => '',
])

@php
    $onDark = $surface === 'dark';
    $comfortable = $inset === 'comfortable';

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

    $logoNudge = $logo === '2' ? 'translate-y-[3.6%]' : '';

    $imgClass = $comfortable
        ? 'h-full w-full origin-center scale-[1.12] object-contain '.$logoNudge
        : 'h-full w-full origin-center scale-[1.72] object-cover '.$logoNudge;
@endphp

<span {{ $attributes->class([
    'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full',
    $onDark ? 'bg-brand-white' : '',
    $comfortable ? 'p-0.5' : '',
    $box,
]) }}>
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        class="{{ $imgClass }}"
    >
</span>
