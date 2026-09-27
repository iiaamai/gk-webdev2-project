@props([
    'name',
    'size' => 'size-5',
])

<i
    data-lucide="{{ $name }}"
    {{ $attributes->class([$size, 'shrink-0 inline-block']) }}
    aria-hidden="true"
></i>
