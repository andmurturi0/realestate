<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const usingRecoveryCode = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const toggleRecoveryCode = () => {
    usingRecoveryCode.value = ! usingRecoveryCode.value;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.post(route('two-factor.login.store'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <AuthBase
        title="Verifikim dyfaktorësh"
        :description="
            usingRecoveryCode
                ? 'Vendos një nga kodet e tua rezervë'
                : 'Vendos kodin 6-shifror nga aplikacioni yt i autentikimit'
        "
    >
        <Head title="Verifikim dyfaktorësh" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div v-if="! usingRecoveryCode" class="grid gap-2">
                    <Label for="code">Kodi i autentikimit</Label>
                    <Input
                        id="code"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        required
                        autofocus
                        v-model="form.code"
                        placeholder="123456"
                    />
                    <InputError :message="form.errors.code" />
                </div>

                <div v-else class="grid gap-2">
                    <Label for="recovery_code">Kodi rezervë</Label>
                    <Input
                        id="recovery_code"
                        type="text"
                        autocomplete="one-time-code"
                        required
                        autofocus
                        v-model="form.recovery_code"
                        placeholder="xxxxx-xxxxx"
                    />
                    <InputError :message="form.errors.recovery_code" />
                </div>

                <Button type="submit" class="w-full" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Verifiko
                </Button>

                <button type="button" class="text-center text-sm text-muted-foreground underline underline-offset-4" @click="toggleRecoveryCode">
                    {{ usingRecoveryCode ? 'Përdor kodin nga aplikacioni' : 'Përdor një kod rezervë' }}
                </button>
            </div>
        </form>
    </AuthBase>
</template>
