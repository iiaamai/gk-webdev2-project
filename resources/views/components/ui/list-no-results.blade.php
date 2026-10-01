@props([
    'clearUrl',
])

<x-ui.empty-state title="No matches" icon="search">
    <x-slot:description>Try different search terms or filters.</x-slot:description>
    <x-slot:actions>
        <x-ui.button href="{{ $clearUrl }}" variant="secondary">Clear filters</x-ui.button>
    </x-slot:actions>
</x-ui.empty-state>
