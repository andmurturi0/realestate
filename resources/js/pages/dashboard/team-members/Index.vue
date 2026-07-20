<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { GripVertical, Pencil, Plus, Trash2, UserRound } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import draggable from 'vuedraggable';

interface TeamMemberRow {
    id: number;
    name: string;
    position: string;
    photo_url: string | null;
    sort_order: number;
    is_active: boolean;
}

const props = defineProps<{
    teamMembers: TeamMemberRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Ekipi', href: '/dashboard/team-members' }];

const flashSuccess = computed(() => usePage<SharedData>().props.flash.success);

const ordered = ref<TeamMemberRow[]>([...props.teamMembers]);
watch(
    () => props.teamMembers,
    (teamMembers) => (ordered.value = [...teamMembers]),
);

const persistOrder = () => {
    router.put(
        route('dashboard.team-members.reorder'),
        { order: ordered.value.map((teamMember) => teamMember.id) },
        { preserveScroll: true, preserveState: true },
    );
};

const toDelete = ref<TeamMemberRow | null>(null);

const confirmDelete = () => {
    if (!toDelete.value) return;

    router.delete(route('dashboard.team-members.destroy', toDelete.value.id), {
        preserveScroll: true,
        onFinish: () => (toDelete.value = null),
    });
};
</script>

<template>
    <Head title="Ekipi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-medium">Ekipi</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Shfaqen te faqja "Rreth nesh", vetëm ata aktivë, sipas renditjes.</p>
                </div>
                <Button as-child>
                    <Link :href="route('dashboard.team-members.create')">
                        <Plus class="size-4" />
                        Shto anëtar
                    </Link>
                </Button>
            </div>

            <div
                v-if="flashSuccess"
                class="rounded-md border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200"
            >
                {{ flashSuccess }}
            </div>

            <div v-if="ordered.length === 0" class="rounded-lg border border-dashed px-6 py-12 text-center text-muted-foreground">
                Nuk ka ende asnjë anëtar.
            </div>

            <draggable v-else v-model="ordered" item-key="id" class="flex flex-col gap-2" ghost-class="opacity-40" @end="persistOrder">
                <template #item="{ element }">
                    <div class="flex items-center gap-3 rounded-lg border bg-card px-3 py-2.5">
                        <GripVertical class="size-4 shrink-0 cursor-grab text-muted-foreground" />

                        <img v-if="element.photo_url" :src="element.photo_url" :alt="element.name" class="size-10 shrink-0 rounded-full object-cover" />
                        <div v-else class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted">
                            <UserRound class="size-4 text-muted-foreground" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ element.name }}</p>
                            <p class="truncate text-sm text-muted-foreground">{{ element.position }}</p>
                        </div>

                        <span
                            class="shrink-0 rounded-full px-2 py-0.5 text-xs"
                            :class="element.is_active ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300' : 'bg-muted text-muted-foreground'"
                        >
                            {{ element.is_active ? 'Aktiv' : 'Joaktiv' }}
                        </span>

                        <div class="flex shrink-0 gap-1">
                            <Button variant="ghost" size="icon" as-child>
                                <Link :href="route('dashboard.team-members.edit', element.id)" :aria-label="`Edito ${element.name}`">
                                    <Pencil class="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="text-destructive hover:text-destructive"
                                :aria-label="`Fshi ${element.name}`"
                                @click="toDelete = element"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                </template>
            </draggable>
        </div>

        <Dialog :open="toDelete !== null" @update:open="(open) => !open && (toDelete = null)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Fshi anëtarin {{ toDelete?.name }}?</DialogTitle>
                    <DialogDescription>Ky veprim nuk mund të zhbëhet.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="toDelete = null">Anulo</Button>
                    <Button variant="destructive" @click="confirmDelete">Fshije</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
