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
    { title: 'Agjentët', href: '/dashboard/agents' },
    { title: 'Agjent i ri', href: '/dashboard/agents/create' },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    whatsapp: '',
    role: 'agent' as 'admin' | 'agent',
    bio: { sq: '', en: '', de: '' } as Record<Locale, string>,
    avatar: null as File | null,
    is_active: true as boolean,
});

const activeLocale = ref<Locale>('sq');

const submit = () => {
    form.post(route('dashboard.agents.store'), { forceFormData: true });
};

const selectClass =
    'h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
</script>

<template>
    <Head title="Agjent i ri" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-xl font-medium">Agjent i ri</h1>
            </div>

            <form class="grid gap-6" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Emri</Label>
                    <Input id="name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input id="email" v-model="form.email" type="email" required />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="phone">Telefoni</Label>
                        <Input id="phone" v-model="form.phone" />
                        <InputError :message="form.errors.phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="whatsapp">WhatsApp</Label>
                        <Input id="whatsapp" v-model="form.whatsapp" />
                        <InputError :message="form.errors.whatsapp" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="role">Roli</Label>
                    <select id="role" v-model="form.role" :class="selectClass">
                        <option value="agent">Agjent</option>
                        <option value="admin">Admin</option>
                    </select>
                    <InputError :message="form.errors.role" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="password">Fjalëkalimi</Label>
                        <Input id="password" v-model="form.password" type="password" required />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password_confirmation">Konfirmo fjalëkalimin</Label>
                        <Input id="password_confirmation" v-model="form.password_confirmation" type="password" required />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label>Bio</Label>
                    <LocaleTabBar
                        v-model="activeLocale"
                        :error-locales="(['sq', 'en', 'de'] as Locale[]).filter((locale) => Boolean(form.errors[`bio.${locale}`]))"
                    />
                    <textarea
                        v-for="locale in ['sq', 'en', 'de'] as Locale[]"
                        v-show="activeLocale === locale"
                        :key="locale"
                        v-model="form.bio[locale]"
                        rows="4"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <InputError :message="form.errors[`bio.${activeLocale}`] ?? form.errors.bio" />
                </div>

                <ImageUploadField v-model="form.avatar" label="Fotoja" :error="form.errors.avatar" />

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_active" type="checkbox" class="size-4 rounded border-input" />
                    Aktiv (mund të kyçet)
                </label>
                <InputError :message="form.errors.is_active" />

                <div>
                    <Button type="submit" :disabled="form.processing">Krijo agjentin</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
