import { createElement, MapPin } from 'lucide';

export const mapPinColors = {
    pickup: '#2596be',
    dropoff: '#047857',
    route: '#1e7898',
};

export function firstSymbolLayerId(map) {
    return map.getStyle()?.layers?.find((layer) => layer.type === 'symbol')?.id;
}

/**
 * Insert after the last road/tunnel/bridge paint layer so the route sits
 * just above the street network and still under labels/buildings that follow.
 */
export function layerIdJustAboveRoads(map) {
    const layers = map.getStyle()?.layers ?? [];
    let lastRoadIndex = -1;

    layers.forEach((layer, index) => {
        if (
            layer.type !== 'symbol'
            && /^(road|tunnel|bridge|ferry|aerialway|turning-traffic|guide)/i.test(layer.id)
        ) {
            lastRoadIndex = index;
        }
    });

    if (lastRoadIndex >= 0 && lastRoadIndex < layers.length - 1) {
        return layers[lastRoadIndex + 1].id;
    }

    return firstSymbolLayerId(map);
}

export function addAboveRoads(map, layer) {
    const beforeId = layerIdJustAboveRoads(map);

    if (beforeId && map.getLayer(beforeId)) {
        map.addLayer(layer, beforeId);

        return;
    }

    map.addLayer(layer);
}

export function addPinsOverLabels(map, layer) {
    map.addLayer(layer);
}

function renderPin(kind) {
    const svg = createElement(MapPin, {
        width: 64,
        height: 64,
        fill: mapPinColors[kind],
        stroke: '#ffffff',
        'stroke-width': 2,
    });
    const xml = new XMLSerializer().serializeToString(svg);

    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error('pin'));
        image.src = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(xml)}`;
    });
}

export async function ensurePinImages(map) {
    await Promise.all(['pickup', 'dropoff'].map(async (kind) => {
        const id = `${kind}-pin`;

        if (map.hasImage(id)) {
            return;
        }

        const image = await renderPin(kind);

        if (!map.hasImage(id)) {
            map.addImage(id, image, { pixelRatio: 2 });
        }
    }));
}

export function pinFeatureCollection(pickup, dropoff) {
    const features = [];

    if (pickup) {
        features.push({
            type: 'Feature',
            properties: { kind: 'pickup' },
            geometry: { type: 'Point', coordinates: [pickup.lng ?? pickup[0], pickup.lat ?? pickup[1]] },
        });
    }

    if (dropoff) {
        features.push({
            type: 'Feature',
            properties: { kind: 'dropoff' },
            geometry: { type: 'Point', coordinates: [dropoff.lng ?? dropoff[0], dropoff.lat ?? dropoff[1]] },
        });
    }

    return { type: 'FeatureCollection', features };
}
