<script setup lang="ts">
import InboxPagination from '@/components/inbox/InboxPagination.vue';
import LeadDetailPanel from '@/components/inbox/LeadDetailPanel.vue';
import LeadFilters from '@/components/inbox/LeadFilters.vue';
import LeadList, { type LeadListItem } from '@/components/inbox/LeadList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type AgentOption, emptyLeadFilters, type LeadFilterState, leadFiltersToQuery, offerStatusLabels, whatsAppMessageLink } from '@/lib/inbox';
import { type Paginated } from '@/lib/listing';
import { categoryLabels, formatPriceCents, type ListingType, type PropertyCategory } from '@/lib/property';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Home } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface OfferRow {
    id: number;
    name: string;
    phone: string;
    category: PropertyCategory;
    listing_type: ListingType;
    surface_m2: number;
    asking_price: number;
    status: string;
    assigned_agent: string | null;
    converted_property_id: number | null;
    created_at: string | null;
}

interface SelectedOffer {
    id: number;
    first_name: string;
    last_name: string;
    phone: string;
    listing_type: ListingType;
    category: PropertyCategory;
    location: string;
    surface_m2: number;
    asking_price: number;
    status: string;
    assigned_to: number | null;
    created_at: string | null;
    can_assign: boolean;
    converted_property: { slug: string; reference_code: string } | null;
    notes: { id: number; body: string; author: string; created_at: string | null }[];
}

const props = defineProps<{
    offers: Paginated<OfferRow>;
    filters: Partial<LeadFilterState>;
    agents: AgentOption[] | null;
    unassignedCount: number | null;
    selected: SelectedOffer | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Ofertat', href: '/dashboard/inbox/offers' }];

const filters = ref<LeadFilterState>({ ...emptyLeadFilters(), ...props.filters });

const items = computed<LeadListItem[]>(() =>
    props.offers.data.map((offer) => ({
        id: offer.id,
        name: offer.name,
        secondary: `${categoryLabels[offer.category]} · ${offer.surface_m2} m² · ${formatPriceCents(offer.asking_price)}`,
        status: offer.status,
        statusLabel: offerStatusLabels[offer.status as keyof typeof offerStatusLabels] ?? offer.status,
        assigned_agent: offer.assigned_agent,
        created_at: offer.created_at,
    })),
);

function reload(extra: Record<string, unknown> = {}) {
    router.get(route('dashboard.inbox.offers'), { ...leadFiltersToQuery(filters.value), ...extra }, { preserveState: true, preserveScroll: true });
}

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

function applyDebounced() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => reload({ selected: props.selected?.id }), 300);
}

function selectOffer(id: number) {
    router.get(
        route('dashboard.inbox.offers'),
        { ...leadFiltersToQuery(filters.value), selected: id },
        { only: ['selected'], preserveState: true, preserveScroll: true },
    );
}

const whatsappHref = computed(() =>
    props.selected
        ? whatsAppMessageLink(props.selected.phone, `Përshëndetje ${props.selected.first_name}, ju kontaktojmë lidhur me ofertën tuaj për pronën.`)
        : null,
);
</script>

<template>
    <Head title="Ofertat" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-medium">Ofertat</h1>
                <p class="mt-1 text-sm text-muted-foreground">{{ offers.total }} oferta gjithsej</p>
            </div>

            <div
                v-if="unassignedCount"
                class="rounded-md border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200"
            >
                {{ unassignedCount }} oferta pa agjent.
            </div>

            <LeadFilters
                v-model:filters="filters"
                :agents="agents"
                :status-options="offerStatusLabels"
                @apply="reload()"
                @apply-debounced="applyDebounced"
            />

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
                <div class="flex flex-col gap-3">
                    <LeadList :items="items" :selected-id="selected?.id ?? null" @select="selectOffer" />

                    <InboxPagination :links="offers.links" :from="offers.from" :to="offers.to" :total="offers.total" />
                </div>

                <div v-if="selected">
                    <LeadDetailPanel
                        :title="`${selected.first_name} ${selected.last_name}`"
                        :phone="selected.phone"
                        :status="selected.status"
                        :status-options="offerStatusLabels"
                        :assigned-to="selected.assigned_to"
                        :can-assign="selected.can_assign"
                        :agents="agents"
                        :notes="selected.notes"
                        :status-url="route('dashboard.inbox.offers.status', selected.id)"
                        :assign-url="route('dashboard.inbox.offers.assign', selected.id)"
                        :notes-url="route('dashboard.inbox.offers.notes.store', selected.id)"
                        :whatsapp-href="whatsappHref"
                    >
                        <template #extra>
                            <div class="mb-3 grid grid-cols-2 gap-2 rounded-md bg-muted/40 p-3 text-sm sm:grid-cols-4">
                                <div><span class="text-muted-foreground">Kategoria:</span> {{ categoryLabels[selected.category] }}</div>
                                <div>
                                    <span class="text-muted-foreground">Lloji:</span> {{ selected.listing_type === 'sale' ? 'Shitje' : 'Qira' }}
                                </div>
                                <div><span class="text-muted-foreground">Lokacioni:</span> {{ selected.location }}</div>
                                <div><span class="text-muted-foreground">Sipërfaqja:</span> {{ selected.surface_m2 }} m²</div>
                                <div class="font-medium">Çmimi kërkuar: {{ formatPriceCents(selected.asking_price) }}</div>
                            </div>

                            <Link
                                v-if="selected.converted_property"
                                :href="route('properties.show', selected.converted_property.slug)"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm hover:bg-accent"
                            >
                                <Home class="size-4" />
                                Shiko pronën {{ selected.converted_property.reference_code }}
                            </Link>
                            <Link
                                v-else
                                :href="route('dashboard.inbox.offers.convert.create', selected.id)"
                                class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90"
                            >
                                <Home class="size-4" />
                                Krijo pronë nga kjo ofertë
                            </Link>
                        </template>
                    </LeadDetailPanel>
                </div>
                <div v-else class="flex items-center justify-center rounded-lg border border-dashed p-8 text-sm text-muted-foreground">
                    Zgjedh një ofertë nga lista për të parë detajet.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
