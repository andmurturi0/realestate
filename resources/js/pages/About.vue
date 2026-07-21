<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useTranslations } from '@/lib/trans';
import { Head } from '@inertiajs/vue3';
import { Award, Heart, Quote, ShieldCheck, User, Users } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

defineProps<{
    about_content: string | null;
    testimonials: {
        id: number;
        author_name: string;
        company: string | null;
        quote: string;
        photo_url: string | null;
    }[];
    teamMembers: {
        id: number;
        name: string;
        position: string;
        photo_url: string | null;
    }[];
}>();

const { t } = useTranslations();

const featurePoints = computed(() => [
    { icon: Award, title: t('Përvojë e Gjatë'), description: t('Vite përvojë në tregun e patundshmërive dhe njohuri të thella të zonës.') },
    { icon: ShieldCheck, title: t('Besueshmëri'), description: t('Prona të verifikuara dhe transparencë e plotë në çdo hap të procesit.') },
    { icon: Users, title: t('Ekip Profesional'), description: t("Agjentë të kualifikuar, gati t'ju udhëheqin drejt vendimit të duhur.") },
    { icon: Heart, title: t('Shërbim i Personalizuar'), description: t('Çdo klient trajtohet me kujdes, sipas nevojave dhe buxhetit të tij.') },
]);

// A quote around this length reliably overflows the 4-line clamp below, so
// only those get a "Read more" toggle — short quotes never show a dead button.
const QUOTE_TRUNCATE_THRESHOLD = 200;

const expandedTestimonials = reactive(new Set<number>());

const isLongQuote = (quote: string) => quote.length > QUOTE_TRUNCATE_THRESHOLD;

const toggleTestimonial = (id: number) => {
    if (expandedTestimonials.has(id)) {
        expandedTestimonials.delete(id);
    } else {
        expandedTestimonials.add(id);
    }
};
</script>

<template>
    <Head :title="t('Rreth Nesh')" />

    <PublicLayout>
        <div class="mx-auto w-full max-w-screen-2xl flex-1 px-4 py-8 md:py-12">
            <h1 class="text-display-lg font-medium">{{ t('Rreth Nesh') }}</h1>

            <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="feature in featurePoints" :key="feature.title" class="rounded-xl border bg-card p-5">
                    <div class="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <component :is="feature.icon" class="size-5" />
                    </div>
                    <h2 class="mt-4 font-medium">{{ feature.title }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ feature.description }}</p>
                </div>
            </div>

            <div v-if="about_content" class="prose prose-sm dark:prose-invert mt-12 max-w-3xl" v-html="about_content" />

            <section v-if="testimonials.length" class="mt-16">
                <h2 class="text-display-sm font-medium">{{ t('Çfarë Thonë Klientët') }}</h2>
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <figure v-for="testimonial in testimonials" :key="testimonial.id" class="flex h-full flex-col rounded-xl border bg-card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <Quote class="size-10 shrink-0 text-amber-400" fill="currentColor" stroke="none" />
                            <div v-if="testimonial.photo_url || testimonial.company" class="flex min-w-0 items-center gap-2 pt-1">
                                <img
                                    v-if="testimonial.photo_url"
                                    :src="testimonial.photo_url"
                                    :alt="testimonial.company ?? testimonial.author_name"
                                    class="size-8 shrink-0 rounded object-contain"
                                />
                                <span v-if="testimonial.company" class="truncate text-xs font-medium text-muted-foreground">
                                    {{ testimonial.company }}
                                </span>
                            </div>
                        </div>

                        <h3 class="mt-3 truncate font-medium">{{ testimonial.company ?? testimonial.author_name }}</h3>

                        <blockquote
                            class="mt-2 min-h-24 flex-1 text-sm text-muted-foreground"
                            :class="expandedTestimonials.has(testimonial.id) ? '' : 'line-clamp-4'"
                        >
                            &ldquo;{{ testimonial.quote }}&rdquo;
                        </blockquote>

                        <div class="mt-1 min-h-5">
                            <button
                                v-if="isLongQuote(testimonial.quote)"
                                type="button"
                                class="text-xs font-medium text-primary hover:underline"
                                @click="toggleTestimonial(testimonial.id)"
                            >
                                {{ expandedTestimonials.has(testimonial.id) ? t('Mbylle') : t('Lexo më shumë') }}
                            </button>
                        </div>

                        <figcaption class="mt-4 border-t pt-3">
                            <p class="truncate text-xs font-medium tracking-wide [font-variant:small-caps]">{{ testimonial.author_name }}</p>
                        </figcaption>
                    </figure>
                </div>
            </section>

            <section v-if="teamMembers.length" class="mt-16">
                <h2 class="text-display-sm font-medium">{{ t('Njihuni me Ekipin') }}</h2>
                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <div v-for="member in teamMembers" :key="member.id" class="flex flex-col items-center text-center">
                        <img v-if="member.photo_url" :src="member.photo_url" :alt="member.name" class="size-24 rounded-full object-cover" />
                        <div v-else class="flex size-24 items-center justify-center rounded-full bg-muted">
                            <User class="size-10 text-muted-foreground" />
                        </div>
                        <p class="mt-3 font-medium">{{ member.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ member.position }}</p>
                    </div>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>
