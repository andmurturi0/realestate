<script setup lang="ts">
import PropertyForm, { type FeatureOption, type MunicipalityOption, type PropertyFormPayload } from '@/components/properties/PropertyForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    offer: { id: number; name: string };
    property: Partial<PropertyFormPayload>;
    municipalities: MunicipalityOption[];
    features: Record<string, FeatureOption[]>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ofertat', href: '/dashboard/inbox/offers' },
    { title: `Krijo pronë nga ${props.offer.name}`, href: `/dashboard/inbox/offers/${props.offer.id}/convert` },
];

const submit = (form: { post: (url: string) => void }) => {
    form.post(route('dashboard.inbox.offers.convert.store', props.offer.id));
};
</script>

<template>
    <Head title="Krijo pronë nga oferta" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-xl font-medium">Krijo pronë nga oferta e {{ offer.name }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Titulli dhe çmimi janë parambushur nga oferta — mund t'i ndryshoni lirisht para se të ruani. Fotot shtohen pas ruajtjes.
                </p>
            </div>

            <PropertyForm
                :municipalities="municipalities"
                :features="features"
                :agents="null"
                :property="property as PropertyFormPayload"
                @submit="submit"
            >
                <template #submit-label>Krijo pronën</template>
            </PropertyForm>
        </div>
    </AppLayout>
</template>
