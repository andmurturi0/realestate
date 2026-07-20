<script setup lang="ts">
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    lat: number;
    lng: number;
}>();

// A ~200m privacy circle, never an exact pin — the public site never
// discloses a property's precise street location (FAZAT.md Faza 6A).
const PRIVACY_RADIUS_METERS = 200;

const mapElement = ref<HTMLDivElement>();
let map: L.Map | undefined;

onMounted(() => {
    map = L.map(mapElement.value as HTMLDivElement, {
        scrollWheelZoom: false,
        dragging: true,
    }).setView([props.lat, props.lng], 15);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    L.circle([props.lat, props.lng], {
        radius: PRIVACY_RADIUS_METERS,
        color: '#16a34a',
        fillColor: '#16a34a',
        fillOpacity: 0.15,
        weight: 2,
    }).addTo(map);
});

onBeforeUnmount(() => map?.remove());
</script>

<template>
    <div ref="mapElement" class="z-0 h-72 w-full rounded-xl border" />
</template>
