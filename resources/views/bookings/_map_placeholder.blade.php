@php
    $routeMap = $routeMap ?? null;
    $mapSize = $mapSize ?? 'default';
    $placeholderCaption = $placeholderCaption ?? 'Pickup to dropoff route preview.';
    $placeholderMinClass = $mapSize === 'overview' ? 'min-h-[32rem]' : 'min-h-48';
    $imageMaxClass = $mapSize === 'overview' ? 'max-h-[32rem] min-h-[32rem]' : 'max-h-80';
    $emptyCaption = filled($routeMap?->message)
        ? $routeMap->message
        : $placeholderCaption;
@endphp

<div
    class="overflow-hidden rounded-md border border-dashed border-border-strong bg-primary-tone-1"
    aria-label="Drop-off location map preview"
>
    @if ($routeMap?->hasImage())
        <img
            src="{{ $routeMap->imageUrl }}"
            alt="Route from pickup to dropoff{{ isset($booking) ? ' for '.$booking->booking_number : '' }}"
            class="h-auto w-full object-cover {{ $imageMaxClass }}"
            width="640"
            height="400"
        >
    @else
        <div class="flex {{ $placeholderMinClass }} flex-col items-center justify-center gap-2 px-6 text-center">
            <x-ui.icon name="map" size="size-10" class="text-primary opacity-80" />
            <p class="max-w-md text-sm text-text-muted">{{ $emptyCaption }}</p>
            <span class="sr-only">Map preview for drop-off location</span>
        </div>
    @endif
</div>
