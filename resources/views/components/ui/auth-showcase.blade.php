@props([
    'compact' => false,
])

<div @class([
    'flex flex-col items-center justify-center bg-transparent',
    'px-6 py-8 md:min-h-screen md:px-12 md:py-16' => ! $compact,
    'px-6 py-8' => $compact,
])>
    <x-brand.lockup
        surface="light"
        :size="$compact ? 'showcase-compact' : 'showcase'"
    />
</div>
