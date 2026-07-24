<script setup lang="ts">
import LocationSelect from '@/components/site/LocationSelect.vue';
import { type FurnishingOption, type ListingFilters, type LocationOption } from '@/lib/listing';
import { categoryLabels, type PropertyCategory } from '@/lib/property';
import { useTranslations } from '@/lib/trans';
import {
    Briefcase,
    Building,
    Building2,
    ChevronDown,
    Home,
    LandPlot,
    Search,
    SlidersHorizontal,
    Store,
    Warehouse,
    X,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    locations: LocationOption[];
    furnishingOptions: FurnishingOption[];
    /** Live count of properties matching the current filter state, for the mobile sheet's "Shfaq X prona" button. */
    resultCount: number;
}>();

const { t } = useTranslations();

// The page's filter state — mutated in place here, applied by the parent.
const filters = defineModel<ListingFilters>('filters', { required: true });

const emit = defineEmits<{
    (e: 'apply'): void;
    (e: 'apply-debounced'): void;
    (e: 'clear'): void;
}>();

const categoryIcons: Record<PropertyCategory, LucideIcon> = {
    house: Home,
    apartment: Building2,
    office: Briefcase,
    store: Store,
    land: LandPlot,
    warehouse: Warehouse,
    object: Building,
};

const bedroomOptions = [1, 2, 3, 4, 5] as const;

const advancedCount = computed(
    () =>
        (filters.value.possession ? 1 : 0) +
        (filters.value.documents ? 1 : 0) +
        (filters.value.bedrooms != null ? 1 : 0) +
        filters.value.furnishing.length,
);

const showMore = ref(advancedCount.value > 0);

// ---- Mobile filter sheet --------------------------------------------------

const mobileFiltersOpen = ref(false);

/** Filters hidden inside the mobile sheet — type/exclusive stay visible in the bar, so they're excluded here. */
const sheetFilterCount = computed(
    () =>
        filters.value.category.length +
        (filters.value.price_min !== '' || filters.value.price_max !== '' ? 1 : 0) +
        (filters.value.surface_min !== '' || filters.value.surface_max !== '' ? 1 : 0) +
        (filters.value.location != null ? 1 : 0) +
        advancedCount.value,
);

const showResultsLabel = computed(() => (props.resultCount === 1 ? t('Shfaq 1 pronë') : t('Shfaq {count} prona', { count: props.resultCount })));

function applyAndCloseMobile() {
    emit('apply');
    mobileFiltersOpen.value = false;
}

function clearAndCloseMobile() {
    emit('clear');
    mobileFiltersOpen.value = false;
}

function onMobileSheetKeydown(event: KeyboardEvent) {
    if (mobileFiltersOpen.value && event.key === 'Escape') {
        mobileFiltersOpen.value = false;
    }
}

onMounted(() => window.addEventListener('keydown', onMobileSheetKeydown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onMobileSheetKeydown);
    document.body.style.overflow = '';
});

