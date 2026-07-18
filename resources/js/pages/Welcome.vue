<script setup lang="ts">
import { useBrandColor } from '@/composables/useBrandColor';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<SharedData>();
const settings = computed(() => page.props.settings);

useBrandColor();

const socialLinks = computed(() =>
    [
        { label: 'Facebook', href: settings.value.facebook },
        { label: 'Instagram', href: settings.value.instagram },
        { label: 'TikTok', href: settings.value.tiktok },
        { label: 'LinkedIn', href: settings.value.linkedin },
        { label: 'YouTube', href: settings.value.youtube },
    ].filter((link) => Boolean(link.href)),
);
</script>

<template>
    <Head :title="settings.agency_name ?? ''" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
                <Link href="/" class="flex items-center gap-2">
                    <img
                        v-if="settings.logo_url"
                        :src="settings.logo_url"
                        :alt="settings.agency_name ?? ''"
                        class="h-9 w-auto object-contain"
                    />
                    <span class="text-lg font-semibold">{{ settings.agency_name }}</span>
                </Link>
                <div class="flex items-center gap-4 text-sm text-muted-foreground">
                    <a v-if="settings.phone" :href="`tel:${settings.phone}`" class="hover:text-foreground">
                        {{ settings.phone }}
                    </a>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-md bg-primary px-3 py-1.5 text-primary-foreground"
                    >
                        Dashboard
                    </Link>
                    <Link v-else :href="route('login')" class="rounded-md bg-primary px-3 py-1.5 text-primary-foreground">Kyçu</Link>
                </div>
            </div>
        </header>

        <!-- The real public homepage is built in a later phase (FAZAT.md). -->
        <main class="flex flex-1 items-center justify-center px-4">
            <div class="max-w-xl py-16 text-center">
                <h1 class="text-3xl font-semibold">{{ settings.agency_name }}</h1>
                <p v-if="settings.address" class="mt-3 text-muted-foreground">{{ settings.address }}</p>
                <p v-if="settings.email" class="mt-1 text-muted-foreground">{{ settings.email }}</p>
            </div>
        </main>

        <footer class="border-t">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-6 text-sm text-muted-foreground sm:flex-row">
                <span>© {{ new Date().getFullYear() }} {{ settings.agency_name }}</span>
                <div v-if="socialLinks.length" class="flex gap-4">
                    <a
                        v-for="link in socialLinks"
                        :key="link.label"
                        :href="link.href as string"
                        target="_blank"
                        rel="noopener"
                        class="hover:text-foreground"
                    >
                        {{ link.label }}
                    </a>
                </div>
            </div>
        </footer>
    </div>
</template>
