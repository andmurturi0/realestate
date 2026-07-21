<script setup lang="ts">
import { useFavourites } from '@/composables/useFavourites';
import { type PropertyCardData } from '@/lib/listing';
import { formatPriceCents } from '@/lib/property';
import { publicRoute } from '@/lib/route';
import { useTranslations } from '@/lib/trans';
import { Link } from '@inertiajs/vue3';
import { Bath, BedDouble, Heart, ImageOff, MapPin, Ruler } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    property: PropertyCardData;
}>();

const { t, locale } = useTranslations();

const intlLocales = { sq: 'sq-AL', en: 'en-IE', de: 'de-DE' } as const;

const favourites = useFavourites();
const isFavourite = computed(() => favourites.has(props.property.id));

const imageSrc = computed(() => props.property.thumbnail_url ?? props.property.image_url);

const locationLabel = computed(() => {
    const parts = [props.property.location, props.property.municipality].filter(Boolean);

    return parts.join(', ');
});

const surfaceLabel = computed(() =>
    props.property.surface_m2 == null ? null : `${new Intl.NumberFormat(intlLocales[locale.value]).format(props.property.surface_m2)} m²`,
);

const listingTypeSuffix = computed(() => (props.property.listing_type === 'sale' ? t('për shitje') : t('me qira')));
</script>

<template>
    <article
        class="group overflow-hidden rounded-xl border bg-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-hover"
    >
        <Link :href="publicRoute('properties.show', property.slug)" class="relative block aspect-[4/3] overflow-hidden bg-muted">
            <img
                v-if="imageSrc"
                :src="imageSrc"
                :alt="property.title"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
            <div v-else class="flex h-full w-full items-center justify-center text-muted-foreground">
                <ImageOff class="size-8" />
            </div>

            <div class="absolute left-3 top-3 flex items-center gap-1.5">
                <span v-if="property.is_exclusive" class="rounded-full bg-amber-500 px-2.5 py-1 text-xs font-medium text-white">
                    {{ t('Ekskluzive') }}
                </span>
                <span
                    class="rounded-full px-2.5 py-1 text-xs font-medium text-white"
                    :class="property.listing_type === 'sale' ? 'bg-green-600' : 'bg-blue-600'"
                >
                    {{ property.listing_type === 'sale' ? t('Në shitje') : t('Me qira') }}
                </span>
            </div>

            <button
                type="button"
                :aria-label="isFavourite ? t('Hiq nga të preferuarat') : t('Shto te të preferuarat')"
                :aria-pressed="isFavourite"
                class="absolute right-3 top-3 rounded-full bg-white/90 p-2 shadow-sm transition-colors dark:bg-gray-900/80"
                :class="isFavourite ? 'text-red-500' : 'text-gray-700 hover:text-red-500 dark:text-gray-200'"
                @click.stop.prevent="favourites.toggle(property.id)"
            >
                <Heart class="size-4" :class="{ 'fill-current': isFavourite }" />
            </button>
        </Link>

        <div class="flex flex-col gap-1.5 p-4">
            <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                <span class="text-xl font-medium">
                    {{ formatPriceCents(property.price, locale)
                    }}<span v-if="property.listing_type === 'rent'" class="text-sm font-normal text-muted-foreground">{{ t('/muaj') }}</span>
                </span>
                <span class="text-sm font-normal text-muted-foreground">{{ listingTypeSuffix }}</span>
                <span
                    v-if="property.price_negotiable"
                    class="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-[11px] font-normal text-muted-foreground"
                >
                    {{ t('I negociueshëm') }}
                </span>
            </div>

            <h3 class="line-clamp-2 font-normal leading-snug text-foreground">
                <Link :href="publicRoute('properties.show', property.slug)" class="hover:underline">{{ property.title }}</Link>
            </h3>

            <p v-if="locationLabel" class="flex items-center gap-1 text-sm text-muted-foreground">
                <MapPin class="size-3.5 shrink-0" />
                {{ locationLabel }}
            </p>

            <div class="mt-1.5 flex items-center justify-between gap-2 border-t pt-2.5 text-sm text-muted-foreground">
                <div class="flex items-center gap-3">
                    <span v-if="property.bedrooms != null" class="flex items-center gap-1" :title="t('Dhoma gjumi')">
                        <BedDouble class="size-4" />
                        {{ property.bedrooms }}
                    </span>
                    <span v-if="property.bathrooms != null" class="flex items-center gap-1" :title="t('Banjo')">
                        <Bath class="size-4" />
                        {{ property.bathrooms }}
                    </span>
                    <span v-if="surfaceLabel" class="flex items-center gap-1" :title="t('Sipërfaqja')">
                        <Ruler class="size-4" />
                        {{ surfaceLabel }}
                    </span>
                </div>
                <span class="shrink-0 text-xs">{{ property.reference_code }}</span>
            </div>
        </div>
    </article>
</template>
