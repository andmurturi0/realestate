<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import MapPicker from '@/components/MapPicker.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import LocaleTabBar, { type Locale } from '@/components/properties/LocaleTabBar.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    categoryDetailFields,
    categoryLabels,
    detailFieldLabels,
    statusLabels,
    type DetailField,
    type ListingType,
    type PropertyCategory,
    type PropertyStatus,
} from '@/lib/property';
import { useForm } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

export interface LocationOption {
    id: number;
    name: string;
    lat: number | null;
    lng: number | null;
}

export interface MunicipalityOption extends LocationOption {
    neighborhoods: LocationOption[];
}

export interface FeatureOption {
    id: number;
    name: string;
}

type Translations = Partial<Record<Locale, string | null>>;

export interface PropertyFormPayload {
    id: number;
    reference_code: string;
    listing_type: ListingType;
    is_exclusive: boolean;
    category: PropertyCategory;
    status: PropertyStatus;
    agent_id: number;
    title: Translations;
    description: Translations;
    price_euros: number;
    price_negotiable: boolean;
    surface_m2: number;
    land_surface_m2: number | null;
    bedrooms: number | null;
    bathrooms: number | null;
    floor: number | null;
    total_floors: number | null;
    year_built: number | null;
    parking_spaces: number | null;
    location_id: number;
    municipality_id: number | null;
    address_line: string | null;
    lat: number;
    lng: number;
    has_possession_sheet: boolean | null;
    document_type: 'notary' | 'lawyer' | null;
    meta_title: Translations;
    meta_description: Translations;
    features: number[];
}

const props = defineProps<{
    municipalities: MunicipalityOption[];
    features: Record<string, FeatureOption[]>;
    agents: { id: number; name: string }[] | null;
    property?: PropertyFormPayload;
}>();

const emit = defineEmits<{
    (e: 'submit', form: ReturnType<typeof buildForm>): void;
}>();

const translations = (source?: Translations): Record<Locale, string> => ({
    sq: source?.sq ?? '',
    en: source?.en ?? '',
    de: source?.de ?? '',
});

const buildForm = () =>
    useForm({
        listing_type: (props.property?.listing_type ?? 'sale') as ListingType,
        is_exclusive: props.property?.is_exclusive ?? false,
        category: (props.property?.category ?? 'apartment') as PropertyCategory,
        status: (props.property?.status ?? 'draft') as PropertyStatus,
        agent_id: props.property?.agent_id ?? null,
        title: translations(props.property?.title),
        description: translations(props.property?.description),
        price: props.property?.price_euros ?? null,
        price_negotiable: props.property?.price_negotiable ?? false,
        surface_m2: props.property?.surface_m2 ?? null,
        land_surface_m2: props.property?.land_surface_m2 ?? null,
        bedrooms: props.property?.bedrooms ?? null,
        bathrooms: props.property?.bathrooms ?? null,
        floor: props.property?.floor ?? null,
        total_floors: props.property?.total_floors ?? null,
        year_built: props.property?.year_built ?? null,
        parking_spaces: props.property?.parking_spaces ?? null,
        location_id: props.property?.location_id ?? null,
        address_line: props.property?.address_line ?? '',
        lat: props.property?.lat ?? null,
        lng: props.property?.lng ?? null,
        has_possession_sheet: props.property?.has_possession_sheet ?? null,
        document_type: props.property?.document_type ?? null,
        meta_title: translations(props.property?.meta_title),
        meta_description: translations(props.property?.meta_description),
        features: props.property?.features ?? [],
    });

const form = buildForm();

const submit = () => emit('submit', form);

// ---- Basics ------------------------------------------------------------

const listingTypeOptions: { value: ListingType; label: string }[] = [
    { value: 'sale', label: 'Në shitje' },
    { value: 'rent', label: 'Me qira' },
];

const selectClass =
    'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';

// ---- Content (translations) --------------------------------------------

const contentLocale = ref<Locale>('sq');
const seoLocale = ref<Locale>('sq');

const errors = computed(() => form.errors as Record<string, string | undefined>);

const localesWithErrors = (fields: string[]): Locale[] =>
    (['sq', 'en', 'de'] as Locale[]).filter((locale) => fields.some((field) => errors.value[`${field}.${locale}`]));

// ---- Details (category-dependent) --------------------------------------

const visibleDetailFields = computed<DetailField[]>(() => categoryDetailFields[form.category] ?? []);

watch(
    () => form.category,
    (category) => {
        const allowed = categoryDetailFields[category] ?? [];

        (Object.keys(detailFieldLabels) as DetailField[]).forEach((field) => {
            if (!allowed.includes(field)) {
                form[field] = null;
            }
        });
    },
);

// ---- Location ----------------------------------------------------------

const municipalityId = ref<number | null>(props.property?.municipality_id ?? null);

const selectedMunicipality = computed(() => props.municipalities.find((m) => m.id === municipalityId.value));

const setPin = (location?: LocationOption | null) => {
    if (location && location.lat != null && location.lng != null) {
        form.lat = location.lat;
        form.lng = location.lng;
    }
};

