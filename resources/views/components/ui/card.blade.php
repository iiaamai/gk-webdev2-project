@props([
    'padding' => true,
])

<div {{ $attributes->class([
    'rounded-lg border border-border bg-surface-elevated shadow-sm',
    'p-5' => $padding,
]) }}>
    {{ $slot }}
</div>
