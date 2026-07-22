<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Power, Trash2, UserRound } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface AgentRow {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: 'admin' | 'agent';
    avatar_url: string | null;
    is_active: boolean;
    properties_count: number;
    leads_count: number;
}

const props = defineProps<{
    users: AgentRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Agjentët', href: '/dashboard/agents' }];

const page = usePage<SharedData>();
const flashSuccess = computed(() => page.props.flash.success);
const currentUserId = computed(() => page.props.auth.user.id);

const roleLabels: Record<AgentRow['role'], string> = {
    admin: 'Admin',
    agent: 'Agjent',
};

const toggleActive = (user: AgentRow) => {
    router.patch(route('dashboard.agents.toggle-active', user.id), {}, { preserveScroll: true });
};

const toDelete = ref<AgentRow | null>(null);

const confirmDelete = () => {
    if (!toDelete.value) return;

    router.delete(route('dashboard.agents.destroy', toDelete.value.id), {
        preserveScroll: true,
        onFinish: () => (toDelete.value = null),
    });
};
</script>

<template>
    <Head title="Agjentët" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-w-0 flex-1 flex-col gap-4 overflow-x-hidden p-4 [contain:inline-size]">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-medium">Agjentët</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{{ props.users.length }} përdorues gjithsej</p>
                </div>
                <Button as-child>
                    <Link :href="route('dashboard.agents.create')">
                        <Plus class="size-4" />
                        Shto agjent
                    </Link>
                </Button>
            </div>

            <div
                v-if="flashSuccess"
                class="rounded-md border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200"
            >
                {{ flashSuccess }}
            </div>

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                            <th class="px-3 py-2 font-medium">Agjenti</th>
                            <th class="px-3 py-2 font-medium">Roli</th>
                            <th class="px-3 py-2 font-medium">Telefoni</th>
                            <th class="px-3 py-2 font-medium">Prona aktive</th>
                            <th class="px-3 py-2 font-medium">Lidhje të caktuara</th>
                            <th class="px-3 py-2 font-medium">Statusi</th>
                            <th class="px-3 py-2 text-right font-medium">Veprime</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="props.users.length === 0">
                            <td colspan="7" class="px-3 py-12 text-center text-muted-foreground">Nuk ka ende asnjë përdorues.</td>
                        </tr>
                        <tr v-for="user in props.users" :key="user.id" class="border-b last:border-0 hover:bg-muted/30">
                            <td class="px-3 py-2">
                                <div class="flex min-w-0 items-center gap-3">
                                    <img
                                        v-if="user.avatar_url"
                                        :src="user.avatar_url"
                                        :alt="user.name"
                                        class="size-9 shrink-0 rounded-full object-cover"
                                    />
                                    <div v-else class="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted">
                                        <UserRound class="size-4 text-muted-foreground" />
                                    </div>
                                    <div class="w-0 min-w-0">
                                        <p class="truncate font-medium">{{ user.name }}</p>
                                        <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <span class="rounded-full bg-muted px-2 py-0.5 text-xs">{{ roleLabels[user.role] }}</span>
                            </td>
                            <td class="px-3 py-2">{{ user.phone ?? '—' }}</td>
                            <td class="px-3 py-2 tabular-nums">{{ user.properties_count }}</td>
                            <td class="px-3 py-2 tabular-nums">{{ user.leads_count }}</td>
                            <td class="px-3 py-2">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs"
                                    :class="
                                        user.is_active
                                            ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ user.is_active ? 'Aktiv' : 'Joaktiv' }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="route('dashboard.agents.edit', user.id)" :aria-label="`Edito ${user.name}`">
                                            <Pencil class="size-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="user.id !== currentUserId"
                                        variant="ghost"
                                        size="icon"
                                        :aria-label="user.is_active ? `Çaktivizo ${user.name}` : `Aktivizo ${user.name}`"
                                        @click="toggleActive(user)"
                                    >
                                        <Power class="size-4" />
                                    </Button>
                                    <Button
                                        v-if="user.id !== currentUserId"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        :aria-label="`Fshi ${user.name}`"
                                        @click="toDelete = user"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Dialog :open="toDelete !== null" @update:open="(open) => !open && (toDelete = null)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Fshi {{ toDelete?.name }}?</DialogTitle>
                    <DialogDescription>
                        Pronat dhe lidhjet e caktuara për këtë përdorues do t'i kalojnë ty ({{ page.props.auth.user.name }}) dhe do t'i mund t'i
                        rikthesh te një agjent tjetër më vonë. Shënimet e lidhjeve të tij do të fshihen. Ky veprim nuk mund të zhbëhet.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="toDelete = null">Anulo</Button>
                    <Button variant="destructive" @click="confirmDelete">Fshije</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
