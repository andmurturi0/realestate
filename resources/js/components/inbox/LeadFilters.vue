<script setup lang="ts">
import { type AgentOption, type LeadFilterState } from '@/lib/inbox';

defineProps<{
    agents: AgentOption[] | null;
    statusOptions: Record<string, string>;
}>();

const filters = defineModel<LeadFilterState>('filters', { required: true });

const emit = defineEmits<{
    (e: 'apply'): void;
    (e: 'apply-debounced'): void;
}>();

const inputClass =
    'h-9 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
</script>

<template>
    <div class="flex flex-wrap items-center gap-2 rounded-lg border bg-card p-3">
        <input
            id="lead-search"
            v-model="filters.search"
            type="text"
            name="search"
            placeholder="Kërko sipas emrit ose telefonit…"
            :class="[inputClass, 'w-56']"
            @input="emit('apply-debounced')"
        />

        <select id="lead-status" v-model="filters.status" name="status" :class="inputClass" @change="emit('apply')">
            <option value="">Të gjitha statuset</option>
            <option v-for="(label, value) in statusOptions" :key="value" :value="value">{{ label }}</option>
        </select>

        <select v-if="agents" id="lead-assigned-to" v-model="filters.assigned_to" name="assigned_to" :class="inputClass" @change="emit('apply')">
            <option value="">Të gjithë agjentët</option>
            <option v-for="agent in agents" :key="agent.id" :value="agent.id">{{ agent.name }}</option>
        </select>

        <input id="lead-date-from" v-model="filters.date_from" type="date" name="date_from" :class="inputClass" @change="emit('apply')" />
        <span class="text-sm text-muted-foreground">–</span>
        <input id="lead-date-to" v-model="filters.date_to" type="date" name="date_to" :class="inputClass" @change="emit('apply')" />
    </div>
</template>
