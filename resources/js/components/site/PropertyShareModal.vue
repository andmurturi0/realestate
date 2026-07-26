<script setup lang="ts">
import { useTranslations } from '@/lib/trans';
import { useClipboard } from '@vueuse/core';
import { Check, Copy, Facebook, Mail, MessageCircle, Phone, Send, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, watch } from 'vue';

const props = defineProps<{
    modelValue: boolean;
    title: string;
    imageUrl: string | null;
    url: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();

const { t } = useTranslations();
const { copy, copied } = useClipboard({ legacy: true });

function close() {
    emit('update:modelValue', false);
}

function onOverlayClick(event: MouseEvent) {
    if (event.target === event.currentTarget) close();
}

function onKeydown(event: KeyboardEvent) {
    if (props.modelValue && event.key === 'Escape') close();
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));

watch(
    () => props.modelValue,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : '';
    },
);

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});

// Meta's official app-id-free Messenger share scheme — only opens on devices
// with the Messenger app installed, but it's the one send-to-Messenger link
// that works without registering a Facebook app (which this project has no
// per-agency way to store, per the "nothing hardcoded" rule).
const networks = computed(() => [
    {
        key: 'facebook',
        icon: Facebook,
        color: '#1877F2',
        label: t('Ndaje në Facebook'),
        href: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(props.url)}`,
    },
    {
        key: 'messenger',
        icon: MessageCircle,
        color: '#0084FF',
        label: t('Ndaje në Messenger'),
        href: `fb-messenger://share/?link=${encodeURIComponent(props.url)}`,
    },
    {
        key: 'telegram',
        icon: Send,
        color: '#26A5E4',
        label: t('Ndaje në Telegram'),
        href: `https://t.me/share/url?url=${encodeURIComponent(props.url)}&text=${encodeURIComponent(props.title)}`,
    },
    {
        key: 'whatsapp',
        icon: Phone,
        color: '#25D366',
        label: t('Ndaje në WhatsApp'),
        href: `https://api.whatsapp.com/send?text=${encodeURIComponent(`${props.title} ${props.url}`)}`,
    },
    {
        key: 'email',
        icon: Mail,
        color: null,
        label: t('Ndaje me email'),
        href: `mailto:?subject=${encodeURIComponent(props.title)}&body=${encodeURIComponent(props.url)}`,
    },
]);

function copyUrl() {
    copy(props.url);
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="modelValue"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
            role="dialog"
            aria-modal="true"
            :aria-label="t('Ndaje')"
            @click="onOverlayClick"
        >
            <div class="w-full max-w-sm rounded-xl bg-background p-5 shadow-hover">
                <div class="flex items-start justify-between gap-2">
                    <h2 class="text-lg font-medium">{{ t('Ndaje') }}</h2>
                    <button
                        type="button"
                        class="rounded-full p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                        :aria-label="t('Mbyll')"
                        @click="close"
                    >
                        <X class="size-5" />
                    </button>
                </div>

                <p class="mt-1 line-clamp-2 text-sm font-medium">{{ title }}</p>

                <img v-if="imageUrl" :src="imageUrl" :alt="title" class="mt-3 aspect-[16/10] w-full rounded-lg object-cover" />

                <div class="mt-4 flex flex-wrap justify-between gap-2">
                    <a
                        v-for="network in networks"
                        :key="network.key"
                        :href="network.href"
                        target="_blank"
                        rel="noopener noreferrer"
                        :aria-label="network.label"
                        :title="network.label"
                        class="flex size-11 items-center justify-center rounded-full text-white transition-opacity hover:opacity-90"
                        :class="network.color ? '' : 'bg-muted !text-foreground'"
                        :style="network.color ? { backgroundColor: network.color } : undefined"
                    >
                        <component :is="network.icon" class="size-5" />
                    </a>
                </div>

                <div class="mt-4 flex items-center gap-2">
                    <input
                        type="text"
                        readonly
                        :value="url"
                        :aria-label="t('Lidhja e pronës')"
                        class="h-10 flex-1 truncate rounded-md border border-input bg-muted px-3 text-xs text-muted-foreground focus-visible:outline-none"
                        @focus="($event.target as HTMLInputElement).select()"
                    />
                    <button
                        type="button"
                        class="flex h-10 shrink-0 items-center gap-1.5 rounded-md border border-input px-3 text-xs font-medium transition-colors hover:bg-accent"
                        @click="copyUrl"
                    >
                        <Check v-if="copied" class="size-4 text-green-600" />
                        <Copy v-else class="size-4" />
                        {{ copied ? t('U kopjua') : t('Kopjo') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
