<script setup lang="ts">
import IntlTelInput from '@intl-tel-input/vue/with-utils';
import 'intl-tel-input/styles';
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        id?: string;
        placeholder?: string;
        inputClass?: string;
    }>(),
    { id: 'phone' },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'update:valid': [value: boolean];
}>();

const iti = ref<InstanceType<typeof IntlTelInput> | null>(null);

// The library's own v-model reflects the display format (international, with
// spaces); we re-read the instance ourselves to always emit E.164 for storage.
function handleChangeNumber() {
    emit('update:modelValue', iti.value?.instance?.getNumber('E164') ?? '');
}

function handleChangeValidity(valid: boolean) {
    emit('update:valid', valid);
}
</script>

<template>
    <IntlTelInput
        ref="iti"
        :model-value="props.modelValue"
        initial-country="xk"
        :country-search="true"
        container-class="w-full"
        :input-props="{
            id: props.id,
            name: props.id,
            required: true,
            placeholder: props.placeholder,
            class:
                props.inputClass ??
                'h-10 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
        }"
        @change-number="handleChangeNumber"
        @change-validity="handleChangeValidity"
    />
</template>
