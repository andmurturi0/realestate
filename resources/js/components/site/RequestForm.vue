<script setup lang="ts">
import LocationSelect from '@/components/site/LocationSelect.vue';
import { type LocationOption } from '@/lib/listing';
import { categoryLabels, type PropertyCategory } from '@/lib/property';
import { useTranslations } from '@/lib/trans';
import { Briefcase, Building, Building2, CheckCircle2, Home, LandPlot, Store, Warehouse, type LucideIcon } from 'lucide-vue-next';
import { onMounted, reactive, ref } from 'vue';

const props = defineProps<{
    municipalities: LocationOption[];
}>();

const { t } = useTranslations();

const categoryIcons: Record<PropertyCategory, LucideIcon> = {
    house: Home,
    apartment: Building2,
    office: Briefcase,
    store: Store,
    land: LandPlot,
    warehouse: Warehouse,
    object: Building,
};

const surfaceUnitLabels: Record<'m2' | 'ari' | 'ha', string> = {
    m2: 'm²',
    ari: 'Ari',
    ha: 'Ha',
};

const form = reactive({
    first_name: '',
    last_name: '',
    phone: '',
    request_type: 'buying' as 'buying' | 'renting',
    category: '' as PropertyCategory | '',
    location_id: null as number | null,
    budget: '',
    surface_unit: 'm2' as 'm2' | 'ari' | 'ha',
    surface_min: '',
    surface_max: '',
    details: '',
    // Honeypot: real visitors never see or fill this field.
    website: '',
    form_rendered_at: 0,
});

onMounted(() => {
    form.form_rendered_at = Date.now();
});

const errors = ref<Record<string, string[]>>({});
const submitting = ref(false);
const success = ref(false);
const rateLimited = ref(false);

function readCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

