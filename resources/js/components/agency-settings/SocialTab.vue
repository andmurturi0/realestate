<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    settings: Record<string, unknown>;
}>();

const fields = [
    { key: 'facebook', label: 'Facebook' },
    { key: 'instagram', label: 'Instagram' },
    { key: 'tiktok', label: 'TikTok' },
    { key: 'linkedin', label: 'LinkedIn' },
    { key: 'youtube', label: 'YouTube' },
    { key: 'app_store_url', label: 'App Store' },
    { key: 'play_store_url', label: 'Play Store' },
] as const;

type SocialKey = (typeof fields)[number]['key'];

const form = useForm(
    Object.fromEntries(fields.map((field) => [field.key, (props.settings[field.key] as string | null) ?? ''])) as Record<SocialKey, string>,
);

const submit = () => {
    form.put(route('dashboard.settings.social'), { preserveScroll: true });
};
</script>

<template>
    <form class="grid max-w-xl gap-4" @submit.prevent="submit">
        <div v-for="field in fields" :key="field.key" class="grid gap-2">
            <Label :for="`social_${field.key}`">{{ field.label }}</Label>
            <Input :id="`social_${field.key}`" v-model="form[field.key]" type="url" placeholder="https://" />
            <InputError :message="form.errors[field.key]" />
        </div>

        <div>
            <Button type="submit" :disabled="form.processing">Ruaj</Button>
        </div>
    </form>
</template>
