<script setup lang="ts">
export type Locale = 'sq' | 'en' | 'de';

defineProps<{
    modelValue: Locale;
    /** Locales that currently carry a validation error — marked with a red dot. */
    errorLocales?: Locale[];
}>();

defineEmits<{
    (e: 'update:modelValue', value: Locale): void;
}>();

const locales: { key: Locale; label: string }[] = [
    { key: 'sq', label: 'SQ' },
    { key: 'en', label: 'EN' },
    { key: 'de', label: 'DE' },
];
</script>

<template>
    <div class="flex gap-1 border-b">
        <button
            v-for="locale in locales"
            :key="locale.key"
            type="button"
            class="-mb-px inline-flex items-center gap-1.5 border-b-2 px-3 py-1.5 text-sm font-medium"
            :class="modelValue === locale.key ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'"
            @click="$emit('update:modelValue', locale.key)"
        >
            {{ locale.label }}
            <span v-if="errorLocales?.includes(locale.key)" class="size-1.5 rounded-full bg-destructive" />
        </button>
    </div>
</template>
