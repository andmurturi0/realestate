<script setup lang="ts">
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import LanguageSwitcher from '@/components/site/LanguageSwitcher.vue';
import { useBrandColor } from '@/composables/useBrandColor';
import { useFavourites } from '@/composables/useFavourites';
import { publicRoute } from '@/lib/route';
import { useTranslations } from '@/lib/trans';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Heart } from 'lucide-vue-next';
import { computed, watch } from 'vue';

const page = usePage<SharedData>();
const settings = computed(() => page.props.settings);
const { t } = useTranslations();

useBrandColor();
const favourites = useFavourites();

// <html lang> is set server-side on the initial load only — switching locale
// afterwards is a client-side Inertia visit that never re-renders app.blade.php,
// so without this it stays stuck on whatever locale first loaded the page.
watch(
    () => page.props.locale,
    (locale) => {
        document.documentElement.lang = locale;
    },
    { immediate: true },
);

// Strip the locale prefix before matching against page.url, so the "active"
// nav state works the same under /en and /de as it does unprefixed.
const currentPath = computed(() => page.url.replace(/^\/(en|de)(?=\/|$)/, '') || '/');

const navLinks = computed(() => [
    { label: t('Pronat'), href: publicRoute('properties.index'), active: currentPath.value === '/' || currentPath.value.startsWith('/properties') },
    { label: t('Rreth Nesh'), href: publicRoute('about'), active: currentPath.value.startsWith('/about') },
    { label: t('Ofro Pronën'), href: publicRoute('offer-property.create'), active: currentPath.value.startsWith('/offer-property') },
    { label: t('Bëj Kërkesë'), href: publicRoute('create-request.create'), active: currentPath.value.startsWith('/create-request') },
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
            <div class="mx-auto flex max-w-screen-2xl items-center justify-between gap-4 px-4 py-4">
                <div class="flex items-center gap-8">
                    <Link :href="publicRoute('home')" class="flex items-center gap-2">
                        <img v-if="settings.logo_url" :src="settings.logo_url" :alt="settings.agency_name ?? ''" class="h-9 w-auto object-contain" />
                        <span class="text-lg font-medium">{{ settings.agency_name }}</span>
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
                    <LanguageSwitcher />
                    <AppearanceToggle :labels="{ light: t('E çelët'), dark: t('E errët'), system: t('Sistemi'), toggle: t('Ndrysho pamjen') }" />
                    <Link
                        :href="publicRoute('favorites.index')"
                        class="relative flex items-center hover:text-foreground"
                        :aria-label="t('Të preferuarat')"
                    >
                        <Heart class="size-5" />
                        <span
                            v-if="favourites.count.value > 0"
                            class="absolute -right-2 -top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-medium text-primary-foreground"
                        >
                            {{ favourites.count.value }}
                        </span>
                    </Link>
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="rounded-md bg-primary px-3 py-1.5 text-primary-foreground">
                        {{ t('Paneli') }}
                    </Link>
                    <Link v-else :href="route('login')" class="rounded-md bg-primary px-3 py-1.5 text-primary-foreground">{{ t('Kyçu') }}</Link>
                </div>
            </div>
        </header>

        <main class="flex flex-1 flex-col">
            <slot />
        </main>

        <footer class="border-t">
            <div
                class="mx-auto flex max-w-screen-2xl flex-col items-center justify-between gap-2 px-4 py-6 text-sm text-muted-foreground sm:flex-row"
            >
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
