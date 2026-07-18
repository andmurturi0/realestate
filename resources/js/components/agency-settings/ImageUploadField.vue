<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { computed, ref } from 'vue';

const props = defineProps<{
    label: string;
    currentUrl?: string | null;
    error?: string;
}>();

const model = defineModel<File | null>();

const previewUrl = ref<string | null>(null);

function onChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    model.value = file;

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = file ? URL.createObjectURL(file) : null;
}

const shownUrl = computed(() => previewUrl.value ?? props.currentUrl ?? null);
</script>

<template>
    <div class="grid gap-2">
        <Label>{{ label }}</Label>
        <div class="flex items-center gap-3">
            <img v-if="shownUrl" :src="shownUrl" alt="" class="size-12 rounded-md border object-contain" />
            <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm"
                @change="onChange"
            />
        </div>
        <InputError :message="error" />
    </div>
</template>
