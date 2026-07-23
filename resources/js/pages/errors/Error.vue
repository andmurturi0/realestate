<script setup lang="ts">
import { isDarkTheme } from '@/composables/useAppearance';
import { publicRoute } from '@/lib/route';
import { useTranslations } from '@/lib/trans';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    status: number;
}>();

const { t } = useTranslations();
const page = usePage<SharedData>();
const logoUrl = computed(() =>
    isDarkTheme.value ? page.props.settings.logo_dark_url || page.props.settings.logo_url : page.props.settings.logo_url,
);

const copy: Record<number, { title: string; message: string }> = {
    404: { title: t('Faqja nuk u gjet'), message: t('Faqja që kërkoni nuk ekziston ose është zhvendosur.') },
    403: { title: t('Nuk keni qasje'), message: t('Nuk keni leje për ta parë këtë faqe.') },
    500: { title: t('Diçka shkoi keq'), message: t('Ndodhi një gabim i papritur. Ju lutemi provoni përsëri më vonë.') },
    503: { title: t('Në mirëmbajtje'), message: t('Faqja është përkohësisht e paarritshme. Ju lutemi provoni përsëri së shpejti.') },
};

const fallback = { title: t('Diçka shkoi keq'), message: t('Ndodhi një gabim i papritur.') };

const title = computed(() => (copy[props.status] ?? fallback).title);
const message = computed(() => (copy[props.status] ?? fallback).message);
</script>

<template>
    <Head :title="`${status} — ${title}`" />

    <div class="flex min-h-screen flex-col items-center justify-center gap-5 bg-background px-4 text-center">
        <img v-if="logoUrl" :src="logoUrl" :alt="page.props.settings.agency_name ?? ''" class="h-10 w-auto object-contain" />
        <span v-else class="text-lg font-medium">{{ page.props.settings.agency_name }}</span>

        <p class="text-sm font-medium text-muted-foreground">{{ status }}</p>
        <h1 class="text-display-md font-medium text-foreground">{{ title }}</h1>
        <p class="max-w-md text-muted-foreground">{{ message }}</p>

        <Link
            :href="page.props.auth.user ? route('dashboard') : publicRoute('home')"
            class="mt-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90"
        >
            {{ t('Kthehu në ballina') }}
        </Link>
    </div>
</template>
