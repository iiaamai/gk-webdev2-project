@extends(auth()->user()?->isSystemAdmin() ? 'layouts.admin' : 'layouts.staff')

@section('title', 'Driver map')

@section('main-fill-height')
@endsection

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

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4">
        <x-ui.page-header
            class="mb-0 shrink-0"
            title="Driver map"
            subtitle="Latest location for drivers, including those without an active booking."
        />

        @unless ($mapboxReady)
            <div class="shrink-0 rounded-md border border-warning/30 bg-warning-subtle px-4 py-3 text-sm text-warning" role="status">
                Mapbox is off, so live driver pins cannot be drawn. Enable Mapbox to use this map.
            </div>
        @endunless

        @if ($mapboxReady)
            <div class="relative grid h-full min-h-0 flex-1 w-full grid-cols-1 grid-rows-[minmax(0,1fr)] overflow-hidden rounded-lg border border-border">
                <div
                    id="{{ $canvasId }}"
                    class="col-start-1 row-start-1 h-full min-h-0 w-full"
                    role="region"
                    aria-label="Driver locations"
                ></div>
                <div
                    data-map-loading-for="{{ $canvasId }}"
                    class="col-start-1 row-start-1 z-10 flex items-center justify-center bg-primary-tone-1 text-sm text-text-muted"
                >
                    Loading map…
                </div>
                <div
                    x-data="{ listOpen: true }"
                    class="pointer-events-none col-start-1 row-start-1 z-20 flex items-start justify-end p-3"
                >
                    <div class="pointer-events-auto flex max-h-full w-72 max-w-[calc(100%-1.5rem)] flex-col items-end">
                    <div class="flex max-h-full w-full flex-col overflow-hidden rounded-lg border border-border bg-surface-elevated/95 shadow-sm">
                        <button
                            type="button"
                            class="flex w-full shrink-0 items-center gap-2 px-3 py-2 text-left text-sm font-medium text-text hover:bg-surface-inset"
                            @click="listOpen = ! listOpen"
                            :aria-expanded="listOpen"
                            aria-controls="drivers-map-list"
                        >
                            <x-ui.icon name="users" size="size-4" />
                            <span class="flex-1">Drivers</span>
                            <span class="text-xs text-text-muted" data-drivers-count></span>
                            <x-ui.icon
                                name="chevron-down"
                                size="size-4"
                                class="shrink-0 text-text-muted transition-transform"
                                x-bind:class="listOpen && 'rotate-180'"
                            />
                        </button>
                        <div
                            x-show="listOpen"
                            x-cloak
                            class="flex min-h-0 max-h-[min(24rem,calc(100vh-10rem))] flex-col border-t border-border"
                        >
                            <div class="shrink-0 px-3 py-2">
                                <label class="sr-only" for="drivers-map-search">Search drivers</label>
                                <x-ui.input
                                    id="drivers-map-search"
                                    type="search"
                                    data-drivers-search
                                    placeholder="Search name, plate, or trip"
                                    autocomplete="off"
                                    class="!py-1.5 text-sm"
                                />
                            </div>
                            <ul
                                id="drivers-map-list"
                                data-drivers-list
                                class="min-h-0 flex-1 divide-y divide-border overflow-y-auto"
                            >
                                <li class="px-3 py-2 text-sm text-text-muted">Loading drivers…</li>
                            </ul>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="pointer-events-none col-start-1 row-start-1 z-20 flex flex-col items-start justify-end gap-2 self-end p-4">
                    <p
                        data-drivers-error
                        hidden
                        class="rounded-md border border-warning/30 bg-warning-subtle px-4 py-2 text-sm text-warning shadow-sm"
                        role="status"
                    >
                        Driver locations could not be loaded. Retrying…
                    </p>
                    <p
                        data-drivers-empty
                        hidden
                        class="rounded-md border border-border bg-surface-elevated/95 px-4 py-2 text-sm text-text-muted shadow-sm"
                    >
                        No driver has shared a location yet.
                    </p>
                </div>
            </div>
            <script type="application/json" data-drivers-map>@json($payload)</script>
            @push('head')
                <link href="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.css" rel="stylesheet">
                <script src="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.js"></script>
                @vite('resources/js/drivers-map.js')
            @endpush
        @else
            <div class="flex min-h-0 flex-1 items-center justify-center rounded-lg border border-border bg-surface-elevated">
                <x-ui.empty-state title="Driver map unavailable" icon="map">
                    <x-slot:description>Turn on Mapbox, then reopen this page. Drivers still share location while their portal is open.</x-slot:description>
                </x-ui.empty-state>
            </div>
        @endif
    </div>
@endsection
