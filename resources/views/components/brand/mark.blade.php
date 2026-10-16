@props([
    'size' => 'md',
])

@php
    $box = match ($size) {
        'lg' => 'h-12 w-auto',
        'sm' => 'h-8 w-auto',
        default => 'h-10 w-auto',
    };
@endphp

<svg {{ $attributes->class([$box]) }} viewBox="0 0 88 56" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <path d="M8 44C8 24 22 8 40 8c10 0 16 5 22 12" stroke="currentColor" stroke-width="10" stroke-linecap="round"/>
    <path d="M18 38c6-10 16-16 28-14 8 1 14 6 20 12" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="3 5" opacity="0.9"/>
    <path d="M58 8 L82 22 L58 28 Z" fill="#C8102E"/>
    <path d="M40 20 h18 v26 h-8 v-10 h-10 z" fill="#C8102E"/>
</svg>
