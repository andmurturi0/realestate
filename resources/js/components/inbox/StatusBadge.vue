<script setup lang="ts">
import { leadStatusClasses } from '@/lib/inbox';

defineProps<{
    status: string;
    label: string;
}>();
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="leadStatusClasses[status] ?? 'bg-muted'">
        {{ label }}
    </span>
</template>
