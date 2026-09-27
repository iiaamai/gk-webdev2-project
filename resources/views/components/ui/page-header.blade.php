@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->class(['mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-text">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-text-muted">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
