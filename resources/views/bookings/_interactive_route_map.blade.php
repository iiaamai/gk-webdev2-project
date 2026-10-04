@php
    $mapSize = $mapSize ?? 'default';
    $canvasId = 'route-map-'.substr(uniqid(), -8);
    $heightClass = $mapSize === 'overview' ? 'h-[32rem]' : 'h-80';
    $height = $mapSize === 'overview' ? '32rem' : '20rem';
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
        'geometry' => $routeMap->geometry,
        'pickup' => [(float) $routeMap->pickupLng, (float) $routeMap->pickupLat],
        'dropoff' => [(float) $routeMap->dropoffLng, (float) $routeMap->dropoffLat],
    ];
@endphp

<div class="relative w-full {{ $heightClass }}" style="height: {{ $height }}">
    <div
        id="{{ $canvasId }}"
        class="h-full w-full"
        role="region"
        aria-label="Route from pickup to dropoff{{ isset($booking) ? ' for '.$booking->booking_number : '' }}"
    ></div>
    <div
        data-map-loading-for="{{ $canvasId }}"
        class="absolute inset-0 z-10 flex items-center justify-center bg-primary-tone-1 text-sm text-text-muted"
    >
        Loading map…
    </div>
</div>
<script type="application/json" data-route-map-for="{{ $canvasId }}">@json($payload)</script>

@once
    @push('head')
        <link href="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.css" rel="stylesheet">
        <script src="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.js"></script>
        @vite('resources/js/booking-route-map.js')
    @endpush
@endonce
