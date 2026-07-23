<script setup lang="ts">
import RequestForm from '@/components/site/RequestForm.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { type LocationOption } from '@/lib/listing';
import { useTranslations } from '@/lib/trans';
import { Head } from '@inertiajs/vue3';
import { ClipboardCheck, MessageCircle, Search } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps<{
    municipalities: LocationOption[];
}>();

const { t } = useTranslations();

const steps = computed(() => [
    {
        icon: Search,
        title: t('Kërkimi'),
        description: t('Agjentët tanë kërkojnë prona që përputhen me kriteret tuaja.'),
    },
    {
        icon: ClipboardCheck,
        title: t('Përputhjet'),
        description: t('Sapo gjendet një pronë përputhëse, ju njoftojmë menjëherë.'),
    },
    {
        icon: MessageCircle,
        title: t('Ju kontaktojmë'),
        description: t('Një agjent ju kontakton për të organizuar një vizitë.'),
    },
]);
</script>

<template>
    <Head :title="t('Krijoni Kërkesën Tuaj')" />

    <PublicLayout show-brand-pattern>
        <div class="relative z-10 mx-auto w-full max-w-2xl px-4 py-10 pt-10 md:pt-14">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-medium">{{ t('Krijoni Kërkesën Tuaj') }}</h1>
                <p class="mt-2 text-muted-foreground">{{ t("Na tregoni çfarë kërkoni dhe do t'ju njoftojmë sapo gjejmë një pronë përputhëse.") }}</p>
            </div>

            <RequestForm :municipalities="municipalities" />

            <div class="mt-8 rounded-xl border bg-card p-6">
                <h2 class="mb-4 font-medium">{{ t('Çfarë ndodh pas dërgimit të kërkesës?') }}</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div v-for="step in steps" :key="step.title" class="flex flex-col items-start gap-2">
                        <div class="flex size-9 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <component :is="step.icon" class="size-5" />
                        </div>
                        <p class="text-sm font-medium">{{ step.title }}</p>
                        <p class="text-xs text-muted-foreground">{{ step.description }}</p>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
