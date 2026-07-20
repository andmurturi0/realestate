<script setup lang="ts">
import { type PropertyImageData } from '@/lib/detail';
import { useSwipe } from '@vueuse/core';
import { ChevronLeft, ChevronRight, X } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    images: PropertyImageData[];
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();

const index = defineModel<number>('index', { default: 0 });

function close() {
    emit('update:modelValue', false);
}

function next() {
    index.value = (index.value + 1) % props.images.length;
}

function prev() {
    index.value = (index.value - 1 + props.images.length) % props.images.length;
}

function onKeydown(event: KeyboardEvent) {
    if (!props.modelValue) return;

    if (event.key === 'Escape') close();
    if (event.key === 'ArrowRight') next();
    if (event.key === 'ArrowLeft') prev();
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));

const containerRef = ref<HTMLDivElement>();

useSwipe(containerRef, {
    onSwipeEnd(_event, direction) {
        if (direction === 'left') next();
        if (direction === 'right') prev();
    },
});

// Preload the next image so arrow navigation feels instant.
watch(
    () => [props.modelValue, index.value],
    ([open]) => {
        if (!open) return;

        const nextImage = props.images[(index.value + 1) % props.images.length];

        if (nextImage?.url) {
            const preload = new Image();
            preload.src = nextImage.url;
        }
    },
    { immediate: true },
);

watch(
    () => props.modelValue,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : '';
    },
);

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="modelValue"
            ref="containerRef"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/95"
            role="dialog"
            aria-modal="true"
            aria-label="Galeria e fotove"
        >
            <button
                type="button"
                class="absolute right-4 top-4 rounded-full p-2 text-white/80 hover:bg-white/10 hover:text-white"
                aria-label="Mbyll"
                @click="close"
            >
                <X class="size-6" />
            </button>

            <button
                v-if="images.length > 1"
                type="button"
                class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full p-2 text-white/80 hover:bg-white/10 hover:text-white sm:left-4"
                aria-label="Foto e mëparshme"
                @click="prev"
            >
                <ChevronLeft class="size-8" />
            </button>

            <img
                v-if="images[index]?.url"
                :src="images[index].url!"
                :alt="images[index].alt ?? ''"
                class="max-h-[85vh] max-w-[90vw] select-none object-contain"
            />

            <button
                v-if="images.length > 1"
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full p-2 text-white/80 hover:bg-white/10 hover:text-white sm:right-4"
                aria-label="Foto tjetër"
                @click="next"
            >
                <ChevronRight class="size-8" />
            </button>

            <span class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-black/50 px-3 py-1 text-sm text-white">
                {{ index + 1 }} / {{ images.length }}
            </span>
        </div>
    </Teleport>
</template>
