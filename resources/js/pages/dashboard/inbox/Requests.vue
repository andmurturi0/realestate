<script setup lang="ts">
import InboxPagination from '@/components/inbox/InboxPagination.vue';
import LeadDetailPanel from '@/components/inbox/LeadDetailPanel.vue';
import LeadFilters from '@/components/inbox/LeadFilters.vue';
import LeadList, { type LeadListItem } from '@/components/inbox/LeadList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type AgentOption, emptyLeadFilters, type LeadFilterState, leadFiltersToQuery, leadStatusLabels, whatsAppMessageLink } from '@/lib/inbox';
import { type Paginated, type PropertyCardData } from '@/lib/listing';
import { categoryLabels, formatPriceCents, type PropertyCategory } from '@/lib/property';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { MessageCircle, SearchX } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface RequestRow {
    id: number;
    name: string;
    phone: string;
    category: PropertyCategory;
    request_type: 'buying' | 'renting';
    budget_max: number;
    status: string;
    assigned_agent: string | null;
    created_at: string | null;
}

interface SelectedRequest {
    id: number;
    first_name: string;
    last_name: string;
    phone: string;
    request_type: 'buying' | 'renting';
    category: PropertyCategory;
    location: string;
    budget_max: number;
    surface_min_m2: number | null;
    surface_max_m2: number | null;
    surface_unit: 'm2' | 'ari' | 'ha';
    details: string | null;
    status: string;
    assigned_to: number | null;
    created_at: string | null;
    can_assign: boolean;
    matches: PropertyCardData[];
    notes: { id: number; body: string; author: string; created_at: string | null }[];
}

const props = defineProps<{
    requests: Paginated<RequestRow>;
    filters: Partial<LeadFilterState>;
    agents: AgentOption[] | null;
    selected: SelectedRequest | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Kërkesat', href: '/dashboard/inbox/requests' }];

const filters = ref<LeadFilterState>({ ...emptyLeadFilters(), ...props.filters });

const items = computed<LeadListItem[]>(() =>
    props.requests.data.map((request) => ({
        id: request.id,
        name: request.name,
        secondary: `${categoryLabels[request.category]} · deri ${formatPriceCents(request.budget_max)}`,
        status: request.status,
        statusLabel: leadStatusLabels[request.status as keyof typeof leadStatusLabels] ?? request.status,
        assigned_agent: request.assigned_agent,
        created_at: request.created_at,
    })),
);

function reload(extra: Record<string, unknown> = {}) {
    router.get(route('dashboard.inbox.requests'), { ...leadFiltersToQuery(filters.value), ...extra }, { preserveState: true, preserveScroll: true });
}

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

function applyDebounced() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => reload({ selected: props.selected?.id }), 300);
}

function selectRequest(id: number) {
    router.get(
        route('dashboard.inbox.requests'),
        { ...leadFiltersToQuery(filters.value), selected: id },
        { only: ['selected'], preserveState: true, preserveScroll: true },
    );
}

const whatsappHref = computed(() =>
    props.selected
        ? whatsAppMessageLink(props.selected.phone, `Përshëndetje ${props.selected.first_name}, kemi lajme lidhur me kërkesën tuaj.`)
        : null,
);

function matchWhatsAppHref(match: PropertyCardData): string {
    if (!props.selected) return '#';

    const url = route('properties.show', match.slug);
    const message = `Përshëndetje ${props.selected.first_name}, kjo pronë mund t'ju interesojë: ${match.reference_code} — ${url}`;

    return whatsAppMessageLink(props.selected.phone, message);
}

const surfaceUnitLabel: Record<'m2' | 'ari' | 'ha', string> = { m2: 'm²', ari: 'ari', ha: 'ha' };
</script>

