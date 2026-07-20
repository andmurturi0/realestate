<script setup lang="ts">
import { type PaginationLink } from '@/lib/listing';
import { paginationLabel } from '@/lib/pagination';
import { useTranslations } from '@/lib/trans';

defineProps<{
    links: PaginationLink[];
}>();

const emit = defineEmits<{
    (e: 'navigate', url: string): void;
}>();

const { t } = useTranslations();

function label(link: PaginationLink): string {
    return paginationLabel(link.label, t('Prapa'), t('Para'));
}
</script>

<template>
    <!-- 3 links = prev + one page + next: nothing to paginate. -->
    <nav v-if="links.length > 3" class="flex flex-wrap items-center justify-center gap-1" :aria-label="t('Paginimi')">
        <button
            v-for="(link, index) in links"
            :key="index"
            type="button"
            class="min-w-9 rounded-md border px-3 py-1.5 text-sm transition-colors"
            :class="[
                link.active ? 'border-primary bg-primary text-primary-foreground' : 'bg-background hover:bg-accent hover:text-accent-foreground',
                link.url === null ? 'cursor-not-allowed opacity-40' : '',
            ]"
            :disabled="link.url === null"
            :aria-current="link.active ? 'page' : undefined"
            @click="link.url && emit('navigate', link.url)"
        >
            {{ label(link) }}
        </button>
    </nav>
</template>
