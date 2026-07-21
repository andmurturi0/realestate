<script setup lang="ts">
import PropertyLightbox from '@/components/site/PropertyLightbox.vue';
import { type PropertyImageData } from '@/lib/detail';
import { ImageOff } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    images: PropertyImageData[];
}>();

const activeIndex = ref(0);
const lightboxOpen = ref(false);

function openAt(index: number) {
    activeIndex.value = index;
    lightboxOpen.value = true;
}

// Preload the next thumbnail's full-size image so the strip feels instant.
watch(activeIndex, (i) => {
    const nextImage = props.images[i + 1];

    if (nextImage?.url) {
        const preload = new Image();
        preload.src = nextImage.url;
    }
});

// Both variants are proportional scale-downs of the same original (never
// upscaled — ImageService::scaleDown), so pairing them in one srcset is safe.
// The declared widths are nominal caps, not measured pixels: most uploads
// are larger than 1920px so this holds in practice, but a small enough
// original would make the descriptor optimistic — a soft mismatch the
// browser tolerates, not a broken image.
function galleryMainSrcset(image: PropertyImageData | undefined): string | undefined {
    if (!image?.url) return undefined;
    if (!image.thumbnail_url) return undefined;

    return `${image.thumbnail_url} 400w, ${image.url} 1920w`;
}
</script>

<template>
    <div v-if="images.length">
        <button type="button" class="block aspect-[16/10] w-full overflow-hidden rounded-xl bg-muted" @click="openAt(activeIndex)">
            <img
                v-if="images[activeIndex]?.url"
                :src="images[activeIndex].url!"
                :srcset="galleryMainSrcset(images[activeIndex])"
                sizes="(min-width: 1024px) 66vw, 100vw"
                :alt="images[activeIndex].alt ?? ''"
                fetchpriority="high"
                decoding="async"
                class="h-full w-full object-cover"
            />
        </button>

        <div v-if="images.length > 1" class="mt-3 grid grid-cols-5 gap-2 sm:grid-cols-6">
            <button
                v-for="(image, i) in images"
                :key="image.id"
                type="button"
                class="aspect-[4/3] overflow-hidden rounded-md ring-2 ring-offset-2 ring-offset-background transition-shadow"
                :class="i === activeIndex ? 'ring-primary' : 'ring-transparent hover:ring-muted-foreground/30'"
                @click="openAt(i)"
            >
                <img
                    :src="image.thumbnail_url ?? image.url ?? ''"
                    :alt="image.alt ?? ''"
                    loading="lazy"
                    decoding="async"
                    class="h-full w-full object-cover"
                />
            </button>
        </div>

        <PropertyLightbox v-model="lightboxOpen" v-model:index="activeIndex" :images="images" />
    </div>

    <div v-else class="flex aspect-[16/10] w-full items-center justify-center rounded-xl bg-muted text-muted-foreground">
        <ImageOff class="size-10" />
    </div>
</template>
