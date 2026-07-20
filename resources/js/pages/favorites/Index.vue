<script setup lang="ts">
import PropertyCard from '@/components/site/PropertyCard.vue';
import PropertyCardSkeleton from '@/components/site/PropertyCardSkeleton.vue';
import { useFavourites } from '@/composables/useFavourites';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { type PropertyCardData } from '@/lib/listing';
import { Head, Link } from '@inertiajs/vue3';
import { HeartOff } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const favourites = useFavourites();
const properties = ref<PropertyCardData[]>([]);
const loading = ref(true);

onMounted(async () => {
    if (favourites.ids.value.length === 0) {
        loading.value = false;

        return;
    }

    const params = new URLSearchParams();
    favourites.ids.value.forEach((id) => params.append('ids[]', String(id)));

    try {
        const response = await fetch(`${route('favorites.properties')}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        const body = (await response.json()) as { data: PropertyCardData[] };
        properties.value = body.data;

        // Ids that no longer resolve to a published property (deleted,
        // unpublished, etc.) are pruned from storage instead of lingering.
        favourites.ids.value = body.data.map((property) => property.id);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <Head title="Të preferuarat" />

    <PublicLayout>
        <div class="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
            <h1 class="mb-4 text-2xl font-semibold">Të preferuarat</h1>

            <div v-if="loading" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <PropertyCardSkeleton v-for="index in Math.min(favourites.ids.value.length || 3, 6)" :key="index" />
            </div>

            <div v-else-if="properties.length" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <PropertyCard v-for="property in properties" :key="property.id" :property="property" />
            </div>

            <div v-else class="flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-20 text-center">
                <div class="rounded-full bg-muted p-4">
                    <HeartOff class="size-8 text-muted-foreground" />
                </div>
                <h2 class="text-lg font-medium">Ende nuk keni prona të preferuara</h2>
                <p class="max-w-sm text-sm text-muted-foreground">
                    Shtyp ikonën e zemrës te një pronë për ta ruajtur këtu. Të preferuarat ruhen vetëm në këtë shfletues.
                </p>
                <Link
                    :href="route('properties.index')"
                    class="mt-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90"
                >
                    Shfleto pronat
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
