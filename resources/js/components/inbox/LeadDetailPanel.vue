<script setup lang="ts">
import { type AgentOption, type LeadNoteData, relativeTime } from '@/lib/inbox';
import { router } from '@inertiajs/vue3';
import { MessageCircle, Phone } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    title: string;
    phone: string;
    status: string;
    statusOptions: Record<string, string>;
    assignedTo: number | null;
    canAssign: boolean;
    agents: AgentOption[] | null;
    notes: LeadNoteData[];
    statusUrl: string;
    assignUrl: string;
    notesUrl: string;
    whatsappHref?: string | null;
}>();

const selectClass =
    'h-9 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';

function updateStatus(event: Event) {
    const status = (event.target as HTMLSelectElement).value;
    router.patch(props.statusUrl, { status }, { preserveScroll: true, preserveState: true });
}

function updateAssignment(event: Event) {
    const value = (event.target as HTMLSelectElement).value;
    router.patch(props.assignUrl, { assigned_to: value || null }, { preserveScroll: true, preserveState: true });
}

const noteBody = ref('');
const submittingNote = ref(false);

function submitNote() {
    if (!noteBody.value.trim()) return;

    submittingNote.value = true;
    router.post(
        props.notesUrl,
        { body: noteBody.value },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => (noteBody.value = ''),
            onFinish: () => (submittingNote.value = false),
        },
    );
}
</script>

<template>
    <div class="flex flex-col gap-4 rounded-lg border bg-card p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold">{{ title }}</h2>
                <p class="text-sm text-muted-foreground">{{ phone }}</p>
            </div>

            <div class="flex items-center gap-2">
                <a :href="`tel:${phone}`" class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm hover:bg-accent">
                    <Phone class="size-4" /> Telefono
                </a>
                <a
                    v-if="whatsappHref"
                    :href="whatsappHref"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm hover:bg-accent"
                >
                    <MessageCircle class="size-4" /> WhatsApp
                </a>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <div class="grid gap-1">
                <label class="text-xs font-medium text-muted-foreground">Statusi</label>
                <select :value="status" :class="selectClass" @change="updateStatus">
                    <option v-for="(label, value) in statusOptions" :key="value" :value="value">{{ label }}</option>
                </select>
            </div>

            <div v-if="canAssign && agents" class="grid gap-1">
                <label class="text-xs font-medium text-muted-foreground">Cakto te agjenti</label>
                <select :value="assignedTo ?? ''" :class="selectClass" @change="updateAssignment">
                    <option value="">— Pa agjent —</option>
                    <option v-for="agent in agents" :key="agent.id" :value="agent.id">{{ agent.name }}</option>
                </select>
            </div>
        </div>

        <div>
            <slot name="extra" />
        </div>

        <div class="border-t pt-3">
            <h3 class="mb-2 text-sm font-semibold">Shënime</h3>

            <div class="mb-3 flex flex-col gap-2">
                <p v-if="notes.length === 0" class="text-sm text-muted-foreground">Ende pa shënime.</p>
                <div v-for="note in notes" :key="note.id" class="rounded-md bg-muted/40 p-2 text-sm">
                    <p>{{ note.body }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ note.author }} · {{ relativeTime(note.created_at) }}</p>
                </div>
            </div>

            <div class="flex gap-2">
                <textarea
                    v-model="noteBody"
                    rows="2"
                    placeholder="Shto një shënim…"
                    class="flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                <button
                    type="button"
                    :disabled="submittingNote || !noteBody.trim()"
                    class="self-start rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                    @click="submitNote"
                >
                    Ruaj
                </button>
            </div>
        </div>
    </div>
</template>
