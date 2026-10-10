document.addEventListener('DOMContentLoaded', () => {
    const node = document.querySelector('script[data-drivers-map]');

    if (!node || typeof mapboxgl === 'undefined') {
        return;
    }

    let payload;

    try {
        payload = JSON.parse(node.textContent);
    } catch {
        return;
    }

    const element = document.getElementById(payload.canvasId);

    if (!element || !payload.token) {
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
    });

    map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

    const markers = new Map();
    const empty = document.querySelector('[data-drivers-empty]');
    const loading = document.querySelector(`[data-map-loading-for="${payload.canvasId}"]`);

    const pull = async () => {
        try {
            const response = await fetch(payload.locationsUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const drivers = data.drivers ?? [];
            const seen = new Set();

            drivers.forEach((driver) => {
                seen.add(driver.id);
                const lngLat = [driver.lng, driver.lat];
                const text = [
                    driver.name,
                    driver.vehicle,
                    driver.booking_number ? `Trip ${driver.booking_number}` : 'No active booking',
                ].filter(Boolean).join(' · ');

                const existing = markers.get(driver.id);

                if (existing) {
                    existing.setLngLat(lngLat);
                    existing.setPopup(new mapboxgl.Popup({ offset: 24 }).setText(text));
                } else {
                    const marker = new mapboxgl.Marker({ color: '#b45309' })
                        .setLngLat(lngLat)
                        .setPopup(new mapboxgl.Popup({ offset: 24 }).setText(text))
                        .addTo(map);

                    markers.set(driver.id, marker);
                }
            });

            markers.forEach((marker, id) => {
                if (!seen.has(id)) {
                    marker.remove();
                    markers.delete(id);
                }
            });

            if (empty) {
                empty.hidden = drivers.length > 0;
            }

            if (loading) {
                loading.hidden = true;
            }
        } catch {
            if (loading) {
                loading.hidden = true;
            }
        }
    };

    map.on('load', () => {
        pull();
        window.setInterval(pull, 12000);
    });
});
