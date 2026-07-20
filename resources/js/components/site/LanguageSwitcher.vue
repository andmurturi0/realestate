<script setup lang="ts">
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage<SharedData>();
const open = ref(false);
const root = ref<HTMLElement | null>(null);

// Language code, not a country flag — English isn't tied to Great Britain,
// and a GB flag next to "English" reads wrong for an audience outside the UK.
const localeMeta: Record<'sq' | 'en' | 'de', { code: string; name: string }> = {
    sq: { code: 'SQ', name: 'Shqip' },
    en: { code: 'EN', name: 'English' },
    de: { code: 'DE', name: 'Deutsch' },
};

function switchLocale(locale: 'sq' | 'en' | 'de'): void {
    open.value = false;

    if (locale === page.props.locale) {
        return;
    }

    // The unprefixed sq route has no URL segment to signal "explicitly sq"
    // versus "no preference, defer to the cookie" — so switching to sq would
    // otherwise keep resolving to whatever en/de the cookie still holds from
    // a previous switch. Writing it here first (SetLocale's cookie isn't
    // httpOnly) makes the target locale win regardless of prefix ambiguity.
    document.cookie = `locale=${locale}; path=/; max-age=${60 * 60 * 24 * 365}; SameSite=Lax`;

    const currentPath = window.location.pathname.replace(/^\/(en|de)(?=\/|$)/, '') || '/';
    const targetPath = locale === 'sq' ? currentPath : `/${locale}${currentPath === '/' ? '' : currentPath}`;

    router.visit(targetPath + window.location.search, { preserveScroll: true });
}

function onClickOutside(event: MouseEvent): void {
    if (open.value && root.value && !root.value.contains(event.target as Node)) {
        open.value = false;
    }
}

onMounted(() => document.addEventListener('click', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside));
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="flex items-center gap-1 rounded-md px-2 py-1.5 text-sm text-muted-foreground hover:bg-accent hover:text-foreground"
            :aria-expanded="open"
            @click="open = !open"
        >
            <span class="font-semibold tracking-wide">{{ localeMeta[page.props.locale].code }}</span>
            <span class="hidden sm:inline">{{ localeMeta[page.props.locale].name }}</span>
            <ChevronDown class="size-3.5" />
        </button>
        <div v-if="open" class="absolute right-0 z-20 mt-1 w-36 rounded-md border bg-popover p-1 shadow-md">
            <button
                v-for="locale in page.props.locales"
                :key="locale"
                type="button"
                class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent"
                :class="locale === page.props.locale ? 'font-medium text-foreground' : 'text-muted-foreground'"
                @click="switchLocale(locale)"
            >
                <span class="w-6 font-semibold tracking-wide">{{ localeMeta[locale].code }}</span>
                <span>{{ localeMeta[locale].name }}</span>
            </button>
        </div>
    </div>
</template>
