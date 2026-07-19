<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Building2, CalendarDays, CheckCircle2, Inbox } from 'lucide-vue-next';
import { computed } from 'vue';

interface RecentLead {
    type: 'message' | 'offer' | 'request';
    id: number;
    name: string;
    phone: string;
    status: string;
    created_at: string | null;
}

const props = defineProps<{
    stats: {
        properties: number;
        published: number;
        newLeads: number;
        monthLeads: number;
    };
    recentLeads: RecentLead[];
}>();

const page = usePage<SharedData>();
const isAgent = computed(() => page.props.auth.user.role === 'agent');

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Përmbledhje', href: '/dashboard' }];

const statCards = computed(() => [
    { label: isAgent.value ? 'Pronat e mia' : 'Pronat', value: props.stats.properties, icon: Building2 },
    { label: 'Të publikuara', value: props.stats.published, icon: CheckCircle2 },
    { label: 'Leads të reja', value: props.stats.newLeads, icon: Inbox },
    { label: 'Leads këtë muaj', value: props.stats.monthLeads, icon: CalendarDays },
]);

const typeLabels: Record<RecentLead['type'], string> = {
    message: 'Mesazh',
    offer: 'Ofertë',
    request: 'Kërkesë',
};

const statusLabels: Record<string, string> = {
    new: 'E re',
    contacted: 'Kontaktuar',
    in_progress: 'Në proces',
    closed: 'Mbyllur',
    converted: 'Konvertuar',
    rejected: 'Refuzuar',
};

const formatDate = (value: string | null) =>
    value
        ? new Date(value).toLocaleDateString('sq-AL', {
              day: 'numeric',
              month: 'short',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '';
</script>

<template>
    <Head title="Përmbledhje" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card v-for="card in statCards" :key="card.label">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ card.label }}</CardTitle>
                        <component :is="card.icon" class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-semibold">{{ card.value }}</div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Leads e fundit</CardTitle>
                </CardHeader>
                <CardContent>
                    <p v-if="recentLeads.length === 0" class="py-6 text-center text-sm text-muted-foreground">Ende nuk ka leads.</p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="lead in recentLeads"
                            :key="`${lead.type}-${lead.id}`"
                            class="flex flex-wrap items-center justify-between gap-2 py-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ lead.name }}</p>
                                <p class="text-sm text-muted-foreground">{{ lead.phone }}</p>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="rounded-full bg-muted px-2 py-0.5 text-xs">{{ typeLabels[lead.type] }}</span>
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs"
                                    :class="lead.status === 'new' ? 'bg-primary text-primary-foreground' : 'bg-muted'"
                                >
                                    {{ statusLabels[lead.status] ?? lead.status }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ formatDate(lead.created_at) }}</span>
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
