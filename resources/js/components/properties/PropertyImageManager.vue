<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/vue3';
import { ImagePlus, Loader2, Star, Trash2, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import draggable from 'vuedraggable';

export interface PropertyImageItem {
    id: number | string;
    url: string | null;
    thumbnail_url: string | null;
    is_primary?: boolean;
}

interface QueuedUpload {
    key: number;
    name: string;
    progress: number;
    status: 'queued' | 'uploading' | 'error';
    error?: string;
    file: File;
}

const MAX_IMAGES = 30;

const props = defineProps<{
    images: PropertyImageItem[];
    /** Missing on the create form: uploads go to the pending (session) endpoints. */
    propertyId?: number;
}>();

const ordered = ref<PropertyImageItem[]>([...props.images]);
watch(
    () => props.images,
    (images) => (ordered.value = [...images]),
);

const queue = ref<QueuedUpload[]>([]);
const uploading = ref(false);
const dragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
let nextKey = 0;

const remainingSlots = computed(() => MAX_IMAGES - props.images.length - queue.value.filter((item) => item.status !== 'error').length);

const uploadUrl = computed(() =>
    props.propertyId ? route('dashboard.properties.images.store', props.propertyId) : route('dashboard.pending-images.store'),
);

const enqueue = (files: FileList | File[]) => {
    for (const file of Array.from(files).slice(0, Math.max(remainingSlots.value, 0))) {
        queue.value.push({ key: nextKey++, name: file.name, progress: 0, status: 'queued', file });
    }
    processQueue();
};

const processQueue = () => {
    if (uploading.value) {
        return;
    }

    const item = queue.value.find((candidate) => candidate.status === 'queued');
    if (!item) {
        return;
    }

    uploading.value = true;
    item.status = 'uploading';

    router.post(
        uploadUrl.value,
        { image: item.file },
        {
            forceFormData: true,
            preserveScroll: true,
            onProgress: (event) => (item.progress = event?.percentage ?? 0),
            onSuccess: () => {
                queue.value = queue.value.filter((candidate) => candidate.key !== item.key);
            },
            onError: (errors) => {
                item.status = 'error';
                item.error = errors.image ?? 'Ngarkimi dështoi.';
            },
            onFinish: () => {
                uploading.value = false;
                processQueue();
            },
        },
    );
};

const dismissError = (key: number) => {
    queue.value = queue.value.filter((item) => item.key !== key);
};

const onDrop = (event: DragEvent) => {
    dragOver.value = false;
    if (event.dataTransfer?.files.length) {
        enqueue(event.dataTransfer.files);
    }
};

const onBrowse = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files?.length) {
        enqueue(input.files);
    }
    input.value = '';
};

const persistOrder = () => {
    if (!props.propertyId) {
        return;
    }

    router.put(
        route('dashboard.properties.images.reorder', props.propertyId),
        { order: ordered.value.map((image) => image.id) },
        { preserveScroll: true },
    );
};

const makePrimary = (image: PropertyImageItem) => {
    if (!props.propertyId || image.is_primary) {
        return;
    }

    router.put(route('dashboard.properties.images.primary', [props.propertyId, image.id]), {}, { preserveScroll: true });
};

const destroy = (image: PropertyImageItem) => {
    if (!confirm('A je i sigurt që do ta fshish këtë foto?')) {
        return;
    }

    const url = props.propertyId
        ? route('dashboard.properties.images.destroy', [props.propertyId, image.id])
        : route('dashboard.pending-images.destroy', image.id);

    router.delete(url, { preserveScroll: true });
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Fotot</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Dropzone -->
            <div
                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-6 py-8 text-center transition-colors"
                :class="dragOver ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50'"
                @click="fileInput?.click()"
                @dragover.prevent="dragOver = true"
                @dragleave.prevent="dragOver = false"
                @drop.prevent="onDrop"
            >
                <ImagePlus class="size-8 text-muted-foreground" />
                <p class="text-sm font-medium">Tërhiq fotot këtu ose kliko për t'i zgjedhur</p>
                <p class="text-xs text-muted-foreground">JPEG, PNG ose WebP — max 8MB secila, deri në {{ MAX_IMAGES }} foto</p>
                <input ref="fileInput" type="file" multiple accept="image/jpeg,image/png,image/webp" class="hidden" @change="onBrowse" />
            </div>

            <!-- Upload progress per file -->
            <ul v-if="queue.length" class="space-y-2">
                <li v-for="item in queue" :key="item.key" class="rounded-md border px-3 py-2 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <span class="flex min-w-0 items-center gap-2">
                            <Loader2 v-if="item.status === 'uploading'" class="size-4 shrink-0 animate-spin text-muted-foreground" />
                            <span class="truncate">{{ item.name }}</span>
                        </span>
                        <span v-if="item.status !== 'error'" class="tabular-nums text-muted-foreground">{{ item.progress }}%</span>
                        <button v-else type="button" class="text-muted-foreground hover:text-foreground" @click="dismissError(item.key)">
                            <X class="size-4" />
                        </button>
                    </div>
                    <div v-if="item.status !== 'error'" class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted">
                        <div class="h-full rounded-full bg-primary transition-all" :style="{ width: `${item.progress}%` }" />
                    </div>
                    <p v-else class="mt-1 text-xs text-destructive">{{ item.error }}</p>
                </li>
            </ul>

            <!-- Image grid -->
            <draggable
                v-if="ordered.length"
                v-model="ordered"
                item-key="id"
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"
                :disabled="!propertyId"
                ghost-class="opacity-40"
                @end="persistOrder"
            >
                <template #item="{ element }">
                    <div class="group relative aspect-[4/3] overflow-hidden rounded-md border bg-muted" :class="{ 'cursor-grab': propertyId }">
                        <img :src="element.thumbnail_url ?? element.url ?? ''" alt="" class="size-full object-cover" />

                        <span
                            v-if="element.is_primary"
                            class="absolute left-1.5 top-1.5 rounded-full bg-amber-400/95 px-2 py-0.5 text-[10px] font-medium text-amber-950"
                        >
                            Kryesore
                        </span>

                        <div class="absolute inset-x-0 bottom-0 flex justify-end gap-1 bg-gradient-to-t from-black/60 to-transparent p-1.5">
                            <button
                                v-if="propertyId"
                                type="button"
                                class="rounded-md p-1.5 text-white transition-colors hover:bg-white/20"
                                :title="element.is_primary ? 'Fotoja kryesore' : 'Bëje kryesore'"
                                @click.stop="makePrimary(element)"
                            >
                                <Star class="size-4" :class="element.is_primary ? 'fill-amber-400 text-amber-400' : ''" />
                            </button>
                            <button
                                type="button"
                                class="rounded-md p-1.5 text-white transition-colors hover:bg-red-500/80"
                                title="Fshije foton"
                                @click.stop="destroy(element)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </div>
                </template>
            </draggable>

            <p v-if="!propertyId && (ordered.length || queue.length)" class="text-xs text-muted-foreground">
                Fotot do t'i bashkëngjiten pronës kur ta ruash. Fotoja e parë bëhet kryesore; renditjen dhe kryesoren mund t'i ndryshosh pas ruajtjes.
            </p>
            <p v-else-if="propertyId && ordered.length > 1" class="text-xs text-muted-foreground">
                Tërhiqi fotot për t'i rirenditur — renditja ruhet automatikisht.
            </p>
        </CardContent>
    </Card>
</template>
