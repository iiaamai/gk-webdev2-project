import { addAboveRoads, addPinsOverLabels, ensurePinImages, mapPinColors, pinFeatureCollection } from './map-pins';

const activeClass = 'rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-text-on-primary';
const idleClass = 'rounded-md border border-border bg-surface-elevated px-3 py-1.5 text-sm font-medium text-text';

function debounce(fn, wait) {
    let timer;

    return (...args) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => fn(...args), wait);
    };
}

function field(id) {
    return document.getElementById(id);
}

function readPoint(prefix) {
    const lng = Number.parseFloat(field(`${prefix}_lng`)?.value ?? '');
    const lat = Number.parseFloat(field(`${prefix}_lat`)?.value ?? '');
    const address = field(`${prefix}_address`)?.value?.trim() ?? '';

    if (!Number.isFinite(lng) || !Number.isFinite(lat)) {
        return null;
    }

    return { lng, lat, address };
}

function writePoint(prefix, point) {
    const latInput = field(`${prefix}_lat`);
    const lngInput = field(`${prefix}_lng`);
    const addressInput = field(`${prefix}_address`);

    if (latInput) {
        latInput.value = point.lat.toFixed(7);
    }

    if (lngInput) {
        lngInput.value = point.lng.toFixed(7);
    }

    if (addressInput && point.address) {
        addressInput.value = point.address;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof mapboxgl === 'undefined') {
        return;
    }

    document.querySelectorAll('[data-location-picker]').forEach((root) => {
        const configNode = root.querySelector('script[data-location-picker-config]');

        if (!configNode) {
            return;
        }

        let config;

        try {
            config = JSON.parse(configNode.textContent);
        } catch {
            return;
        }

        if (!config?.token) {
            return;
        }

        const mapElement = root.querySelector('[data-map]');
        const loading = root.querySelector('[data-map-loading]');
        const searchInput = root.querySelector('[data-search]');
        const searchLabel = root.querySelector('[data-search-label]');
        const results = root.querySelector('[data-results]');
        const error = root.querySelector('[data-search-error]');
        const status = root.querySelector('[data-status]');
        const pickupLabel = root.querySelector('[data-pickup-label]');
        const dropoffLabel = root.querySelector('[data-dropoff-label]');
        const modeButtons = [...root.querySelectorAll('[data-mode]')];

        if (!mapElement || !searchInput || !results) {
            return;
        }

        let mode = 'pickup';
        let pickup = readPoint('pickup');
        let dropoff = readPoint('dropoff');
        let routeRequest = 0;
        let hasFramed = false;
        let dragging = null;
        let suppressClick = false;
        const center = config.center ?? config.proximity ?? [120.9842, 14.5995];
        const zoom = Number.isFinite(config.zoom) ? config.zoom : 11;
        const lineId = 'draft-route-line';
        const pinsId = 'draft-pins';

        mapboxgl.accessToken = config.token;

        const map = new mapboxgl.Map({
            container: mapElement,
            style: config.style,
            center,
            zoom,
            minZoom: config.minZoom,
            maxZoom: config.maxZoom,
            fadeDuration: 0,
        });

        map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

        const hideLoading = () => {
            if (loading) {
                loading.hidden = true;
            }
        };

        function setError(message) {
            if (!error) {
                return;
            }

            error.textContent = message;
            error.classList.toggle('hidden', message === '');
        }

        function hideResults() {
            results.hidden = true;
            results.replaceChildren();
        }

        function paintMode() {
            modeButtons.forEach((button) => {
                const active = button.dataset.mode === mode;
                button.className = active ? activeClass : idleClass;
            });

            const label = mode === 'pickup' ? 'Search pickup address' : 'Search dropoff address';

            if (searchLabel) {
                searchLabel.textContent = label;
            }

            searchInput.placeholder = `${label} in the Philippines`;
            searchInput.value = (mode === 'pickup' ? pickup?.address : dropoff?.address) ?? '';
        }

        function paintLabels() {
            if (pickupLabel) {
                pickupLabel.textContent = pickup?.address || '—';
            }

            if (dropoffLabel) {
                dropoffLabel.textContent = dropoff?.address || '—';
            }
        }

        function syncPins() {
            const source = map.getSource(pinsId);

            if (source) {
                source.setData(pinFeatureCollection(pickup, dropoff));
            }
        }

        async function ensureLayers() {
            await ensurePinImages(map);

            if (!map.getSource('draft-route')) {
                map.addSource('draft-route', {
                    type: 'geojson',
                    data: { type: 'FeatureCollection', features: [] },
                });
                addAboveRoads(map, {
                    id: lineId,
                    type: 'line',
                    source: 'draft-route',
                    layout: { 'line-join': 'round', 'line-cap': 'round' },
                    paint: { 'line-color': mapPinColors.route, 'line-width': 6, 'line-opacity': 0.95 },
                });
            }

            if (!map.getSource(pinsId)) {
                map.addSource(pinsId, {
                    type: 'geojson',
                    data: pinFeatureCollection(pickup, dropoff),
                });
                addPinsOverLabels(map, {
                    id: pinsId,
                    type: 'symbol',
                    source: pinsId,
                    layout: {
                        'icon-image': ['match', ['get', 'kind'], 'dropoff', 'dropoff-pin', 'pickup-pin'],
                        'icon-anchor': 'bottom',
                        'icon-allow-overlap': true,
                        'icon-ignore-placement': true,
                    },
                });
            }
        }

        function beginDrag(event) {
            if (!map.getLayer(pinsId)) {
                return;
            }

            const hits = map.queryRenderedFeatures(event.point, { layers: [pinsId] });

            if (hits.length === 0) {
                return;
            }

            dragging = hits[0].properties.kind;
            suppressClick = true;
            map.dragPan.disable();
        }

        async function drawRoute() {
            const requestId = ++routeRequest;

            if (map.isStyleLoaded()) {
                await ensureLayers();
            }

            syncPins();

            if (!pickup || !dropoff) {
                const source = map.getSource('draft-route');

                if (source) {
                    source.setData({ type: 'FeatureCollection', features: [] });
                }

                fitKnownPoints();

                return;
            }

            const url = new URL(`https://api.mapbox.com/directions/v5/mapbox/driving/${pickup.lng},${pickup.lat};${dropoff.lng},${dropoff.lat}`);
            url.searchParams.set('geometries', 'geojson');
            url.searchParams.set('overview', 'full');
            url.searchParams.set('access_token', config.token);

            try {
                const response = await fetch(url);

                if (!response.ok || requestId !== routeRequest) {
                    return;
                }

                const body = await response.json();
                const geometry = body?.routes?.[0]?.geometry;
                const source = map.getSource('draft-route');

                if (source && geometry) {
                    source.setData(geometry);
                }

                fitKnownPoints(geometry?.coordinates);
            } catch {
                if (status) {
                    status.textContent = 'Route preview is unavailable. Both pins are still saved.';
                }
            }
        }

        function fitKnownPoints(extra = []) {
            const bounds = new mapboxgl.LngLatBounds();

            if (pickup) {
                bounds.extend([pickup.lng, pickup.lat]);
            }

            if (dropoff) {
                bounds.extend([dropoff.lng, dropoff.lat]);
            }

            extra.forEach((coord) => bounds.extend(coord));

            if (bounds.isEmpty()) {
                return;
            }

            map.fitBounds(bounds, {
                padding: 48,
                maxZoom: 14,
                animate: hasFramed,
            });
            hasFramed = true;
        }

        async function reverseGeocode(lng, lat) {
            const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json`);
            url.searchParams.set('access_token', config.token);
            url.searchParams.set('limit', '1');
            url.searchParams.set('country', config.country);

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error('reverse');
            }

            const body = await response.json();

            return body?.features?.[0]?.place_name ?? '';
        }

        async function place(which, lng, lat, address) {
            setError('');

            let resolved = address;

            if (!resolved) {
                try {
                    resolved = await reverseGeocode(lng, lat);
                } catch {
                    setError('Could not look up that map location.');
                    resolved = '';
                }
            }

            const point = { lng, lat, address: resolved };
            writePoint(which, point);

            if (which === 'pickup') {
                pickup = point;
            } else {
                dropoff = point;
            }

            syncPins();
            paintLabels();
            paintMode();

            if (status) {
                status.textContent = which === 'pickup'
                    ? 'Pickup set. Switch to dropoff and set the second pin.'
                    : 'Dropoff set.';
            }

            if (map.isStyleLoaded()) {
                await drawRoute();
            }
        }

        async function search(query) {
            hideResults();
            setError('');

            if (query.trim().length < 3) {
                return;
            }

            const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json`);
            url.searchParams.set('access_token', config.token);
            url.searchParams.set('autocomplete', 'true');
            url.searchParams.set('country', config.country);
            url.searchParams.set('limit', '5');
            url.searchParams.set('proximity', (config.proximity ?? center).join(','));

            try {
                const response = await fetch(url);

                if (!response.ok) {
                    setError('Address search is unavailable right now.');

                    return;
                }

                const body = await response.json();
                const features = body?.features ?? [];

                if (features.length === 0) {
                    setError('No matching addresses.');

                    return;
                }

                features.forEach((feature) => {
                    const item = document.createElement('li');
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'block w-full px-3 py-2 text-left text-text hover:bg-surface-inset';
                    button.textContent = feature.place_name;
                    button.addEventListener('click', () => {
                        const [lng, lat] = feature.center;
                        hideResults();
                        place(mode, lng, lat, feature.place_name);
                    });
                    item.append(button);
                    results.append(item);
                });

                results.hidden = false;
            } catch {
                setError('Address search is unavailable right now.');
            }
        }

        modeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                mode = button.dataset.mode;
                hideResults();
                setError('');
                paintMode();
            });
        });

        searchInput.addEventListener('input', debounce(() => {
            search(searchInput.value);
        }, 300));

        document.addEventListener('click', (event) => {
            if (!root.contains(event.target)) {
                hideResults();
            }
        });

        map.on('mousedown', beginDrag);
        map.on('touchstart', beginDrag);

        const moveDrag = (event) => {
            if (!dragging) {
                return;
            }

            const point = dragging === 'pickup' ? pickup : dropoff;

            if (!point) {
                return;
            }

            point.lng = event.lngLat.lng;
            point.lat = event.lngLat.lat;
            syncPins();
        };

        const endDrag = async () => {
            if (!dragging) {
                return;
            }

            const which = dragging;
            const point = which === 'pickup' ? pickup : dropoff;
            dragging = null;
            map.dragPan.enable();

            if (point) {
                await place(which, point.lng, point.lat, null);
            }
        };

        map.on('mousemove', moveDrag);
        map.on('touchmove', moveDrag);
        map.on('mouseup', endDrag);
        map.on('touchend', endDrag);

        map.on('click', (event) => {
            if (suppressClick) {
                suppressClick = false;

                return;
            }

            place(mode, event.lngLat.lng, event.lngLat.lat, null);
        });

        map.on('load', () => {
            ensureLayers()
                .then(() => drawRoute())
                .finally(() => {
                    map.once('idle', hideLoading);
                });
        });

        map.on('error', hideLoading);

        paintMode();
        paintLabels();
    });
});
