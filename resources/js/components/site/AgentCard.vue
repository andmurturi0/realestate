<script setup lang="ts">
import { whatsAppLink, type PropertyAgentData } from '@/lib/detail';
import { useTranslations } from '@/lib/trans';
import { MessageCircle, Phone, User } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    agent: PropertyAgentData;
    referenceCode: string;
    title: string;
}>();

const { t } = useTranslations();

const whatsappHref = computed(() =>
    props.agent.whatsapp
        ? whatsAppLink(props.agent.whatsapp, t('Përshëndetje, më intereson prona {ref} - {title}', { ref: props.referenceCode, title: props.title }))
        : null,
);
</script>

<template>
    <div class="rounded-xl border bg-card p-4">
        <div class="flex items-center gap-3">
            <img v-if="agent.avatar_url" :src="agent.avatar_url" :alt="agent.name" class="size-12 shrink-0 rounded-full object-cover" />
            <div v-else class="flex size-12 shrink-0 items-center justify-center rounded-full bg-muted">
                <User class="size-6 text-muted-foreground" />
            </div>
            <div class="min-w-0">
                <p class="truncate font-medium">{{ agent.name }}</p>
                <p v-if="agent.phone" class="truncate text-sm text-muted-foreground">{{ agent.phone }}</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2">
            <a
                v-if="agent.phone"
                :href="`tel:${agent.phone}`"
                class="flex items-center justify-center gap-2 rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90"
            >
                <Phone class="size-4" />
                {{ t('Telefono') }}
            </a>
            <a
                v-if="whatsappHref"
                :href="whatsappHref"
                target="_blank"
                rel="noopener"
                class="flex items-center justify-center gap-2 rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90"
            >
                <MessageCircle class="size-4" />
                WhatsApp
            </a>
        </div>
    </div>
</template>
