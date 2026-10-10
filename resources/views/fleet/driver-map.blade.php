@extends(auth()->user()?->isSystemAdmin() ? 'layouts.admin' : 'layouts.staff')

@section('title', 'Driver map')

@section('content')
    @php
        $canvasId = 'drivers-map';
        $style = (string) config('gk.mapbox_style', 'mapbox/streets-v12');

        if (! str_starts_with($style, 'mapbox://')) {
            $style = 'mapbox://styles/'.$style;
        }

        $payload = [
            'canvasId' => $canvasId,
            'token' => (string) config('gk.mapbox_token'),
            'style' => $style,
            'minZoom' => (int) config('gk.mapbox_min_zoom', 8),
            'maxZoom' => (int) config('gk.mapbox_max_zoom', 17),
            'center' => \App\Support\MapboxIntegration::defaultCenter(),
            'zoom' => \App\Support\MapboxIntegration::initialZoom(),
            'locationsUrl' => $locationsUrl,
        ];
    @endphp

    <x-ui.page-header
        title="Driver map"
        subtitle="Latest location for drivers, including those without an active booking."
    />

    @unless ($mapboxReady)
        <div class="mb-4 rounded-md border border-warning/30 bg-warning-subtle px-4 py-3 text-sm text-warning" role="status">
            Mapbox is off, so live driver pins cannot be drawn. Enable Mapbox to use this map.
        </div>
    @endunless

    <x-ui.card>
        @if ($mapboxReady)
            <div class="relative h-[32rem] w-full">
                <div id="{{ $canvasId }}" class="h-full w-full" role="region" aria-label="Driver locations"></div>
                <div
                    data-map-loading-for="{{ $canvasId }}"
                    class="absolute inset-0 z-10 flex items-center justify-center bg-primary-tone-1 text-sm text-text-muted"
                >
                    Loading map…
                </div>
            </div>
            <p class="mt-3 text-sm text-text-muted" data-drivers-empty hidden>
                No driver has shared a location yet.
            </p>
            <script type="application/json" data-drivers-map>@json($payload)</script>
            @push('head')
                <link href="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.css" rel="stylesheet">
                <script src="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.js"></script>
                @vite('resources/js/drivers-map.js')
            @endpush
        @else
            <x-ui.empty-state title="Driver map unavailable" icon="map">
                <x-slot:description>Turn on Mapbox, then reopen this page. Drivers still share location while their portal is open.</x-slot:description>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
@endsection
