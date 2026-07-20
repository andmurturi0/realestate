<script setup lang="ts">
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage<SharedData>();
const open = ref(false);
const root = ref<HTMLElement | null>(null);

const localeMeta: Record<'sq' | 'en' | 'de', { flag: string; name: string }> = {
    sq: { flag: '🇦🇱', name: 'Shqip' },
    en: { flag: '🇬🇧', name: 'English' },
    de: { flag: '🇩🇪', name: 'Deutsch' },
};

function switchLocale(locale: 'sq' | 'en' | 'de'): void {
    open.value = false;

    if (locale === page.props.locale) {
        return;
    }

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
            <span>{{ localeMeta[page.props.locale].flag }}</span>
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
                <span>{{ localeMeta[locale].flag }}</span>
                <span>{{ localeMeta[locale].name }}</span>
            </button>
        </div>
    </div>
</template>
