@props([
    'name',
])

@error($name)
    <p {{ $attributes->class(['mt-1 text-sm text-danger']) }}>{{ $message }}</p>
@enderror
