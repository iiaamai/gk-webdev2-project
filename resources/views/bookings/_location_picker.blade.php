@php
    $style = (string) config('gk.mapbox_style', 'mapbox/streets-v12');

    if (! str_starts_with($style, 'mapbox://')) {
        $style = 'mapbox://styles/'.$style;
    }

    $payload = [
        'token' => (string) config('gk.mapbox_token'),
        'style' => $style,
        'minZoom' => (int) config('gk.mapbox_min_zoom', 8),
        'maxZoom' => (int) config('gk.mapbox_max_zoom', 17),
        'center' => \App\Support\MapboxIntegration::defaultCenter(),
        'zoom' => \App\Support\MapboxIntegration::initialZoom(),
        'country' => 'ph',
        'proximity' => \App\Support\MapboxIntegration::defaultCenter(),
    ];
@endphp

<div class="space-y-3" data-location-picker>
    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            data-mode="pickup"
            class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-text-on-primary"
        >
            Set pickup
        </button>
        <button
            type="button"
            data-mode="dropoff"
            class="rounded-md border border-border bg-surface-elevated px-3 py-1.5 text-sm font-medium text-text"
        >
            Set dropoff
        </button>
    </div>

    <div class="relative">
        <label class="mb-1 block text-sm font-medium text-text" data-search-label for="location-search">Search pickup address</label>
        <input
            id="location-search"
            type="search"
            data-search
            autocomplete="off"
            placeholder="Search pickup address in the Philippines"
            class="block w-full rounded-md border border-border bg-surface-elevated px-3 py-2 text-sm text-text shadow-sm placeholder:text-text-subtle focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
        >
        <ul
            data-results
            hidden
            class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-border bg-surface-elevated py-1 text-sm shadow-md"
        ></ul>
        <p data-search-error class="mt-1 hidden text-sm text-danger"></p>
    </div>

    <div class="relative w-full overflow-hidden rounded-md border border-border" style="height: 20rem">
        <div
            data-map
            class="h-full w-full"
            role="region"
            aria-label="Set pickup and dropoff on the map"
        ></div>
        <div
            data-map-loading
            class="absolute inset-0 z-10 flex items-center justify-center bg-primary-tone-1 text-sm text-text-muted"
        >
            Loading map…
        </div>
    </div>

    <p data-status class="text-sm text-text-muted">Choose pickup, search or click the map, then switch to dropoff.</p>

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-md border border-border bg-surface-inset p-3">
            <p class="flex items-center gap-1.5 text-sm font-medium uppercase tracking-wide text-text-subtle">
                <x-ui.icon name="map-pin" size="size-5" class="fill-primary stroke-white" />
                Pickup
            </p>
            <p class="mt-1 text-sm font-medium text-text" data-pickup-label>—</p>
        </div>
        <div class="rounded-md border border-border bg-surface-inset p-3">
            <p class="flex items-center gap-1.5 text-sm font-medium uppercase tracking-wide text-text-subtle">
                <x-ui.icon name="map-pin" size="size-5" class="fill-success stroke-white" />
                Dropoff
            </p>
            <p class="mt-1 text-sm font-medium text-text" data-dropoff-label>—</p>
        </div>
    </div>

    <script type="application/json" data-location-picker-config>@json($payload)</script>
</div>

@once
    @push('head')
        <link href="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.css" rel="stylesheet">
        <script src="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.js"></script>
        @vite('resources/js/booking-location-map.js')
    @endpush
@endonce
