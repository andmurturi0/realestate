<script setup lang="ts">
import AgentCard from '@/components/site/AgentCard.vue';
import ContactForm from '@/components/site/ContactForm.vue';
import FinancingCalculator from '@/components/site/FinancingCalculator.vue';
import PropertyFeaturesList from '@/components/site/PropertyFeaturesList.vue';
import PropertyGallery from '@/components/site/PropertyGallery.vue';
import PropertyLocationMap from '@/components/site/PropertyLocationMap.vue';
import PropertyShareModal from '@/components/site/PropertyShareModal.vue';
import PropertySpecsGrid from '@/components/site/PropertySpecsGrid.vue';
import SimilarProperties from '@/components/site/SimilarProperties.vue';
import { useFavourites } from '@/composables/useFavourites';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { type PropertyDetailData } from '@/lib/detail';
import { type PropertyCardData } from '@/lib/listing';
import { formatPriceCents } from '@/lib/property';
import { publicRoute } from '@/lib/route';
import { useTranslations } from '@/lib/trans';
import { Head } from '@inertiajs/vue3';
import { Eye, Heart, MapPin, MessageCircle, Share2 } from 'lucide-vue-next';
import { computed, defineAsyncComponent, ref } from 'vue';

// ApexCharts is ~500kb — only fetched when a property actually has a
// price-history chart to show, not bundled into every detail-page load.
const PropertyPriceHistoryChart = defineAsyncComponent(() => import('@/components/site/PropertyPriceHistoryChart.vue'));

const props = defineProps<{
    property: PropertyDetailData;
    similarProperties: PropertyCardData[];
}>();

const { t, locale } = useTranslations();

const intlLocales = { sq: 'sq-AL', en: 'en-IE', de: 'de-DE' } as const;

const locationLabel = computed(() => {
    if (!props.property.location) return null;

    const { name, municipality } = props.property.location;

    return name === municipality ? name : `${name}, ${municipality}`;
});

const publishedLabel = computed(() =>
    props.property.published_at
        ? new Intl.DateTimeFormat(intlLocales[locale.value], { day: 'numeric', month: 'long', year: 'numeric' }).format(
              new Date(props.property.published_at),
          )
        : null,
);

const listingTypeSuffix = computed(() => (props.property.listing_type === 'sale' ? t('për shitje') : t('me qira')));

const contactFormEl = ref<HTMLElement | null>(null);

function scrollToContactForm() {
    contactFormEl.value?.scrollIntoView({ behavior: 'smooth' });
}

const favourites = useFavourites();
const isFavourite = computed(() => favourites.has(props.property.id));

const shareModalOpen = ref(false);
const shareUrl = computed(() => publicRoute('properties.show', props.property.slug));
const shareImage = computed(() => props.property.images.find((image) => image.is_primary)?.url ?? props.property.images[0]?.url ?? null);
</script>

