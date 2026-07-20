<script setup lang="ts">
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';
import { computed } from 'vue';

const settings = computed(() => usePage<SharedData>().props.settings);
</script>

<template>
    <img v-if="settings.logo_url" :src="settings.logo_url" :alt="settings.agency_name ?? ''" class="aspect-square size-8 rounded-md object-contain" />
    <div v-else class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
        <Building2 class="size-5" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate font-medium leading-none">{{ settings.agency_name }}</span>
    </div>
</template>
