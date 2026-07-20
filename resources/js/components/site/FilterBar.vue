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
    SlidersHorizontal,
    Store,
    Warehouse,
    X,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    locations: LocationOption[];
    furnishingOptions: FurnishingOption[];
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
    <div class="flex flex-col gap-3 rounded-xl border bg-card p-4 shadow-sm">
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
        </div>

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
</template>