const segmentClass = (active: boolean) => (active ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground');

const chipClass = (active: boolean) =>
    active
        ? 'border-primary bg-primary text-primary-foreground'
        : 'border-input bg-background text-foreground hover:bg-accent hover:text-accent-foreground';

async function submit() {
    errors.value = {};
    rateLimited.value = false;
    submitting.value = true;

    try {
        const response = await fetch(route('create-request.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': readCookie('XSRF-TOKEN') ?? '',
            },
            body: JSON.stringify(form),
        });

        if (response.status === 422) {
            const body = (await response.json()) as { errors: Record<string, string[]> };
            errors.value = body.errors;

            return;
        }

        if (response.status === 429) {
            rateLimited.value = true;

            return;
        }

        if (response.ok) {
            success.value = true;
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="rounded-xl border bg-card p-6">
        <div v-if="success" class="flex flex-col items-center gap-2 py-10 text-center">
            <CheckCircle2 class="size-12 text-green-600" />
            <p class="text-lg font-medium">{{ t('Kërkesa juaj u dërgua!') }}</p>
            <p class="text-sm text-muted-foreground">{{ t("Një agjent do t'ju kontaktojë sapo të gjejë prona përputhëse.") }}</p>
        </div>

        <form v-else class="space-y-4" @submit.prevent="submit">
            <h2 class="text-lg font-medium">{{ t('Krijoni kërkesën tuaj') }}</h2>

            <!-- Honeypot: off-screen, never display:none (some bots skip that check). -->
            <div class="absolute -left-[9999px]" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" v-model="form.website" type="text" tabindex="-1" autocomplete="off" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="first_name" class="mb-1 block text-sm font-medium">{{ t('Emri') }}</label>
                    <input
                        id="first_name"
                        v-model="form.first_name"
                        type="text"
                        required
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <p v-if="errors.first_name" class="mt-1 text-xs text-destructive">{{ errors.first_name[0] }}</p>
                </div>

                <div>
                    <label for="last_name" class="mb-1 block text-sm font-medium">{{ t('Mbiemri') }}</label>
                    <input
                        id="last_name"
                        v-model="form.last_name"
                        type="text"
                        required
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <p v-if="errors.last_name" class="mt-1 text-xs text-destructive">{{ errors.last_name[0] }}</p>
                </div>
            </div>

            <div>
                <label for="phone" class="mb-1 block text-sm font-medium">{{ t('Telefoni') }}</label>
                <div class="flex items-center gap-2 rounded-md border border-input bg-background px-3 focus-within:ring-2 focus-within:ring-ring">
                    <span class="flex items-center gap-1 text-sm text-muted-foreground">🇽🇰 +383</span>
                    <input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        required
                        placeholder="044 123 456"
                        class="h-10 w-full border-0 bg-transparent px-0 text-sm focus-visible:outline-none"
                    />
                </div>
                <p v-if="errors.phone" class="mt-1 text-xs text-destructive">{{ errors.phone[0] }}</p>
            </div>

            <div>
                <span class="mb-1 block text-sm font-medium">{{ t('Lloji') }}</span>
                <div class="inline-flex rounded-lg border bg-muted/40 p-1">
                    <button
                        type="button"
                        class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors"
                        :class="segmentClass(form.request_type === 'buying')"
                        @click="form.request_type = 'buying'"
                    >
                        {{ t('Blerje') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors"
                        :class="segmentClass(form.request_type === 'renting')"
                        @click="form.request_type = 'renting'"
                    >
                        {{ t('Qira') }}
                    </button>
                </div>
            </div>

            <div>
                <span class="mb-1 block text-sm font-medium">{{ t('Kategoria') }}</span>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="(label, category) in categoryLabels"
                        :key="category"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm transition-colors"
                        :class="chipClass(form.category === category)"
                        :aria-pressed="form.category === category"
                        @click="form.category = category"
                    >
                        <component :is="categoryIcons[category]" class="size-4" />
                        {{ t(label) }}
                    </button>
                </div>
                <p v-if="errors.category" class="mt-1 text-xs text-destructive">{{ errors.category[0] }}</p>
            </div>

            <div>
                <span class="mb-1 block text-sm font-medium">{{ t('Lokacioni') }}</span>
                <LocationSelect v-model="form.location_id" :locations="props.municipalities" />
                <p v-if="errors.location_id" class="mt-1 text-xs text-destructive">{{ errors.location_id[0] }}</p>
            </div>

            <div>
                <label for="budget" class="mb-1 block text-sm font-medium">{{ t('Buxheti (€)') }}</label>
                <input
                    id="budget"
                    v-model="form.budget"
                    type="number"
                    min="0"
                    inputmode="numeric"
                    required
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                <p v-if="errors.budget" class="mt-1 text-xs text-destructive">{{ errors.budget[0] }}</p>
            </div>

            <div>
                <span class="mb-1 block text-sm font-medium">{{ t('Sipërfaqja') }}</span>
                <div class="grid grid-cols-[1fr_1fr_auto] gap-2">
                    <input
                        v-model="form.surface_min"
                        type="number"
                        min="0"
                        inputmode="numeric"
                        :placeholder="t('Min')"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <input
                        v-model="form.surface_max"
                        type="number"
                        min="0"
                        inputmode="numeric"
                        :placeholder="t('Max')"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <select
                        v-model="form.surface_unit"
                        class="h-10 rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <option v-for="(label, unit) in surfaceUnitLabels" :key="unit" :value="unit">{{ t(label) }}</option>
                    </select>
                </div>
                <p v-if="errors.surface_min" class="mt-1 text-xs text-destructive">{{ errors.surface_min[0] }}</p>
                <p v-if="errors.surface_max" class="mt-1 text-xs text-destructive">{{ errors.surface_max[0] }}</p>
            </div>

            <div>
                <label for="details" class="mb-1 block text-sm font-medium">{{ t('Më shumë detaje') }}</label>
                <textarea
                    id="details"
                    v-model="form.details"
                    rows="4"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                <p v-if="errors.details" class="mt-1 text-xs text-destructive">{{ errors.details[0] }}</p>
            </div>

            <p v-if="rateLimited" class="text-sm text-destructive">
                {{ t('Keni arritur numrin maksimal të kërkesave. Ju lutemi provoni sërish më vonë.') }}
            </p>

            <button
                type="submit"
                :disabled="submitting"
                class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 disabled:opacity-50"
            >
                {{ submitting ? t('Duke dërguar…') : t('Dërgo kërkesën') }}
            </button>
        </form>
    </div>
</template>
