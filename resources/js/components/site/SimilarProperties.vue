<script setup lang="ts">
import PropertyCard from '@/components/site/PropertyCard.vue';
import { type PropertyCardData } from '@/lib/listing';
import { useTranslations } from '@/lib/trans';

defineProps<{
    properties: PropertyCardData[];
}>();

const { t } = useTranslations();
</script>

<template>
    <div v-if="properties.length">
        <h2 class="mb-4 text-lg font-medium">{{ t('Prona të ngjashme') }}</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <PropertyCard v-for="property in properties" :key="property.id" :property="property" />
        </div>
    </div>
</template>
