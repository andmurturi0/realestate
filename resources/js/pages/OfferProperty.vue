<script setup lang="ts">
import OfferForm from '@/components/site/OfferForm.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { type LocationOption } from '@/lib/listing';
import { Head } from '@inertiajs/vue3';
import { ClipboardCheck, MessageCircle, PhoneCall } from 'lucide-vue-next';

defineProps<{
    municipalities: LocationOption[];
}>();

const steps = [
    {
        icon: PhoneCall,
        title: 'Ju kontaktojmë',
        description: "Një agjent do t'ju telefonojë brenda 24 orëve për të diskutuar detajet e pronës suaj.",
    },
    {
        icon: ClipboardCheck,
        title: 'Vlerësimi',
        description: 'Agjenti vlerëson pronën dhe ju propozon çmimin më të mirë të tregut.',
    },
    {
        icon: MessageCircle,
        title: 'Publikimi',
        description: 'Pas marrëveshjes, prona publikohet me foto dhe përshkrim profesional.',
    },
];
</script>

<template>
    <Head title="Ofertoni Pronën Tuaj" />

    <PublicLayout>
        <div class="mx-auto w-full max-w-2xl px-4 py-10">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-semibold">Ofertoni Pronën Tuaj</h1>
                <p class="mt-2 text-muted-foreground">Plotësoni formularin më poshtë dhe një agjent do t'ju kontaktojë së shpejti.</p>
            </div>

            <OfferForm :municipalities="municipalities" />

            <div class="mt-8 rounded-xl border bg-card p-6">
                <h2 class="mb-4 font-semibold">Çfarë ndodh pas dërgimit të formularit?</h2>
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
