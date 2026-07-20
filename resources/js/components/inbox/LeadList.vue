<script setup lang="ts">
import StatusBadge from '@/components/inbox/StatusBadge.vue';
import { relativeTime } from '@/lib/inbox';

export interface LeadListItem {
    id: number;
    name: string;
    /** The row's second line — a snippet, or a category/price summary. */
    secondary: string;
    status: string;
    statusLabel: string;
    assigned_agent: string | null;
    created_at: string | null;
}

defineProps<{
    items: LeadListItem[];
    selectedId: number | null;
}>();

defineEmits<{
    (e: 'select', id: number): void;
}>();
</script>

<template>
    <ul class="divide-y overflow-hidden rounded-lg border">
        <li v-if="items.length === 0" class="p-6 text-center text-sm text-muted-foreground">Asnjë rezultat.</li>

        <li v-for="item in items" :key="item.id">
            <button
                type="button"
                class="flex w-full flex-col gap-1 px-4 py-3 text-left transition-colors hover:bg-accent"
                :class="[selectedId === item.id ? 'bg-accent' : '', item.status === 'new' ? 'bg-blue-50/50 dark:bg-blue-950/20' : '']"
                @click="$emit('select', item.id)"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="font-medium" :class="item.status === 'new' ? 'text-foreground' : 'text-foreground/90'">{{ item.name }}</span>
                    <span class="shrink-0 text-xs text-muted-foreground">{{ relativeTime(item.created_at) }}</span>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <span class="truncate text-sm text-muted-foreground">{{ item.secondary }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <StatusBadge :status="item.status" :label="item.statusLabel" />
                    <span v-if="item.assigned_agent" class="text-xs text-muted-foreground">→ {{ item.assigned_agent }}</span>
                    <span v-else class="text-xs italic text-muted-foreground">Pa agjent</span>
                </div>
            </button>
        </li>
    </ul>
</template>
