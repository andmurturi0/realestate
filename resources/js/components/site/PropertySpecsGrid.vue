<script setup lang="ts">
import { categoryDetailFields, type DetailField, type PropertyCategory } from '@/lib/property';
import { useTranslations } from '@/lib/trans';
import { Bath, BedDouble, Building2, Calendar, Car, Layers, Ruler, Trees } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    category: string;
    surfaceM2: number | null;
    landSurfaceM2: number | null;
    bedrooms: number | null;
    bathrooms: number | null;
    floor: number | null;
    totalFloors: number | null;
    yearBuilt: number | null;
    parkingSpaces: number | null;
}>();

const { t, locale } = useTranslations();

const intlLocales = { sq: 'sq-AL', en: 'en-IE', de: 'de-DE' } as const;

interface Spec {
    icon: typeof Ruler;
    label: string;
    value: string;
}

// Computed, not a plain const: switching locale is a client-side Inertia
// visit that reuses this component instance, so t() calls baked into a
// plain object at setup time would freeze at whatever locale first mounted it.
const fieldMeta = computed<Record<DetailField, { icon: typeof Ruler; label: string; format: (v: number) => string }>>(() => ({
    land_surface_m2: {
        icon: Trees,
        label: t('Sipërfaqja e tokës'),
        format: (v) => `${new Intl.NumberFormat(intlLocales[locale.value]).format(v)} m²`,
    },
    bedrooms: { icon: BedDouble, label: t('Dhoma gjumi'), format: (v) => String(v) },
    bathrooms: { icon: Bath, label: t('Banjo'), format: (v) => String(v) },
    floor: { icon: Layers, label: t('Kati'), format: (v) => String(v) },
    total_floors: { icon: Building2, label: t('Katet gjithsej'), format: (v) => String(v) },
    year_built: { icon: Calendar, label: t('Viti i ndërtimit'), format: (v) => String(v) },
    parking_spaces: { icon: Car, label: t('Vende parkimi'), format: (v) => String(v) },
}));

const valuesByField: Record<DetailField, number | null> = {
    land_surface_m2: null,
    bedrooms: null,
    bathrooms: null,
    floor: null,
    total_floors: null,
    year_built: null,
    parking_spaces: null,
};

const specs = computed<Spec[]>(() => {
    const allowed = categoryDetailFields[props.category as PropertyCategory] ?? [];
    const source: Record<DetailField, number | null> = {
        ...valuesByField,
        land_surface_m2: props.landSurfaceM2,
        bedrooms: props.bedrooms,
        bathrooms: props.bathrooms,
        floor: props.floor,
        total_floors: props.totalFloors,
        year_built: props.yearBuilt,
        parking_spaces: props.parkingSpaces,
    };

    const result: Spec[] = [];

    if (props.surfaceM2 != null) {
        result.push({
            icon: Ruler,
            label: t('Sipërfaqja'),
            value: `${new Intl.NumberFormat(intlLocales[locale.value]).format(props.surfaceM2)} m²`,
        });
    }

    for (const field of allowed) {
        const value = source[field];

        if (value != null) {
            result.push({ icon: fieldMeta.value[field].icon, label: fieldMeta.value[field].label, value: fieldMeta.value[field].format(value) });
        }
    }

    return result;
});
</script>

<template>
    <div v-if="specs.length" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
        <div v-for="spec in specs" :key="spec.label" class="flex items-center gap-2 rounded-lg border p-3">
            <component :is="spec.icon" class="size-5 shrink-0 text-muted-foreground" />
            <div class="min-w-0">
                <p class="truncate text-sm font-medium">{{ spec.value }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ spec.label }}</p>
            </div>
        </div>
    </div>
</template>
