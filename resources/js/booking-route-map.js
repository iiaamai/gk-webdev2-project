import { fetchJson } from './http';
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

            if (payload.driverPollUrl) {
                watchDriver(map, payload.driverPollUrl, element.id);
            }
        });

        map.on('error', hideLoading);
    });
});

function watchDriver(map, url, mapId) {
    const status = document.querySelector(`[data-driver-status-for="${mapId}"]`);
    let marker = null;

    const pull = async () => {
        try {
            const data = await fetchJson(url);

            if (data.lat == null || data.lng == null) {
                if (status) {
                    status.textContent = 'Driver location not shared yet.';
                }

                return;
            }

            const lngLat = [data.lng, data.lat];

            if (marker === null) {
                marker = new mapboxgl.Marker({ color: '#b45309' })
                    .setLngLat(lngLat)
                    .addTo(map);
            } else {
                marker.setLngLat(lngLat);
            }

            const label = data.name ? `${data.name}` : 'Driver';
            marker.setPopup(new mapboxgl.Popup({ offset: 24 }).setText(label));

            if (status) {
                status.textContent = data.updated_at
                    ? `${label} location updated ${new Date(data.updated_at).toLocaleTimeString()}`
                    : `${label} location is live.`;
            }
        } catch {
            if (status) {
                status.textContent = 'Driver location could not be loaded. Retrying…';
            }
        }
    };

    pull();
    window.setInterval(pull, 12000);
}
