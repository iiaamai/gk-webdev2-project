@props([
    'tagline' => 'Urban & regional truck logistics',
    'compact' => false,
])

<div @class([
    'flex flex-col items-center justify-center bg-transparent text-center text-text',
    'px-6 py-8 md:min-h-screen md:px-12 md:py-16' => ! $compact,
    'px-6 py-8' => $compact,
])>
    <div @class([
        'inline-flex items-center justify-center rounded-2xl bg-primary text-text-on-primary shadow-sm',
        'size-16 md:size-24' => ! $compact,
        'size-14' => $compact,
    ])>
        <x-ui.icon name="truck" :size="$compact ? 'size-7' : 'size-10 md:size-14'" />
    </div>

    <p @class([
        'mt-5 font-semibold tracking-tight text-text',
        'text-xl md:text-3xl' => ! $compact,
        'text-lg' => $compact,
    ])>
        GK Trucking Services
    </p>

    <p @class([
        'mt-2 max-w-sm text-text-muted',
        'text-sm md:text-base' => ! $compact,
        'text-sm' => $compact,
    ])>
        {{ $tagline }}
    </p>
</div>
