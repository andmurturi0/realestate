<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Construction } from 'lucide-vue-next';

const props = defineProps<{
    title: string;
}>();

const breadcrumbs = [{ title: props.title, href: '#' }];
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 items-center justify-center p-8">
            <div class="text-center">
                <Construction class="mx-auto size-10 text-muted-foreground" />
                <h1 class="mt-4 text-xl font-medium">{{ title }}</h1>
                <p class="mt-2 text-sm text-muted-foreground">Kjo faqe ndërtohet në një fazë të mëvonshme.</p>
            </div>
        </div>
    </AppLayout>
</template>
