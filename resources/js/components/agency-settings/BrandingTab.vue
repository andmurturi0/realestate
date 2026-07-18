<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SharedData } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ImageUploadField from './ImageUploadField.vue';

const props = defineProps<{
    settings: Record<string, unknown>;
}>();

const page = usePage<SharedData>();
const sharedSettings = computed(() => page.props.settings);

const form = useForm({
    agency_name: (props.settings.agency_name as string | null) ?? '',
    primary_color: (props.settings.primary_color as string | null) ?? '#1d4ed8',
    watermark_enabled: Boolean(props.settings.watermark_enabled),
    logo: null as File | null,
    logo_dark: null as File | null,
    favicon: null as File | null,
});

const submit = () => {
    form.post(route('dashboard.settings.branding'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => form.reset('logo', 'logo_dark', 'favicon'),
    });
};
</script>

<template>
    <form class="grid max-w-xl gap-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="agency_name">Emri i agjencisë</Label>
            <Input id="agency_name" v-model="form.agency_name" required />
            <InputError :message="form.errors.agency_name" />
        </div>

        <div class="grid gap-2">
            <Label for="primary_color">Ngjyra primare</Label>
            <div class="flex items-center gap-3">
                <input
                    id="primary_color"
                    v-model="form.primary_color"
                    type="color"
                    class="h-10 w-14 cursor-pointer rounded-md border border-input bg-background p-1"
                />
                <Input v-model="form.primary_color" class="w-32" />
            </div>
            <InputError :message="form.errors.primary_color" />
        </div>

        <ImageUploadField v-model="form.logo" label="Logo" :current-url="sharedSettings.logo_url" :error="form.errors.logo" />
        <ImageUploadField
            v-model="form.logo_dark"
            label="Logo (teme e errët)"
            :current-url="sharedSettings.logo_dark_url"
            :error="form.errors.logo_dark"
        />
        <ImageUploadField
            v-model="form.favicon"
            label="Favicon"
            :current-url="sharedSettings.favicon_url"
            :error="form.errors.favicon"
        />

        <label class="flex items-center gap-2 text-sm">
            <input v-model="form.watermark_enabled" type="checkbox" class="size-4 rounded border-input" />
            Vendos watermark me logon e agjencisë mbi fotot e pronave
        </label>
        <InputError :message="form.errors.watermark_enabled" />

        <div>
            <Button type="submit" :disabled="form.processing">Ruaj</Button>
        </div>
    </form>
</template>
