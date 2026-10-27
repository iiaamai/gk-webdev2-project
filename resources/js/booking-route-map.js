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

        if (!payload.fullscreen) {
            map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');
        }

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
                    ...(payload.fullscreen
                        ? {
                            'icon-pitch-alignment': 'viewport',
                            'icon-rotation-alignment': 'viewport',
                        }
                        : {}),
                },
            });

            if (!payload.fullscreen) {
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
            }

            map.once('idle', hideLoading);

            if (payload.liveNav) {
                startLiveNav(map, payload, lineId);
            } else if (payload.driverPollUrl) {
                watchDriver(map, payload.driverPollUrl, element.id);
            }

            if (payload.fullscreen) {
                map.resize();
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

function haversineMeters(from, to) {
    const earth = 6371000;
    const toRad = (degrees) => (degrees * Math.PI) / 180;
    const dLat = toRad(to[1] - from[1]);
    const dLng = toRad(to[0] - from[0]);
    const lat1 = toRad(from[1]);
    const lat2 = toRad(to[1]);
    const h =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;

    return 2 * earth * Math.asin(Math.sqrt(h));
}

function minDistanceToLine(point, coordinates) {
    let min = Infinity;

    coordinates.forEach((coord) => {
        min = Math.min(min, haversineMeters(point, coord));
    });

    return min;
}

function bearingDegrees(from, to) {
    const toRad = (degrees) => (degrees * Math.PI) / 180;
    const lng1 = toRad(from[0]);
    const lat1 = toRad(from[1]);
    const lng2 = toRad(to[0]);
    const lat2 = toRad(to[1]);
    const y = Math.sin(lng2 - lng1) * Math.cos(lat2);
    const x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(lng2 - lng1);

    return ((Math.atan2(y, x) * 180) / Math.PI + 360) % 360;
}

function compassHeading(coords) {
    const heading = coords?.heading;

    if (typeof heading !== 'number' || Number.isNaN(heading) || heading < 0) {
        return null;
    }

    return heading;
}

function routeAheadBearing(from, coordinates) {
    if (!coordinates || coordinates.length < 2) {
        return null;
    }

    let nearest = 0;
    let nearestDistance = Infinity;

    coordinates.forEach((coord, index) => {
        const distance = haversineMeters(from, coord);

        if (distance < nearestDistance) {
            nearestDistance = distance;
            nearest = index;
        }
    });

    let ahead = Math.min(nearest + 1, coordinates.length - 1);

    while (ahead < coordinates.length - 1 && haversineMeters(from, coordinates[ahead]) < 25) {
        ahead += 1;
    }

    if (ahead === nearest) {
        return null;
    }

    return bearingDegrees(from, coordinates[ahead]);
}

function startLiveNav(map, payload, lineId) {
    const status = document.querySelector('[data-driver-nav-status]');
    const eta = document.querySelector('[data-nav-eta]');
    const recenter = document.querySelector('[data-nav-recenter]');
    const destinations = {
        pickup: payload.pickup,
        dropoff: payload.dropoff,
    };
    const destKey = payload.navDestination === 'dropoff' ? 'dropoff' : 'pickup';
    const dest = destinations[destKey];
    const offRouteMeters = payload.offRouteMeters ?? 80;
    let marker = null;
    let lastCoords = [];
    let lastFetchAt = 0;
    let lastPosition = null;
    let lastBearing = 0;
    let following = true;
    let routeRequest = 0;

    const setStatus = (text) => {
        if (status) {
            status.textContent = text;
        }
    };

    const setEta = (text) => {
        if (eta) {
            eta.textContent = text;
        }
    };

    const applyGeometry = (geometry) => {
        const source = map.getSource(lineId);

        if (!source || !geometry) {
            return;
        }

        source.setData(geometry);
        lastCoords = geometry.coordinates ?? [];
    };

    const cameraOptions = (from, bearing) => ({
        center: from,
        bearing,
        pitch: 50,
        zoom: 16,
        padding: { top: 80, bottom: 220, left: 40, right: 40 },
    });

    const navBearing = (from, position = null) => {
        const heading = compassHeading(position?.coords);

        if (heading !== null) {
            return heading;
        }

        if (lastPosition !== null && haversineMeters(lastPosition, from) >= 8) {
            return bearingDegrees(lastPosition, from);
        }

        const alongRoute = routeAheadBearing(from, lastCoords);

        if (alongRoute !== null) {
            return alongRoute;
        }

        if (dest) {
            return bearingDegrees(from, dest);
        }

        return lastBearing;
    };

    const followCamera = (from, bearing) => {
        if (!following) {
            return;
        }

        map.easeTo({
            ...cameraOptions(from, bearing),
            duration: 600,
        });
    };

    const snapCamera = (from, bearing) => {
        map.jumpTo(cameraOptions(from, bearing));
    };

    const fetchRoute = async (from) => {
        const now = Date.now();

        if (now - lastFetchAt < 8000) {
            return;
        }

        lastFetchAt = now;
        const requestId = ++routeRequest;
        const url = new URL(
            `https://api.mapbox.com/directions/v5/mapbox/driving/${from[0]},${from[1]};${dest[0]},${dest[1]}`,
        );
        url.searchParams.set('geometries', 'geojson');
        url.searchParams.set('overview', 'full');
        url.searchParams.set('access_token', payload.token);

        try {
            const response = await fetch(url);

            if (!response.ok || requestId !== routeRequest) {
                return;
            }

            const body = await response.json();
            const route = body?.routes?.[0];
            const geometry = route?.geometry;

            if (!geometry) {
                setStatus('Could not calculate a driving route. Try again in a moment.');

                return;
            }

            applyGeometry(geometry);

            const km = route.distance != null ? (route.distance / 1000).toFixed(1) : '—';
            const minutes = route.duration != null ? Math.round(route.duration / 60) : '—';
            setEta(`${km} km · ${minutes} min`);
            setStatus('Following the route. Recalculates if you leave it.');
        } catch {
            setStatus('Could not refresh the route. Your location is still shown.');
        }
    };

    const onPosition = (position) => {
        const from = [position.coords.longitude, position.coords.latitude];
        const bearing = navBearing(from, position);

        lastBearing = bearing;
        lastPosition = from;

        if (marker === null) {
            marker = new mapboxgl.Marker({
                color: '#b45309',
                pitchAlignment: 'viewport',
                rotationAlignment: 'viewport',
            })
                .setLngLat(from)
                .addTo(map);
        } else {
            marker.setLngLat(from);
        }

        followCamera(from, bearing);

        const remaining = haversineMeters(from, dest);

        if (remaining <= 40) {
            setEta('Arrived');
            setStatus(`You have arrived at ${destKey}.`);

            return;
        }

        if (lastCoords.length === 0 || minDistanceToLine(from, lastCoords) > offRouteMeters) {
            fetchRoute(from);
        }
    };

    map.on('dragstart', () => {
        following = false;
        setStatus('Map moved. Tap Recenter to follow your heading.');
    });

    recenter?.addEventListener('click', () => {
        following = true;

        if (!lastPosition) {
            setStatus('Waiting for GPS before recentering.');

            return;
        }

        const bearing = navBearing(lastPosition);
        lastBearing = bearing;
        snapCamera(lastPosition, bearing);
        setStatus('Following the route.');
    });

    setStatus('Allow location to start following the route.');

    if (!navigator.geolocation) {
        setStatus('This browser cannot share GPS. Open the job on a phone with location enabled.');

        return;
    }

    navigator.geolocation.watchPosition(
        onPosition,
        () => {
            setStatus('Location is blocked. Allow GPS for this site to follow the route.');
        },
        {
            enableHighAccuracy: true,
            maximumAge: 5000,
            timeout: 20000,
        },
    );
}
