<textarea
    {{ $attributes->class([
        'block w-full rounded-md border border-border bg-surface-elevated px-3 py-2 text-sm text-text shadow-sm',
        'placeholder:text-text-subtle',
        'focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30',
    ]) }}
>{{ $slot }}</textarea>
