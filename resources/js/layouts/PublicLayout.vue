<script setup lang="ts">
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import BrandPattern from '@/components/site/BrandPattern.vue';
import LanguageSwitcher from '@/components/site/LanguageSwitcher.vue';
import { isDarkTheme } from '@/composables/useAppearance';
import { useBrandColor } from '@/composables/useBrandColor';
import { useFavourites } from '@/composables/useFavourites';
import { publicRoute } from '@/lib/route';
import { useTranslations } from '@/lib/trans';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Heart, Menu, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

withDefaults(defineProps<{ showBrandPattern?: boolean }>(), { showBrandPattern: false });

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

const logoUrl = computed(() => (isDarkTheme.value ? settings.value.logo_dark_url || settings.value.logo_url : settings.value.logo_url));

// ---- Mobile nav menu -------------------------------------------------------

const mobileMenuOpen = ref(false);

function closeMobileMenu() {
    mobileMenuOpen.value = false;
}

function onMobileMenuKeydown(event: KeyboardEvent) {
    if (mobileMenuOpen.value && event.key === 'Escape') {
        closeMobileMenu();
    }
}

onMounted(() => window.addEventListener('keydown', onMobileMenuKeydown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onMobileMenuKeydown);
    document.body.style.overflow = '';
});

watch(mobileMenuOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

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
        <header class="sticky top-0 z-30 bg-background shadow-hover">
            <div class="mx-auto flex max-w-screen-2xl items-center justify-between gap-4 px-4 py-4">
                <div class="flex items-center gap-8">
                    <Link :href="publicRoute('home')" class="flex items-center gap-2">
                        <img v-if="logoUrl" :src="logoUrl" :alt="settings.agency_name ?? ''" class="h-12 w-auto object-contain" />
                        <span v-else class="text-lg font-medium">{{ settings.agency_name }}</span>
                    </Link>
                    <nav class="hidden items-center gap-6 text-sm lg:flex">
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
                    <div class="hidden items-center gap-4 lg:flex">
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
                    </div>
                    <button
                        type="button"
                        class="inline-flex size-9 shrink-0 items-center justify-center rounded-md text-foreground hover:bg-accent lg:hidden"
                        :aria-label="t('Hap menynë')"
                        :aria-expanded="mobileMenuOpen"
                        @click="mobileMenuOpen = true"
                    >
                        <Menu class="size-5" />
                    </button>
                </div>
            </div>
        </header>

        <!-- Mobile nav menu: same links/controls as the desktop header, presented as a slide-in drawer below lg. -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-200"
                leave-to-class="opacity-0"
            >
                <div v-if="mobileMenuOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="closeMobileMenu" />
            </Transition>
            <Transition
                enter-active-class="transition-transform duration-300 ease-out"
                enter-from-class="translate-x-full"
                leave-active-class="transition-transform duration-200 ease-in"
                leave-to-class="translate-x-full"
            >
                <div
                    v-if="mobileMenuOpen"
                    class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xs flex-col overflow-y-auto border-l bg-card shadow-2xl lg:hidden"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="t('Hap menynë')"
                >
                    <div class="flex items-center justify-between border-b p-4">
                        <Link :href="publicRoute('home')" class="flex items-center gap-2" @click="closeMobileMenu">
                            <img v-if="logoUrl" :src="logoUrl" :alt="settings.agency_name ?? ''" class="h-9 w-auto object-contain" />
                            <span v-else class="text-base font-medium">{{ settings.agency_name }}</span>
                        </Link>
                        <button
                            type="button"
                            class="rounded-full p-1.5 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                            :aria-label="t('Mbyll')"
                            @click="closeMobileMenu"
                        >
                            <X class="size-5" />
                        </button>
                    </div>

                    <nav class="flex flex-col gap-1 p-4 text-sm">
                        <Link
                            v-for="link in navLinks"
                            :key="link.label"
                            :href="link.href"
                            class="rounded-md px-3 py-2.5 transition-colors"
                            :class="
                                link.active ? 'bg-accent font-medium text-foreground' : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                            "
                            @click="closeMobileMenu"
                        >
                            {{ link.label }}
                        </Link>
                    </nav>

                    <div class="mt-auto flex flex-col gap-4 border-t p-4 text-sm text-muted-foreground">
                        <LanguageSwitcher inline />

                        <div class="flex items-center justify-between gap-4">
                            <AppearanceToggle
                                :labels="{ light: t('E çelët'), dark: t('E errët'), system: t('Sistemi'), toggle: t('Ndrysho pamjen') }"
                            />
                            <Link
                                :href="publicRoute('favorites.index')"
                                class="relative flex items-center hover:text-foreground"
                                :aria-label="t('Të preferuarat')"
                                @click="closeMobileMenu"
                            >
                                <Heart class="size-5" />
                                <span
                                    v-if="favourites.count.value > 0"
                                    class="absolute -right-2 -top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-medium text-primary-foreground"
                                >
                                    {{ favourites.count.value }}
                                </span>
                            </Link>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <main class="relative flex flex-1 flex-col">
            <BrandPattern v-if="showBrandPattern" />
            <slot />
        </main>

        <footer class="border-t">
            <div class="mx-auto max-w-screen-2xl px-4 py-10">
                <div class="grid grid-cols-2 gap-8 text-sm sm:grid-cols-3">
                    <nav class="flex flex-col gap-2">
                        <Link
                            v-for="link in navLinks"
                            :key="link.label"
                            :href="link.href"
                            class="text-muted-foreground transition-colors hover:text-foreground"
                        >
                            {{ link.label }}
                        </Link>
                    </nav>
                    <nav class="flex flex-col gap-2">
                        <Link :href="publicRoute('terms')" class="text-muted-foreground transition-colors hover:text-foreground">
                            {{ t('Termat dhe Kushtet') }}
                        </Link>
                        <Link :href="publicRoute('privacy')" class="text-muted-foreground transition-colors hover:text-foreground">
                            {{ t('Politika e Privatësisë') }}
                        </Link>
                    </nav>
                    <div class="col-span-2 flex flex-col gap-2 sm:col-span-1">
                        <a v-if="settings.phone" :href="`tel:${settings.phone}`" class="text-muted-foreground transition-colors hover:text-foreground">
                            {{ settings.phone }}
                        </a>
                        <a
                            v-if="settings.email"
                            :href="`mailto:${settings.email}`"
                            class="text-muted-foreground transition-colors hover:text-foreground"
                        >
                            {{ settings.email }}
                        </a>
                    </div>
                </div>

                <div
                    class="mt-8 flex flex-col items-center justify-between gap-2 border-t pt-6 text-sm text-muted-foreground sm:flex-row"
                >
                    <Link :href="$page.props.auth.user ? route('dashboard') : route('login')" class="transition-colors hover:text-foreground">
                        © {{ new Date().getFullYear() }} {{ settings.agency_name }}
                    </Link>
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
            </div>
        </footer>
    </div>
</template>
