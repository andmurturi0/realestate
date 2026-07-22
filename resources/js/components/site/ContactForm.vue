<script setup lang="ts">
import PhoneInput from '@/components/site/PhoneInput.vue';
import { useTranslations } from '@/lib/trans';
import { CheckCircle2 } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

const props = defineProps<{
    propertySlug: string;
}>();

const { t } = useTranslations();

const form = reactive({
    full_name: '',
    phone: '',
    message: '',
    // Honeypot: real visitors never see or fill this field.
    website: '',
});

const errors = ref<Record<string, string[]>>({});
const submitting = ref(false);
const success = ref(false);
const rateLimited = ref(false);
const phoneValid = ref(true);

function readCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

async function submit() {
    errors.value = {};
    rateLimited.value = false;

    if (form.phone && !phoneValid.value) {
        errors.value = { phone: [t('Numri i telefonit nuk është i vlefshëm.')] };

        return;
    }

    submitting.value = true;

    try {
        const response = await fetch(route('properties.contact', props.propertySlug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': readCookie('XSRF-TOKEN') ?? '',
            },
            body: JSON.stringify(form),
        });

        if (response.status === 422) {
            const body = (await response.json()) as { errors: Record<string, string[]> };
            errors.value = body.errors;

            return;
        }

        if (response.status === 429) {
            rateLimited.value = true;

            return;
        }

        if (response.ok) {
            success.value = true;
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="rounded-xl border bg-card p-4">
        <div v-if="success" class="flex flex-col items-center gap-2 py-6 text-center">
            <CheckCircle2 class="size-10 text-green-600" />
            <p class="font-medium">{{ t('Mesazhi juaj u dërgua!') }}</p>
            <p class="text-sm text-muted-foreground">{{ t("Agjenti do t'ju kontaktojë së shpejti.") }}</p>
        </div>

        <form v-else class="space-y-3" @submit.prevent="submit">
            <h2 class="font-medium">{{ t('Dërgo mesazh') }}</h2>

            <!-- Honeypot: off-screen, never display:none (some bots skip that check). -->
            <div class="absolute -left-[9999px]" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" v-model="form.website" type="text" tabindex="-1" autocomplete="off" />
            </div>

            <div>
                <label for="full_name" class="mb-1 block text-sm font-medium">{{ t('Emri i plotë') }}</label>
                <input
                    id="full_name"
                    v-model="form.full_name"
                    type="text"
                    required
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                <p v-if="errors.full_name" class="mt-1 text-xs text-destructive">{{ errors.full_name[0] }}</p>
            </div>

            <div>
                <label for="phone" class="mb-1 block text-sm font-medium">{{ t('Telefoni') }}</label>
                <PhoneInput v-model="form.phone" @update:valid="phoneValid = $event" />
                <p v-if="errors.phone" class="mt-1 text-xs text-destructive">{{ errors.phone[0] }}</p>
            </div>

            <div>
                <label for="message" class="mb-1 block text-sm font-medium">{{ t('Mesazhi') }}</label>
                <textarea
                    id="message"
                    v-model="form.message"
                    rows="4"
                    required
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                <p v-if="errors.message" class="mt-1 text-xs text-destructive">{{ errors.message[0] }}</p>
            </div>

            <p v-if="rateLimited" class="text-sm text-destructive">
                {{ t('Keni arritur numrin maksimal të mesazheve. Ju lutemi provoni sërish më vonë.') }}
            </p>

            <button
                type="submit"
                :disabled="submitting"
                class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 disabled:opacity-50"
            >
                {{ submitting ? t('Duke dërguar…') : t('Dërgo mesazhin') }}
            </button>
        </form>
    </div>
</template>
