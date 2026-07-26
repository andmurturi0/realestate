<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useTranslations } from '@/lib/trans';
import { Head } from '@inertiajs/vue3';

defineProps<{
    content: string | null;
}>();

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('Termat dhe Kushtet')" />

    <PublicLayout show-brand-pattern>
        <div class="relative z-10 mx-auto w-full max-w-screen-2xl flex-1 px-4 py-8 pt-10 md:py-12 md:pt-14">
            <h1 class="text-display-lg font-medium">{{ t('Termat dhe Kushtet') }}</h1>

            <div v-if="content" class="prose prose-sm dark:prose-invert mt-8 max-w-3xl" v-html="content" />
            <p v-else class="mt-8 max-w-3xl text-muted-foreground">{{ t('Përmbajtja do të shtohet së shpejti.') }}</p>
        </div>
    </PublicLayout>
</template>