<template>
    <!-- OG/Twitter/JSON-LD live in app.blade.php, not here: this app has no
    Inertia SSR, so <Head> tags only exist post-hydration and crawlers never
    see them. This tag only manages <title>, which Inertia reconciles
    correctly via the `inertia` attribute on the blade template's own tag. -->
    <Head :title="property.title" />

    <PublicLayout show-brand-pattern>
        <div class="relative z-10 mx-auto w-full max-w-screen-2xl flex-1 px-4 py-6 pt-10 md:pt-14">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div class="space-y-8 lg:col-span-2">
                    <PropertyGallery :images="property.images" />

                    <div>
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <div class="mb-2 flex items-center gap-1.5">
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
                                <h1 class="text-2xl font-medium">{{ property.title }}</h1>
                                <p v-if="locationLabel" class="mt-1 flex items-center gap-1 text-sm text-muted-foreground">
                                    <MapPin class="size-4" />
                                    {{ locationLabel }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-baseline justify-end gap-x-2 gap-y-1">
                                <span class="text-2xl font-medium">
                                    {{ formatPriceCents(property.price, locale)
                                    }}<span v-if="property.listing_type === 'rent'" class="text-sm font-normal text-muted-foreground">{{
                                        t('/muaj')
                                    }}</span>
                                </span>
                                <span class="text-sm font-normal text-muted-foreground">{{ listingTypeSuffix }}</span>
                                <span
                                    v-if="property.price_negotiable"
                                    class="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-[11px] font-normal text-muted-foreground"
                                >
                                    {{ t('I negociueshëm') }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                <span>{{ property.reference_code }}</span>
                                <span v-if="publishedLabel">{{ t('Publikuar më {date}', { date: publishedLabel }) }}</span>
                                <span class="flex items-center gap-1">
                                    <Eye class="size-3.5" />
                                    {{ t('{count} shikime', { count: property.views_count }) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    :aria-label="isFavourite ? t('Hiq nga të preferuarat') : t('Shto te të preferuarat')"
                                    :aria-pressed="isFavourite"
                                    class="flex items-center justify-center gap-1.5 rounded-full border p-2 text-sm transition-colors sm:px-3.5"
                                    :class="
                                        isFavourite
                                            ? 'border-red-200 bg-red-50 text-red-500 dark:border-red-900/40 dark:bg-red-950/30'
                                            : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                                    "
                                    @click="favourites.toggle(property.id)"
                                >
                                    <Heart class="size-4" :class="{ 'fill-current': isFavourite }" />
                                    <span class="hidden sm:inline">{{ t('Ruaj') }}</span>
                                </button>

                                <button
                                    type="button"
                                    :aria-label="t('Ndaje')"
                                    class="flex items-center justify-center gap-1.5 rounded-full border p-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground sm:px-3.5"
                                    @click="shareModalOpen = true"
                                >
                                    <Share2 class="size-4" />
                                    <span class="hidden sm:inline">{{ t('Ndaje') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <PropertySpecsGrid
                        :category="property.category"
                        :surface-m2="property.surface_m2"
                        :land-surface-m2="property.land_surface_m2"
                        :bedrooms="property.bedrooms"
                        :bathrooms="property.bathrooms"
                        :floor="property.floor"
                        :total-floors="property.total_floors"
                        :year-built="property.year_built"
                        :parking-spaces="property.parking_spaces"
                    />

                    <div v-if="property.description">
                        <h2 class="mb-2 text-lg font-medium">{{ t('Përshkrimi') }}</h2>
                        <p class="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">{{ property.description }}</p>
                    </div>

                    <PropertyFeaturesList
                        :features="property.features"
                        :has-possession-sheet="property.has_possession_sheet"
                        :document-type="property.document_type"
                    />

                    <PropertyPriceHistoryChart v-if="property.price_history.length >= 2" :history="property.price_history" />

                    <div v-if="property.location">
                        <h2 class="mb-4 text-lg font-medium">{{ t('Lokacioni') }}</h2>
                        <PropertyLocationMap :lat="property.location.lat" :lng="property.location.lng" />
                    </div>
                </div>

                <div class="space-y-4 lg:sticky lg:top-6 lg:self-start">
                    <AgentCard v-if="property.agent" :agent="property.agent" :reference-code="property.reference_code" :title="property.title" />
                    <FinancingCalculator v-if="property.listing_type === 'sale'" :price="property.price" />
                    <div ref="contactFormEl">
                        <ContactForm :property-slug="property.slug" />
                    </div>
                </div>
            </div>

            <div class="mt-12">
                <SimilarProperties :properties="similarProperties" />
            </div>
        </div>

        <button
            type="button"
            :aria-label="t('Kontakto për këtë pronë')"
            class="fixed z-30 flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-hover transition hover:opacity-90 lg:hidden"
            style="bottom: calc(1rem + env(safe-area-inset-bottom)); left: calc(1rem + env(safe-area-inset-left))"
            @click="scrollToContactForm"
        >
            <MessageCircle class="size-6" />
        </button>

        <PropertyShareModal v-model="shareModalOpen" :title="property.title" :image-url="shareImage" :url="shareUrl" />
    </PublicLayout>
</template>
