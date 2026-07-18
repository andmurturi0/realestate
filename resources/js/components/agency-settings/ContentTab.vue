<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/vue3';
import TranslatableRichText from './TranslatableRichText.vue';

const props = defineProps<{
    settings: Record<string, unknown>;
}>();

const fields = [
    { key: 'about_content', label: 'Rreth nesh' },
    { key: 'terms_content', label: 'Kushtet e përdorimit' },
    { key: 'privacy_content', label: 'Politika e privatësisë' },
    { key: 'faq_content', label: 'Pyetjet e shpeshta (FAQ)' },
] as const;

type ContentKey = (typeof fields)[number]['key'];

const normalize = (value: unknown): { sq: string; en: string; de: string } => {
    const translations = (value ?? {}) as Record<string, string | null>;

    return {
        sq: translations.sq ?? '',
        en: translations.en ?? '',
        de: translations.de ?? '',
    };
};

const form = useForm(
    Object.fromEntries(fields.map((field) => [field.key, normalize(props.settings[field.key])])) as Record<
        ContentKey,
        { sq: string; en: string; de: string }
    >,
);

const submit = () => {
    form.put(route('dashboard.settings.content'), { preserveScroll: true });
};
</script>

<template>
    <form class="grid max-w-3xl gap-8" @submit.prevent="submit">
        <TranslatableRichText
            v-for="field in fields"
            :key="field.key"
            v-model="form[field.key]"
            :label="field.label"
            :error="form.errors[field.key]"
        />

        <div>
            <Button type="submit" :disabled="form.processing">Ruaj</Button>
        </div>
    </form>
</template>
