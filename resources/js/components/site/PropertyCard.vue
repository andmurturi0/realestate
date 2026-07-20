<script setup lang="ts">
import { type PropertyCardData } from '@/lib/listing';
import { formatPriceCents } from '@/lib/property';
import { Bath, BedDouble, Heart, ImageOff, MapPin, Ruler } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    property: PropertyCardData;
}>();

const imageSrc = computed(() => props.property.thumbnail_url ?? props.property.image_url);

const locationLabel = computed(() => {
    const parts = [props.property.location, props.property.municipality].filter(Boolean);

    return parts.join(', ');
});

const surfaceLabel = computed(() =>
    props.property.surface_m2 == null ? null : `${new Intl.NumberFormat('sq-AL').format(props.property.surface_m2)} m²`,
);
</script>

<!-- The card links to the detail page starting with Faza 6 — until then it is display-only. -->
<template>
    <article class="group overflow-hidden rounded-xl border bg-card shadow-sm transition-shadow hover:shadow-md">
        <div class="relative aspect-[4/3] overflow-hidden bg-muted">
            <img
                v-if="imageSrc"
                :src="imageSrc"
                :alt="property.title"
                loading="lazy"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
            <div v-else class="flex h-full w-full items-center justify-center text-muted-foreground">
                <ImageOff class="size-8" />
            </div>

            <span
                v-if="property.is_exclusive"
                class="absolute left-3 top-3 rounded-md bg-amber-500 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-white"
            >
                Ekskluzive
            </span>
            <span
                class="absolute right-3 top-3 rounded-md px-2 py-0.5 text-xs font-semibold text-white"
                :class="property.listing_type === 'sale' ? 'bg-primary' : 'bg-sky-600'"
            >
                {{ property.listing_type === 'sale' ? 'Në shitje' : 'Me qira' }}
            </span>

            <!-- Favourite heart: non-functional placeholder — favourites land in Faza 5, pjesa 2. -->
            <button
                type="button"
                aria-label="Shto te të preferuarat"
                class="absolute bottom-3 right-3 rounded-full bg-white/90 p-2 text-gray-700 shadow-sm transition-colors hover:text-red-500 dark:bg-gray-900/80 dark:text-gray-200"
            >
                <Heart class="size-4" />
            </button>
        </div>

        <div class="flex flex-col gap-1.5 p-4">
            <div class="flex items-baseline justify-between gap-2">
                <p class="text-lg font-semibold">
                    {{ formatPriceCents(property.price) }}
                    <span v-if="property.listing_type === 'rent'" class="text-sm font-normal text-muted-foreground">/muaj</span>
                </p>
                <span v-if="property.price_negotiable" class="shrink-0 text-xs text-muted-foreground">I negociueshëm</span>
            </div>

            <h3 class="line-clamp-2 font-medium leading-snug">{{ property.title }}</h3>

            <p v-if="locationLabel" class="flex items-center gap-1 text-sm text-muted-foreground">
                <MapPin class="size-3.5 shrink-0" />
                {{ locationLabel }}
            </p>

            <div class="mt-2 flex items-center justify-between gap-2 border-t pt-3 text-sm text-muted-foreground">
                <div class="flex items-center gap-3">
                    <span v-if="property.bedrooms != null" class="flex items-center gap-1" title="Dhoma gjumi">
                        <BedDouble class="size-4" />
                        {{ property.bedrooms }}
                    </span>
                    <span v-if="property.bathrooms != null" class="flex items-center gap-1" title="Banjo">
                        <Bath class="size-4" />
                        {{ property.bathrooms }}
                    </span>
                    <span v-if="surfaceLabel" class="flex items-center gap-1" title="Sipërfaqja">
                        <Ruler class="size-4" />
                        {{ surfaceLabel }}
                    </span>
                </div>
                <span class="shrink-0 text-xs">{{ property.reference_code }}</span>
            </div>
        </div>
    </article>
</template>
