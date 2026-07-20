<script setup lang="ts">
import InboxPagination from '@/components/inbox/InboxPagination.vue';
import LeadDetailPanel from '@/components/inbox/LeadDetailPanel.vue';
import LeadFilters from '@/components/inbox/LeadFilters.vue';
import LeadList, { type LeadListItem } from '@/components/inbox/LeadList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type AgentOption, emptyLeadFilters, type LeadFilterState, leadFiltersToQuery, leadStatusLabels, whatsAppMessageLink } from '@/lib/inbox';
import { type Paginated } from '@/lib/listing';
import { formatPriceCents } from '@/lib/property';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ImageOff } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface MessageRow {
    id: number;
    name: string;
    phone: string;
    snippet: string;
    status: string;
    assigned_agent: string | null;
    has_property: boolean;
    created_at: string | null;
}

interface SelectedMessage {
    id: number;
    full_name: string;
    phone: string;
    email: string | null;
    message: string;
    status: string;
    assigned_to: number | null;
    created_at: string | null;
    can_assign: boolean;
    property: {
        id: number;
        slug: string;
        reference_code: string;
        title: string;
        price: number;
        thumbnail_url: string | null;
    } | null;
    notes: { id: number; body: string; author: string; created_at: string | null }[];
}

const props = defineProps<{
    messages: Paginated<MessageRow>;
    filters: Partial<LeadFilterState>;
    agents: AgentOption[] | null;
    selected: SelectedMessage | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Mesazhet', href: '/dashboard/inbox/messages' }];

const filters = ref<LeadFilterState>({ ...emptyLeadFilters(), ...props.filters });

const items = computed<LeadListItem[]>(() =>
    props.messages.data.map((message) => ({
        id: message.id,
        name: message.name,
        secondary: message.snippet,
        status: message.status,
        statusLabel: leadStatusLabels[message.status as keyof typeof leadStatusLabels] ?? message.status,
        assigned_agent: message.assigned_agent,
        created_at: message.created_at,
    })),
);

function reload(extra: Record<string, unknown> = {}) {
    router.get(route('dashboard.inbox.messages'), { ...leadFiltersToQuery(filters.value), ...extra }, { preserveState: true, preserveScroll: true });
}

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

function applyDebounced() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => reload({ selected: props.selected?.id }), 300);
}

function selectMessage(id: number) {
    router.get(
        route('dashboard.inbox.messages'),
        { ...leadFiltersToQuery(filters.value), selected: id },
        { only: ['selected'], preserveState: true, preserveScroll: true },
    );
}

const whatsappHref = computed(() =>
    props.selected
        ? whatsAppMessageLink(props.selected.phone, `Përshëndetje ${props.selected.full_name}, ju kontaktojmë lidhur me mesazhin tuaj.`)
        : null,
);
</script>

<template>
    <Head title="Mesazhet" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-medium">Mesazhet</h1>
                <p class="mt-1 text-sm text-muted-foreground">{{ messages.total }} mesazhe gjithsej</p>
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
                    <LeadList :items="items" :selected-id="selected?.id ?? null" @select="selectMessage" />

                    <InboxPagination :links="messages.links" :from="messages.from" :to="messages.to" :total="messages.total" />
                </div>

                <div v-if="selected">
                    <LeadDetailPanel
                        :title="selected.full_name"
                        :phone="selected.phone"
                        :status="selected.status"
                        :status-options="leadStatusLabels"
                        :assigned-to="selected.assigned_to"
                        :can-assign="selected.can_assign"
                        :agents="agents"
                        :notes="selected.notes"
                        :status-url="route('dashboard.inbox.messages.status', selected.id)"
                        :assign-url="route('dashboard.inbox.messages.assign', selected.id)"
                        :notes-url="route('dashboard.inbox.messages.notes.store', selected.id)"
                        :whatsapp-href="whatsappHref"
                    >
                        <template #extra>
                            <div class="mb-3 rounded-md bg-muted/40 p-3 text-sm">{{ selected.message }}</div>

                            <Link
                                v-if="selected.property"
                                :href="route('properties.show', selected.property.slug)"
                                target="_blank"
                                class="flex items-center gap-3 rounded-md border p-2 hover:bg-accent"
                            >
                                <img
                                    v-if="selected.property.thumbnail_url"
                                    :src="selected.property.thumbnail_url"
                                    :alt="selected.property.title"
                                    class="h-12 w-16 rounded object-cover"
                                />
                                <div v-else class="flex h-12 w-16 items-center justify-center rounded bg-muted">
                                    <ImageOff class="size-4 text-muted-foreground" />
                                </div>
                                <div>
                                    <p class="font-mono text-xs text-muted-foreground">{{ selected.property.reference_code }}</p>
                                    <p class="text-sm font-medium">{{ selected.property.title }}</p>
                                    <p class="text-sm text-muted-foreground">{{ formatPriceCents(selected.property.price) }}</p>
                                </div>
                            </Link>
                        </template>
                    </LeadDetailPanel>
                </div>
                <div v-else class="flex items-center justify-center rounded-lg border border-dashed p-8 text-sm text-muted-foreground">
                    Zgjedh një mesazh nga lista për të parë detajet.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
