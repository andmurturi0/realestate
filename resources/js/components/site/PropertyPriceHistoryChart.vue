<script setup lang="ts">
import { formatPriceCents } from '@/lib/property';
import { useTranslations } from '@/lib/trans';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps<{
    history: { date: string; price: number }[];
}>();

const { t, locale } = useTranslations();

const series = computed(() => [
    {
        name: t('Çmimi'),
        data: props.history.map((point) => ({ x: point.date, y: point.price / 100 })),
    },
]);

const options = computed(() => ({
    chart: { type: 'area', toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['#16a34a'],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
    xaxis: { type: 'datetime' },
    yaxis: { labels: { formatter: (value: number) => formatPriceCents(Math.round(value * 100), locale) } },
    tooltip: { y: { formatter: (value: number) => formatPriceCents(Math.round(value * 100), locale) } },
    grid: { borderColor: 'var(--border, #e5e7eb)' },
}));
</script>

<template>
    <div v-if="history.length >= 2">
        <h2 class="mb-4 text-lg font-medium">{{ t('Historiku i çmimit') }}</h2>
        <VueApexCharts type="area" height="240" :options="options" :series="series" />
    </div>
</template>
