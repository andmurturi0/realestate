<script setup lang="ts">
import FilterBar from '@/components/site/FilterBar.vue';
import PaginationLinks from '@/components/site/PaginationLinks.vue';
import PropertyCard from '@/components/site/PropertyCard.vue';
import PropertyCardSkeleton from '@/components/site/PropertyCardSkeleton.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import {
    emptyFilters,
    filtersFromProps,
    filtersToQuery,
    sortLabels,
    type FurnishingOption,
    type LocationOption,
    type Paginated,
    type PropertyCardData,
} from '@/lib/listing';
import { publicRoute } from '@/lib/route';
import { useTranslations } from '@/lib/trans';
import { Head, router } from '@inertiajs/vue3';
import { SearchX } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const { t } = useTranslations();

const props = defineProps<{
    properties: Paginated<PropertyCardData>;
    filters: Record<string, unknown> | unknown[];
    locations: LocationOption[];
    furnishingOptions: FurnishingOption[];
}>();

// Filter state lives in the URL; this is its client-side mirror. The server
// echoes the normalized state back via the `filters` prop.
const filters = ref(filtersFromProps(props.filters));
const loading = ref(false);

const visitOptions = {
    only: ['properties', 'filters'],
    preserveState: true,
    onStart: () => (loading.value = true),
    onFinish: () => (loading.value = false),
};

/** Filter changes always restart from page 1 — the page param is simply not sent. */
function applyFilters() {
    router.get(publicRoute('properties.index'), filtersToQuery(filters.value), {
        ...visitOptions,
        preserveScroll: true,
    });
}

let debounceTimeout: ReturnType<typeof setTimeout> | undefined;

function applyFiltersDebounced() {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(applyFilters, 300);
}

function clearFilters() {
    filters.value = emptyFilters();
    applyFilters();
}

/** Pagination links carry the full query string, so filters survive page changes. */
function goToPage(url: string) {
    router.visit(url, visitOptions);
}

// Back/forward restores historical props — mirror them into the local state
// without firing a new request. Normal filtering echoes back an identical
// state, so this only assigns when the URL actually changed underneath us.
watch(
    () => props.filters,
    (incoming) => {
        const next = filtersFromProps(incoming);

        if (JSON.stringify(next) !== JSON.stringify(filters.value)) {
            filters.value = next;
        }
    },
);

const resultCountLabel = computed(() => (props.properties.total === 1 ? t('1 pronë') : t('{count} prona', { count: props.properties.total })));

const hasActiveFilters = computed(() => Object.keys(filtersToQuery(filters.value)).length > 0);

const skeletonCount = computed(() => Math.min(Math.max(props.properties.data.length, 6), 12));
</script>

<template>
    <Head :title="t('Pronat')" />

    <PublicLayout show-brand-pattern>
        <div class="relative z-10 mx-auto w-full max-w-screen-2xl flex-1 px-4 py-8 pt-10 md:py-12 md:pt-14">
            <h1 class="mb-6 text-display-lg font-medium">{{ t('Pronat') }}</h1>

            <FilterBar
                v-model:filters="filters"
                :locations="locations"
                :furnishing-options="furnishingOptions"
                @apply="applyFilters"
                @apply-debounced="applyFiltersDebounced"
                @clear="clearFilters"
            />

            <div class="mt-6 flex items-center justify-between gap-3">
                <p class="text-sm text-muted-foreground" aria-live="polite">{{ resultCountLabel }}</p>
                <label class="flex items-center gap-2 text-sm text-muted-foreground">
                    {{ t('Rendit:') }}
                    <select
                        v-model="filters.sort"
                        class="h-9 rounded-md border border-input bg-background px-2 text-sm text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        @change="applyFilters"
                    >
                        <option v-for="(label, value) in sortLabels" :key="value" :value="value">{{ t(label) }}</option>
                    </select>
                </label>
            </div>

            <!-- Loading: skeleton cards, never a spinner -->
            <div v-if="loading" class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-5 xl:grid-cols-4">
                <PropertyCardSkeleton v-for="index in skeletonCount" :key="index" />
            </div>

            <div v-else-if="properties.data.length" class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-5 xl:grid-cols-4">
                <PropertyCard v-for="property in properties.data" :key="property.id" :property="property" />
            </div>

            <!-- Empty state -->
            <div v-else class="mt-6 flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-24 text-center">
                <div class="rounded-full bg-muted p-4">
                    <SearchX class="size-8 text-muted-foreground" />
                </div>
                <h2 class="text-lg font-medium">{{ t('Asnjë pronë nuk u gjet') }}</h2>
                <p class="max-w-sm text-sm text-muted-foreground">
                    {{ t("Nuk ka prona që përputhen me filtrat e zgjedhur. Provoni t'i zgjeroni kriteret e kërkimit.") }}
                </p>
                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="mt-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90"
                    @click="clearFilters"
                >
                    {{ t('Pastro filtrat') }}
                </button>
            </div>

            <div class="mt-10">
                <PaginationLinks :links="properties.links" @navigate="goToPage" />
            </div>
        </div>
    </PublicLayout>
</template>