<template>
    <Head title="Kërkesat" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-medium">Kërkesat</h1>
                <p class="mt-1 text-sm text-muted-foreground">{{ requests.total }} kërkesa gjithsej</p>
            </div>

            <LeadFilters
                v-model:filters="filters"
                :agents="agents"
                :status-options="leadStatusLabels"
                @apply="reload()"
                @apply-debounced="applyDebounced"
            />

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
                <div class="flex flex-col gap-3">
                    <LeadList :items="items" :selected-id="selected?.id ?? null" @select="selectRequest" />

                    <InboxPagination :links="requests.links" :from="requests.from" :to="requests.to" :total="requests.total" />
                </div>

                <div v-if="selected">
                    <LeadDetailPanel
                        :title="`${selected.first_name} ${selected.last_name}`"
                        :phone="selected.phone"
                        :status="selected.status"
                        :status-options="leadStatusLabels"
                        :assigned-to="selected.assigned_to"
                        :can-assign="selected.can_assign"
                        :agents="agents"
                        :notes="selected.notes"
                        :status-url="route('dashboard.inbox.requests.status', selected.id)"
                        :assign-url="route('dashboard.inbox.requests.assign', selected.id)"
                        :notes-url="route('dashboard.inbox.requests.notes.store', selected.id)"
                        :whatsapp-href="whatsappHref"
                    >
                        <template #extra>
                            <div class="mb-4 grid grid-cols-2 gap-2 rounded-md bg-muted/40 p-3 text-sm sm:grid-cols-4">
                                <div><span class="text-muted-foreground">Kategoria:</span> {{ categoryLabels[selected.category] }}</div>
                                <div>
                                    <span class="text-muted-foreground">Lloji:</span> {{ selected.request_type === 'buying' ? 'Blerje' : 'Qira' }}
                                </div>
                                <div><span class="text-muted-foreground">Lokacioni:</span> {{ selected.location }}</div>
                                <div>
                                    <span class="text-muted-foreground">Sipërfaqja:</span>
                                    {{ selected.surface_min_m2 ?? '—' }}–{{ selected.surface_max_m2 ?? '—' }}
                                    {{ surfaceUnitLabel[selected.surface_unit] }}
                                </div>
                                <div class="font-medium">Buxheti: deri {{ formatPriceCents(selected.budget_max) }}</div>
                                <div v-if="selected.details" class="col-span-2 sm:col-span-4">
                                    <span class="text-muted-foreground">Detaje:</span> {{ selected.details }}
                                </div>
                            </div>

                            <h3 class="mb-2 text-sm font-medium">Prona përputhëse</h3>

                            <div
                                v-if="selected.matches.length === 0"
                                class="flex flex-col items-center gap-2 rounded-md border border-dashed p-6 text-center"
                            >
                                <SearchX class="size-6 text-muted-foreground" />
                                <p class="text-sm text-muted-foreground">Asnjë pronë nuk përputhet me këtë kërkesë për momentin.</p>
                                <p class="text-xs text-muted-foreground">Provoni të zgjeroni kriteret (buxhet, sipërfaqe ose lokacion).</p>
                            </div>

                            <div v-else class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <div v-for="match in selected.matches" :key="match.id" class="flex items-center gap-2 rounded-md border p-2">
                                    <img
                                        v-if="match.thumbnail_url"
                                        :src="match.thumbnail_url"
                                        :alt="match.title"
                                        class="h-12 w-16 shrink-0 rounded object-cover"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium">{{ match.title }}</p>
                                        <p class="text-xs text-muted-foreground">{{ formatPriceCents(match.price) }} · {{ match.surface_m2 }} m²</p>
                                        <Link
                                            :href="route('properties.show', match.slug)"
                                            target="_blank"
                                            class="text-xs text-primary hover:underline"
                                        >
                                            {{ match.reference_code }}
                                        </Link>
                                    </div>
                                    <a
                                        :href="matchWhatsAppHref(match)"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex shrink-0 items-center gap-1 rounded-md border px-2 py-1 text-xs hover:bg-accent"
                                    >
                                        <MessageCircle class="size-3.5" />
                                        Dërgo
                                    </a>
                                </div>
                            </div>
                        </template>
                    </LeadDetailPanel>
                </div>
                <div v-else class="flex items-center justify-center rounded-lg border border-dashed p-8 text-sm text-muted-foreground">
                    Zgjedh një kërkesë nga lista për të parë detajet.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
