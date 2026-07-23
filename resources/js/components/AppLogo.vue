<script setup lang="ts">
import { isDarkTheme } from '@/composables/useAppearance';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';
import { computed } from 'vue';

const settings = computed(() => usePage<SharedData>().props.settings);
const logoUrl = computed(() => (isDarkTheme.value ? settings.value.logo_dark_url || settings.value.logo_url : settings.value.logo_url));
</script>

<template>
    <img v-if="logoUrl" :src="logoUrl" :alt="settings.agency_name ?? ''" class="aspect-square size-8 rounded-md object-contain" />
    <div v-else class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
        <Building2 class="size-5" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate font-medium leading-none">{{ settings.agency_name }}</span>
    </div>
</template>
