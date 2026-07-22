<script setup lang="ts">
import { formatPriceCents } from '@/lib/property';
import { useTranslations } from '@/lib/trans';
import { ArrowDown, ArrowUp, Minus } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps<{
    history: { date: string; price: number }[];
}>();

const { t, locale } = useTranslations();

const isDark = ref(false);
let themeObserver: MutationObserver | null = null;

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
    themeObserver = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onBeforeUnmount(() => {
    themeObserver?.disconnect();
});

const direction = computed<'up' | 'down' | 'flat'>(() => {
    const first = props.history[0]?.price ?? 0;
    const last = props.history[props.history.length - 1]?.price ?? 0;

    if (last > first) {
        return 'up';
    }

    if (last < first) {
        return 'down';
    }

    return 'flat';
});

const percentageChange = computed(() => {
    const first = props.history[0]?.price ?? 0;
    const last = props.history[props.history.length - 1]?.price ?? 0;

    if (first === 0) {
        return 0;
    }

    return ((last - first) / first) * 100;
});

const THEME_COLORS = {
    up: '#16a34a',
    down: '#dc2626',
    flat: '#6b7280',
} as const;

const lineColor = computed(() => THEME_COLORS[direction.value]);

const series = computed(() => [
    {
        name: t('Çmimi'),
        data: props.history.map((point) => ({ x: point.date, y: point.price / 100 })),
    },
]);

const options = computed(() => {
    const labelColor = isDark.value ? '#a1a1aa' : '#52525b';
    const gridColor = isDark.value ? '#3f3f46' : '#e4e4e7';

    return {
        chart: { type: 'area', toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
        theme: { mode: isDark.value ? 'dark' : 'light' },
        colors: [lineColor.value],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2, colors: [lineColor.value] },
        markers: {
            size: 4,
            colors: ['#ffffff'],
            strokeColors: lineColor.value,
            strokeWidth: 2,
            hover: { size: 6 },
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.35,
                opacityTo: 0,
                colorStops: [
                    { offset: 0, color: lineColor.value, opacity: 0.35 },
                    { offset: 100, color: lineColor.value, opacity: 0 },
                ],
            },
        },
        xaxis: { type: 'datetime', labels: { style: { colors: labelColor } }, axisBorder: { color: gridColor }, axisTicks: { color: gridColor } },
        yaxis: {
            labels: {
                style: { colors: labelColor },
                formatter: (value: number) => formatPriceCents(Math.round(value * 100), locale),
            },
        },
        tooltip: { theme: isDark.value ? 'dark' : 'light', y: { formatter: (value: number) => formatPriceCents(Math.round(value * 100), locale) } },
        grid: { borderColor: gridColor },
    };
});

const BADGE_CLASSES = {
    up: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    down: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    flat: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
} as const;

const badgeClasses = computed(() => BADGE_CLASSES[direction.value]);
</script>

<template>
    <div v-if="history.length >= 2">
        <div class="mb-4 flex items-center gap-2">
            <h2 class="text-lg font-medium">{{ t('Historiku i çmimit') }}</h2>
            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium" :class="badgeClasses">
                <ArrowUp v-if="direction === 'up'" class="size-3" />
                <ArrowDown v-else-if="direction === 'down'" class="size-3" />
                <Minus v-else class="size-3" />
                {{ Math.abs(percentageChange).toFixed(1) }}%
            </span>
        </div>
        <VueApexCharts type="area" height="240" :options="options" :series="series" />
    </div>
</template>
