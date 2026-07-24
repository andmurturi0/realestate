<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    status?: string;
    twoFactorEnabled: boolean;
    qrCodeSvg: string | null;
    manualSetupKey: string | null;
    recoveryCodes: string[] | null;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Dy Faktorë', href: '/settings/two-factor' }];

const statusMessages: Record<string, string> = {
    'two-factor-authentication-enabled': 'Skano kodin QR dhe vendos kodin e gjeneruar për të përfunduar konfigurimin.',
    'two-factor-authentication-confirmed': 'Autentikimi dyfaktorësh u aktivizua me sukses.',
    'two-factor-authentication-disabled': 'Autentikimi dyfaktorësh u çaktivizua.',
    'recovery-codes-generated': 'U gjeneruan kode rezervë të reja.',
};

const statusMessage = computed(() => (props.status ? statusMessages[props.status] : null));

const pendingConfirmation = computed(() => ! props.twoFactorEnabled && !! props.qrCodeSvg);

const enableForm = useForm({});
const confirmForm = useForm({ code: '' });
const disableForm = useForm({});
const recoveryCodesForm = useForm({});

const enable = () => {
    enableForm.post(route('two-factor.enable'), { preserveScroll: true });
};

const confirm = () => {
    confirmForm.post(route('two-factor.confirm'), {
        preserveScroll: true,
        onSuccess: () => confirmForm.reset(),
    });
};

const disable = () => {
    disableForm.delete(route('two-factor.disable'), { preserveScroll: true });
};

const regenerateRecoveryCodes = () => {
    recoveryCodesForm.post(route('two-factor.recovery-codes.regenerate'), { preserveScroll: true });
};

const downloadRecoveryCodes = () => {
    if (! props.recoveryCodes) {
        return;
    }

    const blob = new Blob([props.recoveryCodes.join('\n')], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'kode-rezerve.txt';
    link.click();
    URL.revokeObjectURL(url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Dy Faktorë" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Autentikimi Dyfaktorësh" description="Shto një shtresë shtesë sigurie kur kyçesh në llogarinë tënde" />

                <p v-if="statusMessage" class="text-sm font-medium text-green-600">{{ statusMessage }}</p>

                <!-- Not enabled: offer to start setup -->
                <div v-if="! twoFactorEnabled && ! pendingConfirmation" class="space-y-4">
                    <p class="text-sm text-muted-foreground">
                        Kur e aktivizon, do të të kërkohet një kod 6-shifror nga aplikacioni yt i autentikimit (p.sh. Google Authenticator, Authy)
                        çdo herë që kyçesh, përveç email-it dhe fjalëkalimit.
                    </p>
                    <Button :disabled="enableForm.processing" @click="enable">
                        <LoaderCircle v-if="enableForm.processing" class="h-4 w-4 animate-spin" />
                        Aktivizo autentikimin dyfaktorësh
                    </Button>
                </div>

                <!-- Pending confirmation: show QR + manual key + recovery codes, ask for a code -->
                <div v-else-if="pendingConfirmation" class="space-y-6">
                    <div class="space-y-3">
                        <p class="text-sm text-muted-foreground">
                            Skano këtë kod me aplikacionin tënd të autentikimit, ose vendos çelësin manualisht.
                        </p>
                        <div class="w-fit rounded-lg border bg-white p-4" v-html="qrCodeSvg"></div>
                        <p v-if="manualSetupKey" class="font-mono text-xs text-muted-foreground">Çelësi: {{ manualSetupKey }}</p>
                    </div>

                    <div v-if="recoveryCodes" class="space-y-2">
                        <p class="text-sm font-medium">Kodet rezervë</p>
                        <p class="text-sm text-muted-foreground">
                            Ruaji këto kode në një vend të sigurt. Secili prej tyre mund të përdoret një herë për t'u kyçur nëse humbet aksesin te
                            telefoni.
                        </p>
                        <div class="grid grid-cols-2 gap-1 rounded-lg border bg-muted p-4 font-mono text-sm">
                            <span v-for="code in recoveryCodes" :key="code">{{ code }}</span>
                        </div>
                        <Button type="button" variant="secondary" size="sm" @click="downloadRecoveryCodes">Shkarko kodet</Button>
                    </div>

                    <form class="max-w-xs space-y-4" @submit.prevent="confirm">
                        <div class="grid gap-2">
                            <Label for="code">Kodi i konfirmimit</Label>
                            <Input
                                id="code"
                                v-model="confirmForm.code"
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                autofocus
                                placeholder="123456"
                            />
                            <InputError :message="confirmForm.errors.code" />
                        </div>

                        <div class="flex items-center gap-3">
                            <Button :disabled="confirmForm.processing">
                                <LoaderCircle v-if="confirmForm.processing" class="h-4 w-4 animate-spin" />
                                Konfirmo
                            </Button>
                            <Button type="button" variant="ghost" :disabled="disableForm.processing" @click="disable">Anulo</Button>
                        </div>
                    </form>
                </div>

                <!-- Enabled and confirmed -->
                <div v-else class="space-y-6">
                    <p class="text-sm text-muted-foreground">Autentikimi dyfaktorësh është aktiv për këtë llogari.</p>

                    <div v-if="recoveryCodes" class="space-y-2">
                        <p class="text-sm font-medium">Kodet rezervë</p>
                        <p class="text-sm text-muted-foreground">
                            Nëse i ke përdorur ose i ke humbur, gjenero kode të reja — kodet e vjetra do të pushojnë së funksionuari.
                        </p>
                        <div class="grid grid-cols-2 gap-1 rounded-lg border bg-muted p-4 font-mono text-sm">
                            <span v-for="code in recoveryCodes" :key="code">{{ code }}</span>
                        </div>
                        <div class="flex gap-2">
                            <Button type="button" variant="secondary" size="sm" @click="downloadRecoveryCodes">Shkarko kodet</Button>
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                :disabled="recoveryCodesForm.processing"
                                @click="regenerateRecoveryCodes"
                            >
                                Rigjenero kodet
                            </Button>
                        </div>
                    </div>

                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="destructive">Çaktivizo autentikimin dyfaktorësh</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader class="space-y-3">
                                <DialogTitle>Je i sigurt që dëshiron ta çaktivizosh?</DialogTitle>
                                <DialogDescription>
                                    Llogaria jote do të mbrohet vetëm me email dhe fjalëkalim. Mund ta riaktivizosh sërish kur të duash.
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <DialogClose as-child>
                                    <Button variant="secondary">Anulo</Button>
                                </DialogClose>
                                <Button variant="destructive" :disabled="disableForm.processing" @click="disable">Çaktivizo</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
