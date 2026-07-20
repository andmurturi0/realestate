<script setup lang="ts">
import PropertyForm, { type FeatureOption, type MunicipalityOption, type PropertyFormPayload } from '@/components/properties/PropertyForm.vue';
import PropertyImageManager, { type PropertyImageItem } from '@/components/properties/PropertyImageManager.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    property: PropertyFormPayload;
    municipalities: MunicipalityOption[];
    features: Record<string, FeatureOption[]>;
    agents: { id: number; name: string }[] | null;
    images: PropertyImageItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pronat', href: '/dashboard/properties' },
    { title: props.property.reference_code, href: `/dashboard/properties/${props.property.id}/edit` },
];

const flashSuccess = computed(() => usePage<SharedData>().props.flash.success);

const submit = (form: { put: (url: string, options?: { preserveScroll: boolean }) => void }) => {
    form.put(route('dashboard.properties.update', props.property.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Edito ${property.reference_code}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-xl font-semibold">Edito pronën {{ property.reference_code }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">{{ property.title.sq }}</p>
            </div>

            <div
                v-if="flashSuccess"
                class="rounded-md border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200"
            >
                {{ flashSuccess }}
            </div>

            <PropertyImageManager :property-id="property.id" :images="images" />

            <PropertyForm :municipalities="municipalities" :features="features" :agents="agents" :property="property" @submit="submit">
                <template #submit-label>Ruaj ndryshimet</template>
            </PropertyForm>
        </div>
    </AppLayout>
</template>
