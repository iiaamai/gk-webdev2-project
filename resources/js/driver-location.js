const root = document.querySelector('[data-driver-location]');

if (root && navigator.geolocation) {
    const url = root.dataset.driverLocation;
    const note = document.querySelector('[data-driver-location-note]');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let lastSent = 0;

    const send = (position) => {
        const now = Date.now();

        if (now - lastSent < 12000) {
            return;
        }

        lastSent = now;

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token ?? '',
            },
            body: JSON.stringify({
                lat: position.coords.latitude,
                lng: position.coords.longitude,
            }),
        }).then((response) => {
            if (note && response.ok) {
                note.textContent = 'Sharing your location with dispatch.';
            }
        }).catch(() => {});
    };

    navigator.geolocation.watchPosition(send, () => {
        if (note) {
            note.textContent = 'Location permission is off, so dispatch cannot see you.';
        }
    }, {
        enableHighAccuracy: true,
        maximumAge: 10000,
        timeout: 15000,
    });
}
