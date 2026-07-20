<script setup lang="ts">
import PropertyForm, { type FeatureOption, type MunicipalityOption } from '@/components/properties/PropertyForm.vue';
import PropertyImageManager, { type PropertyImageItem } from '@/components/properties/PropertyImageManager.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    municipalities: MunicipalityOption[];
    features: Record<string, FeatureOption[]>;
    agents: { id: number; name: string }[] | null;
    pendingImages: PropertyImageItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pronat', href: '/dashboard/properties' },
    { title: 'Pronë e re', href: '/dashboard/properties/create' },
];

const submit = (form: { post: (url: string) => void }) => {
    form.post(route('dashboard.properties.store'));
};
</script>

<template>
    <Head title="Pronë e re" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-xl font-medium">Pronë e re</h1>
                <p class="mt-1 text-sm text-muted-foreground">Fotot e ngarkuara i bashkëngjiten pronës kur ta ruash.</p>
            </div>

            <PropertyImageManager :images="pendingImages" />

            <PropertyForm :municipalities="municipalities" :features="features" :agents="agents" @submit="submit">
                <template #submit-label>Krijo pronën</template>
            </PropertyForm>
        </div>
    </AppLayout>
</template>
