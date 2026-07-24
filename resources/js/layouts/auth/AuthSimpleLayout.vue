<script setup lang="ts">
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import { isDarkTheme } from '@/composables/useAppearance';
import { useBrandColor } from '@/composables/useBrandColor';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

useBrandColor();

const page = usePage<SharedData>();
const settings = computed(() => page.props.settings);
const logoUrl = computed(() => (isDarkTheme.value ? settings.value.logo_dark_url || settings.value.logo_url : settings.value.logo_url));

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative flex min-h-svh flex-col bg-background px-6 py-10 md:p-10">
        <div class="absolute right-6 top-6 md:right-10 md:top-10">
            <AppearanceToggle :labels="{ light: 'E çelët', dark: 'E errët', system: 'Sistemi', toggle: 'Ndrysho pamjen' }" />
        </div>

        <div class="flex flex-1 flex-col items-center justify-center">
            <div class="w-full max-w-sm">
                <Link :href="route('home')" class="mb-8 flex flex-col items-center gap-3 text-center">
                    <img v-if="logoUrl" :src="logoUrl" :alt="settings.agency_name ?? ''" class="h-12 w-auto object-contain" />
                    <span class="text-lg font-medium">{{ settings.agency_name }}</span>
                </Link>

                <div class="rounded-xl border bg-card p-8 shadow-card">
                    <div v-if="title || description" class="mb-6 space-y-1 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
                    </div>
                    <slot />
                </div>

                <slot name="footer" />
            </div>
        </div>
    </div>
</template>
