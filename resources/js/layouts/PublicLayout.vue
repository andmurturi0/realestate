<script setup lang="ts">
import { useBrandColor } from '@/composables/useBrandColor';
import { useFavourites } from '@/composables/useFavourites';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Heart } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();
const settings = computed(() => page.props.settings);

useBrandColor();
const favourites = useFavourites();

const navLinks = computed(() => [
    { label: 'Ballina', href: route('home'), active: page.url === '/' },
    { label: 'Pronat', href: route('properties.index'), active: page.url.startsWith('/properties') },
]);

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
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
                <div class="flex items-center gap-8">
                    <Link href="/" class="flex items-center gap-2">
                        <img v-if="settings.logo_url" :src="settings.logo_url" :alt="settings.agency_name ?? ''" class="h-9 w-auto object-contain" />
                        <span class="text-lg font-semibold">{{ settings.agency_name }}</span>
                    </Link>
                    <nav class="hidden items-center gap-6 text-sm sm:flex">
                        <Link
                            v-for="link in navLinks"
                            :key="link.label"
                            :href="link.href"
                            class="transition-colors"
                            :class="link.active ? 'font-medium text-foreground' : 'text-muted-foreground hover:text-foreground'"
                        >
                            {{ link.label }}
                        </Link>
                    </nav>
                </div>
                <div class="flex items-center gap-4 text-sm text-muted-foreground">
                    <a v-if="settings.phone" :href="`tel:${settings.phone}`" class="hidden hover:text-foreground sm:inline">
                        {{ settings.phone }}
                    </a>
                    <Link :href="route('favorites.index')" class="relative flex items-center hover:text-foreground" aria-label="Të preferuarat">
                        <Heart class="size-5" />
                        <span
                            v-if="favourites.count.value > 0"
                            class="absolute -right-2 -top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground"
                        >
                            {{ favourites.count.value }}
                        </span>
                    </Link>
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="rounded-md bg-primary px-3 py-1.5 text-primary-foreground">
                        Dashboard
                    </Link>
                    <Link v-else :href="route('login')" class="rounded-md bg-primary px-3 py-1.5 text-primary-foreground">Kyçu</Link>
                </div>
            </div>
        </header>

        <main class="flex flex-1 flex-col">
            <slot />
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