watch(mobileFiltersOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

function toggleType(type: 'sale' | 'rent') {
    filters.value.type = filters.value.type === type ? '' : type;
    emit('apply');
}

function toggleExclusive() {
    filters.value.exclusive = !filters.value.exclusive;
    emit('apply');
}

function toggleCategory(category: string) {
    const index = filters.value.category.indexOf(category);

    if (index === -1) {
        filters.value.category.push(category);
    } else {
        filters.value.category.splice(index, 1);
    }

    emit('apply');
}

function setBedrooms(value: number | null) {
    filters.value.bedrooms = value;
    emit('apply');
}

function setPossession(value: '' | 'with' | 'without') {
    filters.value.possession = value;
    emit('apply');
}

function setDocuments(value: '' | 'notary' | 'lawyer') {
    filters.value.documents = value;
    emit('apply');
}

function toggleFurnishing(key: string) {
    const index = filters.value.furnishing.indexOf(key);

    if (index === -1) {
        filters.value.furnishing.push(key);
    } else {
        filters.value.furnishing.splice(index, 1);
    }

    emit('apply');
}

function setLocation(value: number | null) {
    filters.value.location = value;
    emit('apply');
}

function clearSearch() {
    filters.value.q = '';
    emit('apply');
}

// ---- Active filter chips -----------------------------------------------

interface ActiveChip {
    id: string;
    label: string;
    remove: () => void;
}

const activeChips = computed<ActiveChip[]>(() => {
    const state = filters.value;
    const chips: ActiveChip[] = [];
    const clearFields = (fn: () => void) => () => {
        fn();
        emit('apply');
    };

    if (state.type) {
        chips.push({
            id: 'type',
            label: state.type === 'sale' ? t('Për shitje') : t('Me qira'),
            remove: clearFields(() => (state.type = '')),
        });
    }

    if (state.exclusive) {
        chips.push({ id: 'exclusive', label: t('Ekskluzive'), remove: clearFields(() => (state.exclusive = false)) });
    }

    if (state.q) {
        chips.push({ id: 'q', label: t('Kërkim: {query}', { query: state.q }), remove: clearFields(() => (state.q = '')) });
    }

    for (const category of state.category) {
        chips.push({
            id: `category-${category}`,
            label: t(categoryLabels[category as PropertyCategory] ?? category),
            remove: () => toggleCategory(category),
        });
    }

    if (state.price_min !== '' || state.price_max !== '') {
        chips.push({
            id: 'price',
            label: t('Çmimi: {min} € – {max} €', { min: state.price_min || '0', max: state.price_max || '∞' }),
            remove: clearFields(() => {
                state.price_min = '';
                state.price_max = '';
            }),
        });
    }

    if (state.surface_min !== '' || state.surface_max !== '') {
        chips.push({
            id: 'surface',
            label: t('Sipërfaqja: {min} – {max} m²', { min: state.surface_min || '0', max: state.surface_max || '∞' }),
            remove: clearFields(() => {
                state.surface_min = '';
                state.surface_max = '';
            }),
        });
    }

    if (state.location != null) {
        chips.push({ id: 'location', label: locationName(state.location), remove: () => setLocation(null) });
    }

    if (state.bedrooms != null) {
        chips.push({
            id: 'bedrooms',
            label: state.bedrooms >= 5 ? t('5+ dhoma') : t('{count} dhoma', { count: state.bedrooms }),
            remove: () => setBedrooms(null),
        });
    }

    if (state.possession) {
        chips.push({
            id: 'possession',
            label: state.possession === 'with' ? t('Me fletë poseduese') : t('Pa fletë poseduese'),
            remove: () => setPossession(''),
        });
    }

    if (state.documents) {
        chips.push({
            id: 'documents',
            label: state.documents === 'notary' ? t('Dokumentet: Noter') : t('Dokumentet: Avokat'),
            remove: () => setDocuments(''),
        });
    }

    for (const key of state.furnishing) {
        chips.push({
            id: `furnishing-${key}`,
            label: props.furnishingOptions.find((option) => option.key === key)?.name ?? key,
            remove: () => toggleFurnishing(key),
        });
    }

    return chips;
});

function locationName(id: number): string {
    for (const municipality of props.locations) {
        if (municipality.id === id) return municipality.name;

        const neighborhood = municipality.neighborhoods.find((n) => n.id === id);
        if (neighborhood) return neighborhood.name;
    }

    return t('Lokacioni');
}

// ---- Shared classes ------------------------------------------------------

const chipClass = (active: boolean) =>
    active
        ? 'border-primary bg-primary text-primary-foreground'
        : 'border-input bg-background text-foreground hover:bg-accent hover:text-accent-foreground';

const segmentClass = (active: boolean) => (active ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground');

const rangeInputClass =
    'h-10 w-full rounded-md border border-input bg-background px-3 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
</script>

<template>
    <div class="flex flex-col gap-3 rounded-xl border bg-card p-4">
        <!-- Search: always visible, above everything else on desktop and inside the mobile bar's visible row. -->
        <div class="relative">
            <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <input
                v-model="filters.q"
                type="search"
                :placeholder="t('Kërko sipas titullit, referencës, lokacionit…')"
                class="h-10 w-full rounded-md border border-input bg-background pl-9 pr-9 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                @input="emit('apply-debounced')"
            />
            <button
                v-if="filters.q"
                type="button"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-full p-0.5 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                :aria-label="t('Pastro kërkimin')"
                @click="clearSearch"
            >
                <X class="size-4" />
            </button>
        </div>

        <!-- Row 1: listing type + exclusive -->
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex rounded-lg border bg-muted/40 p-1">
                <button
                    type="button"
                    class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors"
                    :class="segmentClass(filters.type === 'sale')"
                    @click="toggleType('sale')"
                >
                    {{ t('Për shitje') }}
                </button>
                <button
                    type="button"
                    class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors"
                    :class="segmentClass(filters.type === 'rent')"
                    @click="toggleType('rent')"
                >
                    {{ t('Me qira') }}
                </button>
            </div>
            <button
                type="button"
                class="rounded-lg border px-4 py-2 text-sm font-medium transition-colors"
                :class="chipClass(filters.exclusive)"
                :aria-pressed="filters.exclusive"
                @click="toggleExclusive"
            >
                {{ t('Ekskluzive') }}
            </button>

            <!-- Mobile/tablet: opens the filter sheet; hidden at the lg breakpoint where everything is inline. -->
            <button
                type="button"
                class="relative ml-auto inline-flex h-9 items-center gap-1.5 rounded-lg border px-4 text-sm font-medium transition-colors lg:hidden"
                :class="chipClass(sheetFilterCount > 0)"
                :aria-expanded="mobileFiltersOpen"
                @click="mobileFiltersOpen = true"
            >
                <SlidersHorizontal class="size-4" />
                {{ t('Filtra') }}
                <span v-if="sheetFilterCount" class="rounded-full bg-primary-foreground/20 px-1.5 text-xs">
                    {{ sheetFilterCount }}
                </span>
            </button>
        </div>

        <!-- Everything below is desktop/tablet-inline only (>= lg); on mobile it lives inside the sheet below. -->
        <div class="hidden lg:flex lg:flex-col lg:gap-3">
            <!-- Row 2: category chips -->
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="(label, category) in categoryLabels"
                    :key="category"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm transition-colors"
                    :class="chipClass(filters.category.includes(category))"
                    :aria-pressed="filters.category.includes(category)"
                    @click="toggleCategory(category)"
                >
                    <component :is="categoryIcons[category]" class="size-4" />
                    {{ t(label) }}
                </button>
            </div>

            <!-- Row 3: ranges, location, more -->
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto]">
                <div class="flex items-center gap-1.5">
                    <input
                        v-model="filters.price_min"
                        type="number"
                        min="0"
                        inputmode="numeric"
                        :placeholder="t('Çmimi min €')"
                        :class="rangeInputClass"
                        @input="emit('apply-debounced')"
                    />
                    <span class="text-muted-foreground">–</span>
                    <input
                        v-model="filters.price_max"
                        type="number"
                        min="0"
                        inputmode="numeric"
                        :placeholder="t('Çmimi max €')"
                        :class="rangeInputClass"
                        @input="emit('apply-debounced')"
                    />
                </div>

                <div class="flex items-center gap-1.5">
                    <input
                        v-model="filters.surface_min"
                        type="number"
                        min="0"
                        inputmode="numeric"
                        :placeholder="t('Sipërfaqja min m²')"
                        :class="rangeInputClass"
                        @input="emit('apply-debounced')"
                    />
                    <span class="text-muted-foreground">–</span>
                    <input
                        v-model="filters.surface_max"
                        type="number"
                        min="0"
                        inputmode="numeric"
                        :placeholder="t('Sipërfaqja max m²')"
                        :class="rangeInputClass"
                        @input="emit('apply-debounced')"
                    />
                </div>

                <LocationSelect :model-value="filters.location" :locations="locations" @update:model-value="setLocation" />

                <button
                    type="button"
                    class="inline-flex h-10 items-center justify-center gap-1.5 rounded-md border px-4 text-sm font-medium transition-colors"
                    :class="chipClass(showMore)"
                    :aria-expanded="showMore"
                    @click="showMore = !showMore"
                >
                    <SlidersHorizontal class="size-4" />
                    {{ t('Më shumë') }}
                    <span
                        v-if="advancedCount"
                        class="rounded-full px-1.5 text-xs"
                        :class="showMore ? 'bg-primary-foreground/20' : 'bg-primary text-primary-foreground'"
                    >
                        {{ advancedCount }}
                    </span>
                    <ChevronDown class="size-4 transition-transform" :class="showMore ? 'rotate-180' : ''" />
                </button>
            </div>

            <!-- "Më shumë" panel -->
            <div v-show="showMore" class="grid grid-cols-1 gap-4 border-t pt-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="flex flex-col gap-1.5">
                    <span class="text-xs font-medium uppercase text-muted-foreground">{{ t('Fleta poseduese') }}</span>
                    <div class="inline-flex w-fit rounded-lg border bg-muted/40 p-1">
                        <button
                            v-for="option in [
                                { value: '', label: 'Të gjitha' },
                                { value: 'with', label: 'Me' },
                                { value: 'without', label: 'Pa' },
                            ] as const"
                            :key="option.value"
                            type="button"
                            class="rounded-md px-3 py-1 text-sm transition-colors"
                            :class="segmentClass(filters.possession === option.value)"
                            @click="setPossession(option.value)"
                        >
                            {{ t(option.label) }}
                        </button>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <span class="text-xs font-medium uppercase text-muted-foreground">{{ t('Dokumentet') }}</span>
                    <div class="inline-flex w-fit rounded-lg border bg-muted/40 p-1">
                        <button
                            v-for="option in [
                                { value: '', label: 'Të gjitha' },
                                { value: 'notary', label: 'Noter' },
                                { value: 'lawyer', label: 'Avokat' },
                            ] as const"
                            :key="option.value"
                            type="button"
                            class="rounded-md px-3 py-1 text-sm transition-colors"
                            :class="segmentClass(filters.documents === option.value)"
                            @click="setDocuments(option.value)"
                        >
                            {{ t(option.label) }}
                        </button>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <span class="text-xs font-medium uppercase text-muted-foreground">{{ t('Dhomat') }}</span>
                    <div class="inline-flex w-fit rounded-lg border bg-muted/40 p-1">
                        <button
                            type="button"
                            class="rounded-md px-3 py-1 text-sm transition-colors"
                            :class="segmentClass(filters.bedrooms == null)"
                            @click="setBedrooms(null)"
                        >
                            {{ t('Të gjitha') }}
                        </button>
                        <button
                            v-for="count in bedroomOptions"
                            :key="count"
                            type="button"
                            class="rounded-md px-3 py-1 text-sm transition-colors"
                            :class="segmentClass(filters.bedrooms === count)"
                            @click="setBedrooms(count)"
                        >
                            {{ count === 5 ? '5+' : count }}
                        </button>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <span class="text-xs font-medium uppercase text-muted-foreground">{{ t('Mobilimi') }}</span>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="option in furnishingOptions"
                            :key="option.key"
                            type="button"
                            class="rounded-full border px-3 py-1 text-sm transition-colors"
                            :class="chipClass(filters.furnishing.includes(option.key))"
                            :aria-pressed="filters.furnishing.includes(option.key)"
                            @click="toggleFurnishing(option.key)"
                        >
                            {{ option.name }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Active filters as removable chips -->
            <div v-if="activeChips.length" class="flex flex-wrap items-center gap-1.5 border-t pt-3">
                <button
                    v-for="chip in activeChips"
                    :key="chip.id"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full bg-secondary px-2.5 py-1 text-xs text-secondary-foreground transition-colors hover:bg-secondary/70"
                    :aria-label="t('Hiq filtrin: {label}', { label: chip.label })"
                    @click="chip.remove"
                >
                    {{ chip.label }}
                    <X class="size-3" />
                </button>
                <button type="button" class="ml-1 text-xs font-medium text-muted-foreground underline hover:text-foreground" @click="emit('clear')">
                    {{ t('Pastro të gjitha') }}
                </button>
            </div>
        </div>

        <!-- Mobile filter sheet: same `filters` model and handlers as above, just presented as a bottom sheet. -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-200"
                leave-to-class="opacity-0"
            >
                <div v-if="mobileFiltersOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="mobileFiltersOpen = false" />
            </Transition>
            <Transition
                enter-active-class="transition-transform duration-300 ease-out"
                enter-from-class="translate-y-full"
                leave-active-class="transition-transform duration-200 ease-in"
                leave-to-class="translate-y-full"
            >
                <div
                    v-if="mobileFiltersOpen"
                    class="fixed inset-x-0 bottom-0 z-50 flex max-h-[85vh] flex-col rounded-t-2xl border-t bg-card shadow-2xl lg:hidden"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="t('Filtra')"
                >
                    <div class="flex items-center justify-between border-b p-4">
                        <h2 class="font-semibold text-base">{{ t('Filtra') }}</h2>
                        <button
                            type="button"
                            class="rounded-full p-1.5 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                            :aria-label="t('Mbyll')"
                            @click="mobileFiltersOpen = false"
                        >
                            <X class="size-5" />
                        </button>
                    </div>

                    <div class="flex flex-col gap-4 overflow-y-auto p-4">
                        <!-- Category chips -->
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="(label, category) in categoryLabels"
                                :key="category"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm transition-colors"
                                :class="chipClass(filters.category.includes(category))"
                                :aria-pressed="filters.category.includes(category)"
                                @click="toggleCategory(category)"
                            >
                                <component :is="categoryIcons[category]" class="size-4" />
                                {{ t(label) }}
                            </button>
                        </div>

                        <!-- Ranges + location -->
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-1.5">
                                <input
                                    v-model="filters.price_min"
                                    type="number"
                                    min="0"
                                    inputmode="numeric"
                                    :placeholder="t('Çmimi min €')"
                                    :class="rangeInputClass"
                                    @input="emit('apply-debounced')"
                                />
                                <span class="text-muted-foreground">–</span>
                                <input
                                    v-model="filters.price_max"
                                    type="number"
                                    min="0"
                                    inputmode="numeric"
                                    :placeholder="t('Çmimi max €')"
                                    :class="rangeInputClass"
                                    @input="emit('apply-debounced')"
                                />
                            </div>

                            <div class="flex items-center gap-1.5">
                                <input
                                    v-model="filters.surface_min"
                                    type="number"
                                    min="0"
                                    inputmode="numeric"
                                    :placeholder="t('Sipërfaqja min m²')"
                                    :class="rangeInputClass"
                                    @input="emit('apply-debounced')"
                                />
                                <span class="text-muted-foreground">–</span>
                                <input
                                    v-model="filters.surface_max"
                                    type="number"
                                    min="0"
                                    inputmode="numeric"
                                    :placeholder="t('Sipërfaqja max m²')"
                                    :class="rangeInputClass"
                                    @input="emit('apply-debounced')"
                                />
                            </div>

                            <LocationSelect :model-value="filters.location" :locations="locations" @update:model-value="setLocation" />
                        </div>

                        <!-- Më shumë panel: always expanded inside the sheet, no separate toggle needed here. -->
                        <div class="grid grid-cols-1 gap-4 border-t pt-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-1.5">
                                <span class="text-xs font-medium uppercase text-muted-foreground">{{ t('Fleta poseduese') }}</span>
                                <div class="inline-flex w-fit rounded-lg border bg-muted/40 p-1">
                                    <button
                                        v-for="option in [
                                            { value: '', label: 'Të gjitha' },
                                            { value: 'with', label: 'Me' },
                                            { value: 'without', label: 'Pa' },
                                        ] as const"
                                        :key="option.value"
                                        type="button"
                                        class="rounded-md px-3 py-1 text-sm transition-colors"
                                        :class="segmentClass(filters.possession === option.value)"
                                        @click="setPossession(option.value)"
                                    >
                                        {{ t(option.label) }}
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <span class="text-xs font-medium uppercase text-muted-foreground">{{ t('Dokumentet') }}</span>
                                <div class="inline-flex w-fit rounded-lg border bg-muted/40 p-1">
                                    <button
                                        v-for="option in [
                                            { value: '', label: 'Të gjitha' },
                                            { value: 'notary', label: 'Noter' },
                                            { value: 'lawyer', label: 'Avokat' },
                                        ] as const"
                                        :key="option.value"
                                        type="button"
                                        class="rounded-md px-3 py-1 text-sm transition-colors"
                                        :class="segmentClass(filters.documents === option.value)"
                                        @click="setDocuments(option.value)"
                                    >
                                        {{ t(option.label) }}
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <span class="text-xs font-medium uppercase text-muted-foreground">{{ t('Dhomat') }}</span>
                                <div class="inline-flex w-fit rounded-lg border bg-muted/40 p-1">
                                    <button
                                        type="button"
                                        class="rounded-md px-3 py-1 text-sm transition-colors"
                                        :class="segmentClass(filters.bedrooms == null)"
                                        @click="setBedrooms(null)"
                                    >
                                        {{ t('Të gjitha') }}
                                    </button>
                                    <button
                                        v-for="count in bedroomOptions"
                                        :key="count"
                                        type="button"
                                        class="rounded-md px-3 py-1 text-sm transition-colors"
                                        :class="segmentClass(filters.bedrooms === count)"
                                        @click="setBedrooms(count)"
                                    >
                                        {{ count === 5 ? '5+' : count }}
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <span class="text-xs font-medium uppercase text-muted-foreground">{{ t('Mobilimi') }}</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="option in furnishingOptions"
                                        :key="option.key"
                                        type="button"
                                        class="rounded-full border px-3 py-1 text-sm transition-colors"
                                        :class="chipClass(filters.furnishing.includes(option.key))"
                                        :aria-pressed="filters.furnishing.includes(option.key)"
                                        @click="toggleFurnishing(option.key)"
                                    >
                                        {{ option.name }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 border-t bg-card p-4">
                        <button
                            type="button"
                            class="flex-1 rounded-md border px-4 py-2.5 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                            @click="clearAndCloseMobile"
                        >
                            {{ t('Pastro') }}
                        </button>
                        <button
                            type="button"
                            class="flex-[2] rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90"
                            @click="applyAndCloseMobile"
                        >
                            {{ showResultsLabel }}
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
