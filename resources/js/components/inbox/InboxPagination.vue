<script setup lang="ts">
import { type PaginationLink } from '@/lib/listing';
import { paginationLabel } from '@/lib/pagination';
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}>();
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted-foreground">Duke shfaqur {{ from ?? 0 }}–{{ to ?? 0 }} nga {{ total }}</p>
        <nav class="flex flex-wrap gap-1">
            <template v-for="(link, index) in links" :key="index">
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
</template>
