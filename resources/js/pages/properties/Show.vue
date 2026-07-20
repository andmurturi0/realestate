<script setup lang="ts">
import AgentCard from '@/components/site/AgentCard.vue';
import ContactForm from '@/components/site/ContactForm.vue';
import FinancingCalculator from '@/components/site/FinancingCalculator.vue';
import PropertyFeaturesList from '@/components/site/PropertyFeaturesList.vue';
import PropertyGallery from '@/components/site/PropertyGallery.vue';
import PropertyLocationMap from '@/components/site/PropertyLocationMap.vue';
import PropertySpecsGrid from '@/components/site/PropertySpecsGrid.vue';
import SimilarProperties from '@/components/site/SimilarProperties.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { type PropertyDetailData } from '@/lib/detail';
import { type PropertyCardData } from '@/lib/listing';
import { formatPriceCents } from '@/lib/property';
import { useTranslations } from '@/lib/trans';
import { Head } from '@inertiajs/vue3';
import { Eye, MapPin } from 'lucide-vue-next';
import { computed, defineAsyncComponent } from 'vue';

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
</script>

<template>
    <!-- OG/Twitter/JSON-LD live in app.blade.php, not here: this app has no
    Inertia SSR, so <Head> tags only exist post-hydration and crawlers never
    see them. This tag only manages <title>, which Inertia reconciles
    correctly via the `inertia` attribute on the blade template's own tag. -->
    <Head :title="property.title" />

    <PublicLayout>
        <div class="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div class="space-y-8 lg:col-span-2">
                    <PropertyGallery :images="property.images" />

                    <div>
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h1 class="text-2xl font-semibold">{{ property.title }}</h1>
                                <p v-if="locationLabel" class="mt-1 flex items-center gap-1 text-sm text-muted-foreground">
                                    <MapPin class="size-4" />
                                    {{ locationLabel }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-semibold">
                                    {{ formatPriceCents(property.price, locale) }}
                                    <span v-if="property.listing_type === 'rent'" class="text-base font-normal text-muted-foreground">{{
                                        t('/muaj')
                                    }}</span>
                                </p>
                                <span v-if="property.price_negotiable" class="text-xs text-muted-foreground">{{ t('I negociueshëm') }}</span>
                            </div>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                            <span>{{ property.reference_code }}</span>
                            <span v-if="publishedLabel">{{ t('Publikuar më {date}', { date: publishedLabel }) }}</span>
                            <span class="flex items-center gap-1">
                                <Eye class="size-3.5" />
                                {{ t('{count} shikime', { count: property.views_count }) }}
                            </span>
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
                        <h2 class="mb-2 text-lg font-semibold">{{ t('Përshkrimi') }}</h2>
                        <p class="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">{{ property.description }}</p>
                    </div>

                    <PropertyFeaturesList
                        :features="property.features"
                        :has-possession-sheet="property.has_possession_sheet"
                        :document-type="property.document_type"
                    />

                    <PropertyPriceHistoryChart v-if="property.price_history.length >= 2" :history="property.price_history" />

                    <div v-if="property.location">
                        <h2 class="mb-4 text-lg font-semibold">{{ t('Lokacioni') }}</h2>
                        <PropertyLocationMap :lat="property.location.lat" :lng="property.location.lng" />
                    </div>
                </div>

                <div class="space-y-4 lg:sticky lg:top-6 lg:self-start">
                    <AgentCard v-if="property.agent" :agent="property.agent" :reference-code="property.reference_code" :title="property.title" />
                    <FinancingCalculator v-if="property.listing_type === 'sale'" :price="property.price" />
                    <ContactForm :property-slug="property.slug" />
                </div>
            </div>

            <div class="mt-12">
                <SimilarProperties :properties="similarProperties" />
            </div>
        </div>
    </PublicLayout>
</template>
