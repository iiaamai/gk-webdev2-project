import { addAboveRoads, addPinsOverLabels, ensurePinImages, mapPinColors, pinFeatureCollection } from './map-pins';

document.addEventListener('DOMContentLoaded', () => {
    if (typeof mapboxgl === 'undefined') {
        return;
    }

    document.querySelectorAll('script[data-route-map-for]').forEach((node) => {
        const element = document.getElementById(node.dataset.routeMapFor);
        const loading = document.querySelector(`[data-map-loading-for="${node.dataset.routeMapFor}"]`);

        if (!element) {
            return;
        }

        let payload;

        try {
            payload = JSON.parse(node.textContent);
        } catch {
            return;
        }

        if (!payload?.token || !payload.geometry) {
            return;
        }

        mapboxgl.accessToken = payload.token;

        const map = new mapboxgl.Map({
            container: element,
            style: payload.style,
            center: payload.center,
            zoom: payload.zoom,
            minZoom: payload.minZoom,
            maxZoom: payload.maxZoom,
            fadeDuration: 0,
        });

        map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

        const hideLoading = () => {
            if (loading) {
                loading.hidden = true;
            }
        };

        map.on('load', async () => {
            try {
                await ensurePinImages(map);
            } catch {
                hideLoading();

                return;
            }

            const lineId = `${element.id}-line`;
            const pinsId = `${element.id}-pins`;

            map.addSource(lineId, {
                type: 'geojson',
                data: payload.geometry,
            });

            addAboveRoads(map, {
                id: lineId,
                type: 'line',
                source: lineId,
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round',
                },
                paint: {
                    'line-color': mapPinColors.route,
                    'line-width': 6,
                    'line-opacity': 0.95,
                },
            });

            map.addSource(pinsId, {
                type: 'geojson',
                data: pinFeatureCollection(
                    { lng: payload.pickup[0], lat: payload.pickup[1] },
                    { lng: payload.dropoff[0], lat: payload.dropoff[1] },
                ),
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

            const bounds = new mapboxgl.LngLatBounds();

            (payload.geometry.coordinates || []).forEach((coord) => {
                bounds.extend(coord);
            });

            if (!bounds.isEmpty()) {
                map.fitBounds(bounds, {
                    padding: 48,
                    maxZoom: payload.maxZoom,
                    animate: false,
                });
            }

            map.once('idle', hideLoading);
        });

        map.on('error', hideLoading);
    });
});
