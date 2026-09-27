@props([])

<div {{ $attributes->class(['overflow-x-auto rounded-lg border border-border bg-surface-elevated']) }}>
    <table class="min-w-full divide-y divide-border text-left text-sm">
        @isset($head)
            <thead class="bg-surface-inset text-text-muted">
                {{ $head }}
            </thead>
        @endisset
        <tbody class="divide-y divide-border bg-surface-elevated text-text">
            {{ $slot }}
        </tbody>
    </table>
</div>
