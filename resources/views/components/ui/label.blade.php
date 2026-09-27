@props([
    'for' => null,
])

<label
    @if ($for) for="{{ $for }}" @endif
    {{ $attributes->class(['mb-1 block text-sm font-medium text-text']) }}
>{{ $slot }}</label>
