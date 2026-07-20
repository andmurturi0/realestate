<script setup lang="ts">
import { Minus, Plus } from 'lucide-vue-next';
import { computed } from 'vue';

const modelValue = defineModel<number>({ required: true });

const props = defineProps<{
    id: string;
    label: string;
    min: number;
    max: number;
    step: number;
    format?: (value: number) => string;
}>();

const display = computed(() => (props.format ? props.format(modelValue.value) : String(modelValue.value)));

function round(value: number): number {
    return Math.round(value * 100) / 100;
}

function decrement() {
    modelValue.value = Math.max(props.min, round(modelValue.value - props.step));
}

function increment() {
    modelValue.value = Math.min(props.max, round(modelValue.value + props.step));
}
</script>

<template>
    <div>
        <label :for="id" class="mb-1.5 block text-sm font-medium">{{ label }}</label>
        <div class="flex items-center gap-2">
            <button
                type="button"
                :disabled="modelValue <= min"
                :aria-label="`- ${label}`"
                class="flex size-9 shrink-0 items-center justify-center rounded-md border border-input bg-background transition-colors hover:bg-accent disabled:cursor-not-allowed disabled:opacity-50"
                @click="decrement"
            >
                <Minus class="size-4" />
            </button>
            <div :id="id" class="flex-1 rounded-md border border-input bg-background px-3 py-2 text-center text-sm font-medium tabular-nums">
                {{ display }}
            </div>
            <button
                type="button"
                :disabled="modelValue >= max"
                :aria-label="`+ ${label}`"
                class="flex size-9 shrink-0 items-center justify-center rounded-md border border-input bg-background transition-colors hover:bg-accent disabled:cursor-not-allowed disabled:opacity-50"
                @click="increment"
            >
                <Plus class="size-4" />
            </button>
        </div>
    </div>
</template>
