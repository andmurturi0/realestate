<script setup lang="ts">
import PropertyForm, { type FeatureOption, type MunicipalityOption } from '@/components/properties/PropertyForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    municipalities: MunicipalityOption[];
    features: Record<string, FeatureOption[]>;
    agents: { id: number; name: string }[] | null;
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
                <h1 class="text-xl font-semibold">Pronë e re</h1>
                <p class="mt-1 text-sm text-muted-foreground">Fotot shtohen pas ruajtjes së pronës.</p>
            </div>

            <PropertyForm :municipalities="municipalities" :features="features" :agents="agents" @submit="submit">
                <template #submit-label>Krijo pronën</template>
            </PropertyForm>
        </div>
    </AppLayout>
</template>
