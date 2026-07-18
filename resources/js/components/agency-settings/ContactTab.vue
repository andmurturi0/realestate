<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import OfficeMapPicker from './OfficeMapPicker.vue';

const props = defineProps<{
    settings: Record<string, unknown>;
}>();

const form = useForm({
    phone: (props.settings.phone as string | null) ?? '',
    whatsapp: (props.settings.whatsapp as string | null) ?? '',
    email: (props.settings.email as string | null) ?? '',
    address: (props.settings.address as string | null) ?? '',
    office_lat: props.settings.office_lat != null ? Number(props.settings.office_lat) : null,
    office_lng: props.settings.office_lng != null ? Number(props.settings.office_lng) : null,
});

const updateCoordinates = (coords: { lat: number; lng: number }) => {
    form.office_lat = coords.lat;
    form.office_lng = coords.lng;
};

const submit = () => {
    form.put(route('dashboard.settings.contact'), { preserveScroll: true });
};
</script>

<template>
    <form class="grid max-w-xl gap-6" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="contact_phone">Telefoni</Label>
                <Input id="contact_phone" v-model="form.phone" />
                <InputError :message="form.errors.phone" />
            </div>
            <div class="grid gap-2">
                <Label for="contact_whatsapp">WhatsApp</Label>
                <Input id="contact_whatsapp" v-model="form.whatsapp" />
                <InputError :message="form.errors.whatsapp" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="contact_email">Email</Label>
            <Input id="contact_email" v-model="form.email" type="email" />
            <InputError :message="form.errors.email" />
        </div>

        <div class="grid gap-2">
            <Label for="contact_address">Adresa</Label>
            <Input id="contact_address" v-model="form.address" />
            <InputError :message="form.errors.address" />
        </div>

        <div class="grid gap-2">
            <Label>Lokacioni i zyrës (kliko ose zvarrit pinin)</Label>
            <OfficeMapPicker :lat="form.office_lat" :lng="form.office_lng" @update="updateCoordinates" />
            <div class="flex gap-4 text-xs text-muted-foreground">
                <span>Lat: {{ form.office_lat ?? '—' }}</span>
                <span>Lng: {{ form.office_lng ?? '—' }}</span>
            </div>
            <InputError :message="form.errors.office_lat ?? form.errors.office_lng" />
        </div>

        <div>
            <Button type="submit" :disabled="form.processing">Ruaj</Button>
        </div>
    </form>
</template>
