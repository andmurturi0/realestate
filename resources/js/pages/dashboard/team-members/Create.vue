<script setup lang="ts">
import ImageUploadField from '@/components/agency-settings/ImageUploadField.vue';
import InputError from '@/components/InputError.vue';
import LocaleTabBar, { type Locale } from '@/components/properties/LocaleTabBar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ekipi', href: '/dashboard/team-members' },
    { title: 'Anëtar i ri', href: '/dashboard/team-members/create' },
];

const form = useForm({
    name: '',
    position: { sq: '', en: '', de: '' } as Record<Locale, string>,
    photo: null as File | null,
    is_active: true as boolean,
});

const activeLocale = ref<Locale>('sq');

const submit = () => {
    form.post(route('dashboard.team-members.store'), { forceFormData: true });
};
</script>

<template>
    <Head title="Anëtar i ri" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-xl font-medium">Anëtar i ri</h1>
            </div>

            <form class="grid gap-6" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Emri</Label>
                    <Input id="name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label>Pozicioni</Label>
                    <LocaleTabBar
                        v-model="activeLocale"
                        :error-locales="(['sq', 'en', 'de'] as Locale[]).filter((locale) => Boolean(form.errors[`position.${locale}`]))"
                    />
                    <Input
                        v-for="locale in ['sq', 'en', 'de'] as Locale[]"
                        v-show="activeLocale === locale"
                        :key="locale"
                        v-model="form.position[locale]"
                    />
                    <InputError :message="form.errors[`position.${activeLocale}`] ?? form.errors.position" />
                </div>

                <ImageUploadField v-model="form.photo" label="Fotoja" :error="form.errors.photo" />

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_active" type="checkbox" class="size-4 rounded border-input" />
                    Aktiv (shfaqet publikisht)
                </label>
                <InputError :message="form.errors.is_active" />

                <div>
                    <Button type="submit" :disabled="form.processing">Krijo anëtarin</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
