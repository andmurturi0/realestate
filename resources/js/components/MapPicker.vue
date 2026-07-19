<script setup lang="ts">
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    lat: number | null;
    lng: number | null;
}>();

const emit = defineEmits<{
    (e: 'update', coords: { lat: number; lng: number }): void;
}>();

// Prishtina centre — starting view only, never persisted without a user action.
const FALLBACK_CENTRE: [number, number] = [42.6629, 21.1655];

const mapElement = ref<HTMLDivElement>();

let map: L.Map | undefined;
let marker: L.Marker | undefined;

const pinIcon = L.divIcon({
    html: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="color:#e11d48;width:30px;height:42px"><path d="M12 0C7.31 0 3.5 3.81 3.5 8.5c0 6.11 7.53 14.68 7.85 15.04a.87.87 0 0 0 1.3 0c.32-.36 7.85-8.93 7.85-15.04C20.5 3.81 16.69 0 12 0zm0 12a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7z"/></svg>',
    className: '',
    iconSize: [30, 42],
    iconAnchor: [15, 42],
});

function emitPosition(position: L.LatLng) {
    emit('update', {
        lat: Number(position.lat.toFixed(7)),
        lng: Number(position.lng.toFixed(7)),
    });
}

onMounted(() => {
    const start: [number, number] = props.lat != null && props.lng != null ? [Number(props.lat), Number(props.lng)] : FALLBACK_CENTRE;

    map = L.map(mapElement.value as HTMLDivElement).setView(start, 14);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    marker = L.marker(start, { draggable: true, icon: pinIcon }).addTo(map);

    marker.on('dragend', () => emitPosition(marker!.getLatLng()));

    map.on('click', (event: L.LeafletMouseEvent) => {
        marker!.setLatLng(event.latlng);
        emitPosition(event.latlng);
    });
});

watch(
    () => [props.lat, props.lng],
    ([lat, lng]) => {
        if (map && marker && lat != null && lng != null) {
            const current = marker.getLatLng();

            if (Math.abs(current.lat - Number(lat)) > 1e-7 || Math.abs(current.lng - Number(lng)) > 1e-7) {
                marker.setLatLng([Number(lat), Number(lng)]);
                map.panTo([Number(lat), Number(lng)]);
            }
        }
    },
);

onBeforeUnmount(() => map?.remove());
</script>

<template>
    <div ref="mapElement" class="z-0 h-64 w-full rounded-md border" />
</template>
