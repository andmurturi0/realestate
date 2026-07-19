<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ref, watch, type HTMLAttributes } from 'vue';

const props = defineProps<{
    modelValue: number | null;
    id?: string;
    placeholder?: string;
    class?: HTMLAttributes['class'];
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | null): void;
}>();

const isFocused = ref(false);

const formatEuros = (value: number | null): string =>
    value == null ? '' : new Intl.NumberFormat('sq-AL', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(value);

const text = ref(formatEuros(props.modelValue));

/**
 * Parses "114.000", "1234,56" and "114.000,50" alike: a trailing separator
 * followed by 1-2 digits is the decimal separator, everything else is a
 * thousands separator.
 */
const parseEuros = (input: string): number | null => {
    const cleaned = input.replace(/[^\d.,]/g, '');

    if (cleaned.replace(/[.,]/g, '') === '') {
        return null;
    }

    const lastSeparator = Math.max(cleaned.lastIndexOf('.'), cleaned.lastIndexOf(','));
    const decimalDigits = cleaned.length - lastSeparator - 1;

    if (lastSeparator !== -1 && decimalDigits >= 1 && decimalDigits <= 2) {
        const integerPart = cleaned.slice(0, lastSeparator).replace(/[.,]/g, '');
        return Math.round(Number(`${integerPart}.${cleaned.slice(lastSeparator + 1)}`) * 100) / 100;
    }

    return Number(cleaned.replace(/[.,]/g, ''));
};

const onInput = (event: Event) => {
    text.value = (event.target as HTMLInputElement).value;
    emit('update:modelValue', parseEuros(text.value));
};

const onFocus = () => {
    isFocused.value = true;
    text.value = props.modelValue == null ? '' : String(props.modelValue).replace('.', ',');
};

const onBlur = () => {
    isFocused.value = false;
    text.value = formatEuros(props.modelValue);
};

watch(
    () => props.modelValue,
    (value) => {
        if (!isFocused.value) {
            text.value = formatEuros(value);
        }
    },
);
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            type="text"
            inputmode="decimal"
            :placeholder="placeholder"
            :value="text"
            :class="
                cn(
                    'flex h-10 w-full rounded-md border border-input bg-background py-2 pl-3 pr-8 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                    props.class,
                )
            "
            @input="onInput"
            @focus="onFocus"
            @blur="onBlur"
        />
        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">€</span>
    </div>
</template>
