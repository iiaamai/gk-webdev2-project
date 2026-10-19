import { fetchJson } from './http';

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

    const mapShell = element.parentElement;

    if (!mapShell) {
        return;
    }

    mapboxgl.accessToken = payload.token;

    let map;

    const resizeMap = () => {
        if (map) {
            map.resize();
        }
    };

    const scheduleResize = () => {
        requestAnimationFrame(() => {
            resizeMap();
            requestAnimationFrame(resizeMap);
        });
    };

    const markers = new Map();
    const empty = document.querySelector('[data-drivers-empty]');
    const error = document.querySelector('[data-drivers-error]');
    const list = document.querySelector('[data-drivers-list]');
    const count = document.querySelector('[data-drivers-count]');
    const search = document.querySelector('[data-drivers-search]');
    const loading = document.querySelector(`[data-map-loading-for="${payload.canvasId}"]`);
    let allDrivers = [];

    const hasLocation = (driver) => driver.lat !== null && driver.lng !== null;

    const describe = (driver) => [
        driver.vehicle,
        driver.booking_number ? `Trip ${driver.booking_number}` : 'No active booking',
    ].filter(Boolean).join(' · ');

    const focusDriver = (driver) => {
        if (!map) {
            return;
        }

        const marker = markers.get(driver.id);

        map.flyTo({ center: [driver.lng, driver.lat], zoom: Math.max(map.getZoom(), 14) });

        if (marker && !marker.getPopup().isOpen()) {
            marker.togglePopup();
        }
    };

    const matchesSearch = (driver, query) => {
        if (query === '') {
            return true;
        }

        const haystack = [
            driver.name,
            driver.vehicle,
            driver.booking_number,
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return haystack.includes(query);
    };

    const filteredDrivers = () => {
        const query = search?.value.trim().toLowerCase() ?? '';

        return allDrivers.filter((driver) => matchesSearch(driver, query));
    };

    const renderList = (drivers, emptyMessage = 'No drivers yet.') => {
        if (!list) {
            return;
        }

        list.replaceChildren();

        if (drivers.length === 0) {
            const item = document.createElement('li');
            item.className = 'px-3 py-2 text-sm text-text-muted';
            item.textContent = emptyMessage;
            list.append(item);

            return;
        }

        drivers.forEach((driver) => {
            const item = document.createElement('li');
            const button = document.createElement('button');
            const name = document.createElement('span');
            const detail = document.createElement('span');
            const located = hasLocation(driver);

            button.type = 'button';
            button.disabled = !located;
            button.className = located
                ? 'flex w-full flex-col px-3 py-2 text-left hover:bg-surface-inset'
                : 'flex w-full cursor-not-allowed flex-col px-3 py-2 text-left opacity-60';

            name.className = 'text-sm font-medium text-text';
            name.textContent = driver.name;

            detail.className = 'text-xs text-text-muted';
            detail.textContent = located ? describe(driver) : 'No location yet';

            button.append(name, detail);

            if (located) {
                button.addEventListener('click', () => focusDriver(driver));
            }

            item.append(button);
            list.append(item);
        });
    };

    const refreshList = () => {
        if (count) {
            count.textContent = String(allDrivers.length);
        }

        const query = search?.value.trim().toLowerCase() ?? '';
        const drivers = filteredDrivers();
        const emptyMessage = query === '' ? 'No drivers yet.' : 'No drivers match your search.';

        renderList(drivers, emptyMessage);
    };

    search?.addEventListener('input', refreshList);

    const pull = async () => {
        try {
            const data = await fetchJson(payload.locationsUrl);
            const drivers = data?.drivers ?? [];
            const located = drivers.filter(hasLocation);
            const seen = new Set();

            if (!map) {
                return;
            }

            located.forEach((driver) => {
                seen.add(driver.id);
                const lngLat = [driver.lng, driver.lat];
                const text = [driver.name, describe(driver)].join(' · ');
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

            allDrivers = drivers;
            refreshList();

            if (empty) {
                empty.hidden = located.length > 0;
            }

            if (error) {
                error.hidden = true;
            }
        } catch {
            if (error) {
                error.hidden = false;
            }
        }
    };

    const hideLoading = () => {
        if (loading) {
            loading.hidden = true;
        }
    };

    const bootMap = () => {
        if (element.clientWidth === 0 || element.clientHeight === 0) {
            requestAnimationFrame(bootMap);

            return;
        }

        map = new mapboxgl.Map({
            container: element,
            style: payload.style,
            center: payload.center,
            zoom: payload.zoom,
            minZoom: payload.minZoom,
            maxZoom: payload.maxZoom,
        });

        map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-left');

        window.addEventListener('resize', resizeMap);

        if (typeof ResizeObserver !== 'undefined') {
            const observer = new ResizeObserver(() => {
                scheduleResize();
            });
            observer.observe(mapShell);
        }

        map.once('error', hideLoading);

        map.on('load', () => {
            hideLoading();
            scheduleResize();
            pull();
            window.setInterval(pull, 12000);
        });
    };

    bootMap();
});
