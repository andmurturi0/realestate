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

const props = defineProps<{
    testimonial: {
        id: number;
        author_name: string;
        company: string | null;
        quote: Partial<Record<Locale, string>>;
        photo_url: string | null;
        is_active: boolean;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dëshmitë', href: '/dashboard/testimonials' },
    { title: props.testimonial.author_name, href: `/dashboard/testimonials/${props.testimonial.id}/edit` },
];

const form = useForm({
    author_name: props.testimonial.author_name,
    company: props.testimonial.company ?? '',
    quote: {
        sq: props.testimonial.quote.sq ?? '',
        en: props.testimonial.quote.en ?? '',
        de: props.testimonial.quote.de ?? '',
    } as Record<Locale, string>,
    photo: null as File | null,
    is_active: props.testimonial.is_active,
});

const activeLocale = ref<Locale>('sq');

const submit = () => {
    form.put(route('dashboard.testimonials.update', props.testimonial.id), { forceFormData: true });
};
</script>

<template>
    <Head :title="testimonial.author_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-xl font-medium">Edito dëshminë</h1>
            </div>

            <form class="grid gap-6" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="author_name">Emri</Label>
                    <Input id="author_name" v-model="form.author_name" required />
                    <InputError :message="form.errors.author_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="company">Kompania (opsionale)</Label>
                    <Input id="company" v-model="form.company" />
                    <InputError :message="form.errors.company" />
                </div>

                <div class="grid gap-2">
                    <Label>Citimi</Label>
                    <LocaleTabBar
                        v-model="activeLocale"
                        :error-locales="(['sq', 'en', 'de'] as Locale[]).filter((locale) => Boolean(form.errors[`quote.${locale}`]))"
                    />
                    <textarea
                        v-for="locale in ['sq', 'en', 'de'] as Locale[]"
                        v-show="activeLocale === locale"
                        :key="locale"
                        v-model="form.quote[locale]"
                        rows="4"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm"
                    />
                    <InputError :message="form.errors[`quote.${activeLocale}`] ?? form.errors.quote" />
                </div>

                <ImageUploadField v-model="form.photo" label="Fotoja / logoja" :current-url="testimonial.photo_url" :error="form.errors.photo" />

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_active" type="checkbox" class="size-4 rounded border-input" />
                    Aktive (shfaqet publikisht)
                </label>
                <InputError :message="form.errors.is_active" />

                <div>
                    <Button type="submit" :disabled="form.processing">Ruaj ndryshimet</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
