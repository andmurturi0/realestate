<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { paginationLabel } from '@/lib/pagination';
import {
    categoryLabels,
    formatPriceCents,
    listingTypeLabels,
    statusClasses,
    statusLabels,
    type ListingType,
    type PropertyCategory,
    type PropertyStatus,
} from '@/lib/property';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown, ImageOff, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface PropertyRow {
    id: number;
    reference_code: string;
    title: string;
    category: PropertyCategory;
    listing_type: ListingType;
    is_exclusive: boolean;
    price: number;
    location: string | null;
    agent: string | null;
    status: PropertyStatus;
    views_count: number;
    thumbnail_url: string | null;
    can: { update: boolean; delete: boolean };
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Filters {
    status?: string;
    category?: string;
    listing_type?: string;
    agent?: string;
    search?: string;
    sort?: string;
    direction?: string;
}

const props = defineProps<{
    properties: {
        data: PropertyRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: Filters;
    agents: { id: number; name: string }[] | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pronat', href: '/dashboard/properties' }];

const flashSuccess = computed(() => usePage<SharedData>().props.flash.success);

// ---- Filters -----------------------------------------------------------

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const category = ref(props.filters.category ?? '');
const listingType = ref(props.filters.listing_type ?? '');
const agent = ref(props.filters.agent ?? '');
const sort = ref(props.filters.sort ?? '');
const direction = ref(props.filters.direction ?? '');

const reload = () => {
    const params: Record<string, string> = {};

    if (search.value) params.search = search.value;
    if (status.value) params.status = status.value;
    if (category.value) params.category = category.value;
    if (listingType.value) params.listing_type = listingType.value;
    if (agent.value) params.agent = agent.value;
    if (sort.value) params.sort = sort.value;
    if (direction.value) params.direction = direction.value;

    router.get(route('dashboard.properties.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(reload, 300);
});

watch([status, category, listingType, agent], reload);

// ---- Sorting -----------------------------------------------------------

const sortBy = (column: string) => {
    if (sort.value === column) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = column;
        direction.value = column === 'reference_code' ? 'asc' : 'desc';
    }

    reload();
};

const sortIcon = (column: string) => {
    if (sort.value !== column) return ArrowUpDown;

    return direction.value === 'asc' ? ArrowUp : ArrowDown;
};

// ---- Delete ------------------------------------------------------------

const propertyToDelete = ref<PropertyRow | null>(null);

const confirmDelete = () => {
    if (!propertyToDelete.value) return;

    router.delete(route('dashboard.properties.destroy', propertyToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => (propertyToDelete.value = null),
    });
};

const selectClass =
    'h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
</script>

<template>
    <Head title="Pronat" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-medium">Pronat</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{{ properties.total }} prona gjithsej</p>
                </div>
                <Button as-child>
                    <Link :href="route('dashboard.properties.create')">
                        <Plus class="size-4" />
                        Shto pronë
                    </Link>
                </Button>
            </div>

            <div
                v-if="flashSuccess"
                class="rounded-md border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200"
            >
                {{ flashSuccess }}
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Input v-model="search" placeholder="Kërko me kod ose titull…" class="w-full sm:w-64" />

                <select v-model="status" :class="selectClass">
                    <option value="">Të gjitha statuset</option>
                    <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
                </select>

                <select v-model="category" :class="selectClass">
                    <option value="">Të gjitha kategoritë</option>
                    <option v-for="(label, value) in categoryLabels" :key="value" :value="value">{{ label }}</option>
                </select>

                <select v-model="listingType" :class="selectClass">
                    <option value="">Shitje &amp; qira</option>
                    <option v-for="(label, value) in listingTypeLabels" :key="value" :value="value">{{ label }}</option>
                </select>

                <select v-if="agents" v-model="agent" :class="selectClass">
                    <option value="">Të gjithë agjentët</option>
                    <option v-for="agentOption in agents" :key="agentOption.id" :value="String(agentOption.id)">
                        {{ agentOption.name }}
                    </option>
                </select>
            </div>

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full min-w-[900px] text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                            <th class="px-3 py-2 font-medium">Foto</th>
                            <th class="px-3 py-2 font-medium">
                                <button type="button" class="inline-flex items-center gap-1" @click="sortBy('reference_code')">
                                    Kodi
                                    <component :is="sortIcon('reference_code')" class="size-3" />
                                </button>
                            </th>
                            <th class="px-3 py-2 font-medium">Titulli</th>
                            <th class="px-3 py-2 font-medium">Kategoria</th>
                            <th class="px-3 py-2 font-medium">Lloji</th>
                            <th class="px-3 py-2 font-medium">
                                <button type="button" class="inline-flex items-center gap-1" @click="sortBy('price')">
                                    Çmimi
                                    <component :is="sortIcon('price')" class="size-3" />
                                </button>
                            </th>
                            <th class="px-3 py-2 font-medium">Lokacioni</th>
                            <th class="px-3 py-2 font-medium">Agjenti</th>
                            <th class="px-3 py-2 font-medium">
                                <button type="button" class="inline-flex items-center gap-1" @click="sortBy('status')">
                                    Statusi
                                    <component :is="sortIcon('status')" class="size-3" />
                                </button>
                            </th>
                            <th class="px-3 py-2 font-medium">
                                <button type="button" class="inline-flex items-center gap-1" @click="sortBy('views_count')">
                                    Shikime
                                    <component :is="sortIcon('views_count')" class="size-3" />
                                </button>
                            </th>
                            <th class="px-3 py-2 text-right font-medium">Veprime</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="properties.data.length === 0">
                            <td colspan="11" class="px-3 py-12 text-center text-muted-foreground">Nuk u gjet asnjë pronë me këta filtra.</td>
                        </tr>
                        <tr v-for="property in properties.data" :key="property.id" class="border-b last:border-0 hover:bg-muted/30">
                            <td class="px-3 py-2">
                                <img
                                    v-if="property.thumbnail_url"
                                    :src="property.thumbnail_url"
                                    :alt="property.title"
                                    class="h-10 w-14 rounded object-cover"
                                    loading="lazy"
                                />
                                <div v-else class="flex h-10 w-14 items-center justify-center rounded bg-muted">
                                    <ImageOff class="size-4 text-muted-foreground" />
                                </div>
                            </td>
                            <td class="px-3 py-2 font-mono text-xs">{{ property.reference_code }}</td>
                            <td class="max-w-56 truncate px-3 py-2 font-medium" :title="property.title">
                                {{ property.title }}
                            </td>
                            <td class="px-3 py-2">
                                <span class="rounded-full bg-muted px-2 py-0.5 text-xs">{{ categoryLabels[property.category] }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-1">
                                    <span class="rounded-full bg-muted px-2 py-0.5 text-xs">
                                        {{ listingTypeLabels[property.listing_type] }}
                                    </span>
                                    <span
                                        v-if="property.is_exclusive"
                                        class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                                    >
                                        Ekskluzive
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 font-medium">{{ formatPriceCents(property.price) }}</td>
                            <td class="px-3 py-2">{{ property.location ?? '—' }}</td>
                            <td class="px-3 py-2">{{ property.agent ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs" :class="statusClasses[property.status]">
                                    {{ statusLabels[property.status] }}
                                </span>
                            </td>
                            <td class="px-3 py-2 tabular-nums">{{ property.views_count }}</td>
                            <td class="px-3 py-2">
                                <div class="flex justify-end gap-1">
                                    <Button v-if="property.can.update" variant="ghost" size="icon" as-child>
                                        <Link
                                            :href="route('dashboard.properties.edit', property.id)"
                                            :aria-label="`Edito ${property.reference_code}`"
                                        >
                                            <Pencil class="size-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="property.can.delete"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        :aria-label="`Fshi ${property.reference_code}`"
                                        @click="propertyToDelete = property"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="properties.links.length > 3" class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted-foreground">
                    Duke shfaqur {{ properties.from ?? 0 }}–{{ properties.to ?? 0 }} nga {{ properties.total }}
                </p>
                <nav class="flex flex-wrap gap-1">
                    <template v-for="(link, index) in properties.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            preserve-state
                            class="rounded-md border px-3 py-1.5 text-sm"
                            :class="link.active ? 'border-primary bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
                        >
                            <span>{{ paginationLabel(link.label, 'Prapa', 'Para') }}</span>
                        </Link>
                        <span v-else class="rounded-md border border-transparent px-3 py-1.5 text-sm text-muted-foreground/50">
                            {{ paginationLabel(link.label, 'Prapa', 'Para') }}
                        </span>
                    </template>
                </nav>
            </div>
        </div>

        <Dialog :open="propertyToDelete !== null" @update:open="(open) => !open && (propertyToDelete = null)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Fshi pronën {{ propertyToDelete?.reference_code }}?</DialogTitle>
                    <DialogDescription>
                        "{{ propertyToDelete?.title }}" do të fshihet nga lista. Ky veprim mund të zhbëhet vetëm nga administratori i sistemit.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="propertyToDelete = null">Anulo</Button>
                    <Button variant="destructive" @click="confirmDelete">Fshi pronën</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