const neighborhoodId = computed<number | null>({
    get: () => (form.location_id !== null && form.location_id !== municipalityId.value ? form.location_id : null),
    set: (value) => {
        form.location_id = value ?? municipalityId.value;
        setPin(value ? selectedMunicipality.value?.neighborhoods.find((n) => n.id === value) : selectedMunicipality.value);
    },
});

watch(municipalityId, (id) => {
    form.location_id = id;
    setPin(props.municipalities.find((m) => m.id === id));
});

const updateCoordinates = (coords: { lat: number; lng: number }) => {
    form.lat = coords.lat;
    form.lng = coords.lng;
};

// ---- Documents ---------------------------------------------------------

const possessionOptions: { value: boolean | null; label: string }[] = [
    { value: true, label: 'Po' },
    { value: false, label: 'Jo' },
    { value: null, label: 'E panjohur' },
];

const documentTypeOptions: { value: 'notary' | 'lawyer' | null; label: string }[] = [
    { value: 'notary', label: 'Noter' },
    { value: 'lawyer', label: 'Avokat' },
    { value: null, label: 'Asnjë' },
];

// ---- Features ----------------------------------------------------------

const featureGroupLabels: Record<string, string> = {
    infrastructure: 'Infrastruktura',
    furnishing: 'Mobilimi',
};

const toggleFeature = (id: number, checked: boolean) => {
    form.features = checked ? [...form.features, id] : form.features.filter((featureId) => featureId !== id);
};

defineExpose({ form });
</script>

