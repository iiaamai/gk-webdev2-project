@props([
    'size' => 'md',
    'surface' => 'light',
    'alt' => 'GK Logistics',
])

@php
    $sealSize = match ($size) {
        'lg' => 'lg',
        'sm' => 'sm',
        default => 'md',
    };
@endphp

<x-brand.seal
    logo="1"
    :surface="$surface"
    :size="$sealSize"
    :alt="$alt"
    {{ $attributes }}
/>
