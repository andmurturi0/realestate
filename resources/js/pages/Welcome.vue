<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const settings = computed(() => usePage<SharedData>().props.settings);
</script>

<template>
    <Head :title="settings.agency_name ?? ''" />

    <PublicLayout>
        <!-- The real public homepage is built in a later phase (FAZAT.md). -->
        <div class="flex flex-1 items-center justify-center px-4">
            <div class="max-w-xl py-16 text-center">
                <h1 class="text-3xl font-semibold">{{ settings.agency_name }}</h1>
                <p v-if="settings.address" class="mt-3 text-muted-foreground">{{ settings.address }}</p>
                <p v-if="settings.email" class="mt-1 text-muted-foreground">{{ settings.email }}</p>
            </div>
        </div>
    </PublicLayout>
</template>
