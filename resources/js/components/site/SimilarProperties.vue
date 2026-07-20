<script setup lang="ts">
import PropertyCard from '@/components/site/PropertyCard.vue';
import { type PropertyCardData } from '@/lib/listing';

defineProps<{
    properties: PropertyCardData[];
}>();
</script>

<template>
    <div v-if="properties.length">
        <h2 class="mb-4 text-lg font-semibold">Prona të ngjashme</h2>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <PropertyCard v-for="property in properties" :key="property.id" :property="property" />
        </div>
    </div>
</template>
