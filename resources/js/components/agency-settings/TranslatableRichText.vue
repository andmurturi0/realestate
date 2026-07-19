<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { ref } from 'vue';
import RichTextEditor from './RichTextEditor.vue';

type Locale = 'sq' | 'en' | 'de';

defineProps<{
    label: string;
    error?: string;
}>();

const model = defineModel<Record<Locale, string>>({ required: true });

const locales: { code: Locale; label: string }[] = [
    { code: 'sq', label: 'SQ' },
    { code: 'en', label: 'EN' },
    { code: 'de', label: 'DE' },
];

const activeLocale = ref<Locale>('sq');
</script>

<template>
    <div class="grid gap-2">
        <div class="flex items-center justify-between">
            <Label>{{ label }}</Label>
            <div class="flex gap-1">
                <button
                    v-for="locale in locales"
                    :key="locale.code"
                    type="button"
                    class="rounded-md px-2 py-1 text-xs font-medium"
                    :class="
                        activeLocale === locale.code ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:text-foreground'
                    "
                    @click="activeLocale = locale.code"
                >
                    {{ locale.label }}
                </button>
            </div>
        </div>
        <RichTextEditor v-for="locale in locales" v-show="activeLocale === locale.code" :key="locale.code" v-model="model[locale.code]" />
        <InputError :message="error" />
    </div>
</template>