<template>
    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <!-- 1. Bazat -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Bazat</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Lloji i shpalljes</Label>
                    <div class="inline-flex w-fit gap-1 rounded-md border p-1">
                        <button
                            v-for="option in listingTypeOptions"
                            :key="option.value"
                            type="button"
                            class="rounded px-4 py-1.5 text-sm font-medium"
                            :class="
                                form.listing_type === option.value
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            @click="form.listing_type = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                    <InputError :message="errors.listing_type" />
                </div>

                <div class="grid gap-2">
                    <Label for="category">Kategoria</Label>
                    <select id="category" v-model="form.category" :class="selectClass">
                        <option v-for="(label, value) in categoryLabels" :key="value" :value="value">{{ label }}</option>
                    </select>
                    <InputError :message="errors.category" />
                </div>

                <div class="grid gap-2">
                    <Label for="status">Statusi</Label>
                    <select id="status" v-model="form.status" :class="selectClass">
                        <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
                    </select>
                    <InputError :message="errors.status" />
                </div>

                <div v-if="agents" class="grid gap-2">
                    <Label for="agent_id">Agjenti</Label>
                    <select id="agent_id" v-model="form.agent_id" :class="selectClass">
                        <option :value="null">— Vetë unë —</option>
                        <option v-for="agent in agents" :key="agent.id" :value="agent.id">{{ agent.name }}</option>
                    </select>
                    <InputError :message="errors.agent_id" />
                </div>

                <label class="flex items-center gap-2 text-sm font-medium">
                    <Checkbox :checked="form.is_exclusive" @update:checked="(value) => (form.is_exclusive = value)" />
                    Ekskluzive
                </label>
            </CardContent>
        </Card>

        <!-- 2. Përmbajtja -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Përmbajtja</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4">
                <LocaleTabBar v-model="contentLocale" :error-locales="localesWithErrors(['title', 'description'])" />

                <div class="grid gap-2">
                    <Label :for="`title_${contentLocale}`">
                        Titulli ({{ contentLocale.toUpperCase() }})
                        <span v-if="contentLocale === 'sq'" class="text-destructive">*</span>
                    </Label>
                    <Input :id="`title_${contentLocale}`" v-model="form.title[contentLocale]" />
                    <InputError :message="errors[`title.${contentLocale}`]" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`description_${contentLocale}`">
                        Përshkrimi ({{ contentLocale.toUpperCase() }})
                        <span v-if="contentLocale === 'sq'" class="text-destructive">*</span>
                    </Label>
                    <textarea
                        :id="`description_${contentLocale}`"
                        v-model="form.description[contentLocale]"
                        rows="6"
                        :class="[selectClass, 'h-auto']"
                    />
                    <InputError :message="errors[`description.${contentLocale}`]" />
                </div>
            </CardContent>
        </Card>

        <!-- 3. Çmimi -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Çmimi</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="price">Çmimi (€) <span class="text-destructive">*</span></Label>
                    <MoneyInput id="price" v-model="form.price" />
                    <InputError :message="errors.price" />
                </div>

                <label class="flex items-center gap-2 self-end pb-2 text-sm font-medium">
                    <Checkbox :checked="form.price_negotiable" @update:checked="(value) => (form.price_negotiable = value)" />
                    Çmimi i negociueshëm
                </label>
            </CardContent>
        </Card>

        <!-- 4. Detajet -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Detajet</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="surface_m2">Sipërfaqja (m²) <span class="text-destructive">*</span></Label>
                    <Input id="surface_m2" v-model="form.surface_m2" type="number" min="1" step="0.01" />
                    <InputError :message="errors.surface_m2" />
                </div>

                <div v-for="field in visibleDetailFields" :key="field" class="grid gap-2">
                    <Label :for="field">{{ detailFieldLabels[field] }}</Label>
                    <Input :id="field" v-model="form[field]" type="number" :step="field === 'land_surface_m2' ? '0.01' : '1'" />
                    <InputError :message="errors[field]" />
                </div>
            </CardContent>
        </Card>

        <!-- 5. Lokacioni -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Lokacioni</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="municipality">Komuna <span class="text-destructive">*</span></Label>
                        <select id="municipality" v-model="municipalityId" :class="selectClass">
                            <option :value="null" disabled>Zgjedh komunën…</option>
                            <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                                {{ municipality.name }}
                            </option>
                        </select>
                        <InputError :message="errors.location_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="neighborhood">Lagjja</Label>
                        <select
                            id="neighborhood"
                            v-model="neighborhoodId"
                            :class="selectClass"
                            :disabled="!selectedMunicipality || selectedMunicipality.neighborhoods.length === 0"
                        >
                            <option :value="null">— Vetëm komuna —</option>
                            <option v-for="neighborhood in selectedMunicipality?.neighborhoods ?? []" :key="neighborhood.id" :value="neighborhood.id">
                                {{ neighborhood.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="address_line">Adresa</Label>
                    <Input id="address_line" v-model="form.address_line" />
                    <InputError :message="errors.address_line" />
                </div>

                <div class="grid gap-2">
                    <Label>Pozita në hartë (kliko ose zvarrit pinin)</Label>
                    <MapPicker :lat="form.lat" :lng="form.lng" @update="updateCoordinates" />
                    <div class="flex gap-4 text-xs text-muted-foreground">
                        <span>Lat: {{ form.lat ?? '—' }}</span>
                        <span>Lng: {{ form.lng ?? '—' }}</span>
                    </div>
                    <InputError :message="errors.lat ?? errors.lng" />
                </div>
            </CardContent>
        </Card>

        <!-- 6. Veçoritë -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Veçoritë</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-6 sm:grid-cols-2">
                <div v-for="(groupFeatures, group) in features" :key="group" class="grid content-start gap-3">
                    <h3 class="text-sm font-semibold">{{ featureGroupLabels[group] ?? group }}</h3>
                    <label v-for="feature in groupFeatures" :key="feature.id" class="flex items-center gap-2 text-sm">
                        <Checkbox :checked="form.features.includes(feature.id)" @update:checked="(value) => toggleFeature(feature.id, value)" />
                        {{ feature.name }}
                    </label>
                </div>
                <InputError :message="errors.features" />
            </CardContent>
        </Card>

        <!-- 7. Dokumentet -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Dokumentet</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Fleta poseduese</Label>
                    <div class="inline-flex w-fit gap-1 rounded-md border p-1">
                        <button
                            v-for="option in possessionOptions"
                            :key="option.label"
                            type="button"
                            class="rounded px-3 py-1.5 text-sm font-medium"
                            :class="
                                form.has_possession_sheet === option.value
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            @click="form.has_possession_sheet = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                    <InputError :message="errors.has_possession_sheet" />
                </div>

                <div class="grid gap-2">
                    <Label>Dokumentacioni</Label>
                    <div class="inline-flex w-fit gap-1 rounded-md border p-1">
                        <button
                            v-for="option in documentTypeOptions"
                            :key="option.label"
                            type="button"
                            class="rounded px-3 py-1.5 text-sm font-medium"
                            :class="
                                form.document_type === option.value
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            @click="form.document_type = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                    <InputError :message="errors.document_type" />
                </div>
            </CardContent>
        </Card>

        <!-- 8. SEO -->
        <Collapsible>
            <Card>
                <CollapsibleTrigger class="w-full">
                    <CardHeader class="flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-base">SEO</CardTitle>
                        <ChevronDown class="size-4 text-muted-foreground" />
                    </CardHeader>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <CardContent class="grid gap-4">
                        <LocaleTabBar v-model="seoLocale" :error-locales="localesWithErrors(['meta_title', 'meta_description'])" />

                        <div class="grid gap-2">
                            <Label :for="`meta_title_${seoLocale}`">Meta titulli ({{ seoLocale.toUpperCase() }})</Label>
                            <Input :id="`meta_title_${seoLocale}`" v-model="form.meta_title[seoLocale]" />
                            <InputError :message="errors[`meta_title.${seoLocale}`]" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`meta_description_${seoLocale}`"> Meta përshkrimi ({{ seoLocale.toUpperCase() }}) </Label>
                            <textarea
                                :id="`meta_description_${seoLocale}`"
                                v-model="form.meta_description[seoLocale]"
                                rows="3"
                                :class="[selectClass, 'h-auto']"
                            />
                            <InputError :message="errors[`meta_description.${seoLocale}`]" />
                        </div>
                    </CardContent>
                </CollapsibleContent>
            </Card>
        </Collapsible>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">
                <slot name="submit-label">Ruaj</slot>
            </Button>
            <span v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">U ruajt.</span>
        </div>
    </form>
</template>
