<script setup lang="ts">
import ImageUploadField from '@/components/agency-settings/ImageUploadField.vue';
import InputError from '@/components/InputError.vue';
import LocaleTabBar, { type Locale } from '@/components/properties/LocaleTabBar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    agent: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        whatsapp: string | null;
        role: 'admin' | 'agent';
        bio: Partial<Record<Locale, string>>;
        avatar_url: string | null;
        is_active: boolean;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Agjentët', href: '/dashboard/agents' },
    { title: props.agent.name, href: `/dashboard/agents/${props.agent.id}/edit` },
];

const isSelf = computed(() => usePage<SharedData>().props.auth.user.id === props.agent.id);

const form = useForm({
    name: props.agent.name,
    email: props.agent.email,
    password: '',
    password_confirmation: '',
    phone: props.agent.phone ?? '',
    whatsapp: props.agent.whatsapp ?? '',
    role: props.agent.role,
    bio: {
        sq: props.agent.bio.sq ?? '',
        en: props.agent.bio.en ?? '',
        de: props.agent.bio.de ?? '',
    } as Record<Locale, string>,
    avatar: null as File | null,
    is_active: props.agent.is_active,
});

const activeLocale = ref<Locale>('sq');

const submit = () => {
    form.put(route('dashboard.agents.update', props.agent.id), { forceFormData: true });
};

const selectClass =
    'h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
</script>

<template>
    <Head :title="agent.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-xl font-medium">Edito agjentin</h1>
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
                        <Label for="password">Fjalëkalimi i ri (opsional)</Label>
                        <Input id="password" v-model="form.password" type="password" autocomplete="new-password" />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password_confirmation">Konfirmo fjalëkalimin</Label>
                        <Input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" />
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

                <ImageUploadField v-model="form.avatar" label="Fotoja" :current-url="agent.avatar_url" :error="form.errors.avatar" />

                <label class="flex items-center gap-2 text-sm" :class="{ 'opacity-50': isSelf }">
                    <input v-model="form.is_active" type="checkbox" class="size-4 rounded border-input" :disabled="isSelf" />
                    Aktiv (mund të kyçet)
                </label>
                <p v-if="isSelf" class="-mt-4 text-xs text-muted-foreground">Nuk mund ta çaktivizosh llogarinë tënde.</p>
                <InputError :message="form.errors.is_active" />

                <div>
                    <Button type="submit" :disabled="form.processing">Ruaj ndryshimet</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
