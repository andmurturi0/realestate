<script setup lang="ts">
import BrandingTab from '@/components/agency-settings/BrandingTab.vue';
import ContactTab from '@/components/agency-settings/ContactTab.vue';
import ContentTab from '@/components/agency-settings/ContentTab.vue';
import SocialTab from '@/components/agency-settings/SocialTab.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps<{
    settings: Record<string, unknown>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Cilësimet', href: '/dashboard/settings' }];

const tabs = [
    { key: 'branding', label: 'Brendi' },
    { key: 'contact', label: 'Kontakti' },
    { key: 'social', label: 'Rrjetet sociale' },
    { key: 'content', label: 'Përmbajtja' },
] as const;

type TabKey = (typeof tabs)[number]['key'];

const activeTab = ref<TabKey>('branding');

const flashSuccess = computed(() => usePage<SharedData>().props.flash.success);
</script>

<template>
    <Head title="Cilësimet" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-xl font-semibold">Cilësimet e agjencisë</h1>
                <p class="mt-1 text-sm text-muted-foreground">Brendi, kontakti dhe përmbajtja e faqes publike.</p>
            </div>

            <div
                v-if="flashSuccess"
                class="rounded-md border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200"
            >
                {{ flashSuccess }}
            </div>

            <div class="flex flex-wrap gap-1 border-b">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="-mb-px border-b-2 px-4 py-2 text-sm font-medium"
                    :class="
                        activeTab === tab.key
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>

            <BrandingTab v-if="activeTab === 'branding'" :settings="settings" />
            <ContactTab v-else-if="activeTab === 'contact'" :settings="settings" />
            <SocialTab v-else-if="activeTab === 'social'" :settings="settings" />
            <ContentTab v-else-if="activeTab === 'content'" :settings="settings" />
        </div>
    </AppLayout>
</template>
