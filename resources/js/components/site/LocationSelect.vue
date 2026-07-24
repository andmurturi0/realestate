<script setup lang="ts">
import { type LocationOption } from '@/lib/listing';
import { useTranslations } from '@/lib/trans';
import { onClickOutside } from '@vueuse/core';
import { Check, ChevronDown, MapPin, X } from 'lucide-vue-next';
import { computed, ref, useTemplateRef } from 'vue';

const props = defineProps<{
    modelValue: number | null;
    locations: LocationOption[];
    id?: string;
}>();

const { t } = useTranslations();

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | null): void;
}>();

const open = ref(false);
const search = ref('');
const container = useTemplateRef<HTMLElement>('container');

onClickOutside(container, () => (open.value = false));

const selectedLabel = computed(() => {
    for (const municipality of props.locations) {
        if (municipality.id === props.modelValue) return municipality.name;

        const neighborhood = municipality.neighborhoods.find((n) => n.id === props.modelValue);
        if (neighborhood) return `${neighborhood.name}, ${municipality.name}`;
    }

    return null;
});

/**
 * A municipality stays visible when it matches the term itself (with all its
 * neighborhoods) or when any of its neighborhoods matches (only those shown).
 */
const filteredLocations = computed(() => {
    const term = search.value.trim().toLocaleLowerCase();

    if (!term) return props.locations;

    return props.locations.flatMap((municipality) => {
        if (municipality.name.toLocaleLowerCase().includes(term)) return [municipality];

        const neighborhoods = municipality.neighborhoods.filter((n) => n.name.toLocaleLowerCase().includes(term));

        return neighborhoods.length ? [{ ...municipality, neighborhoods }] : [];
    });
});

function toggle() {
    open.value = !open.value;
    if (open.value) search.value = '';
}

function select(id: number | null) {
    emit('update:modelValue', id);
    open.value = false;
}
</script>

<template>
    <div ref="container" class="relative">
        <button
            type="button"
            class="flex h-10 w-full items-center gap-2 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            :aria-expanded="open"
            @click="toggle"
        >
            <MapPin class="size-4 shrink-0 text-muted-foreground" />
            <span class="flex-1 truncate text-left" :class="selectedLabel ? '' : 'text-muted-foreground'">
                {{ selectedLabel ?? t('Lokacioni') }}
            </span>
            <X
                v-if="modelValue != null"
                class="size-4 shrink-0 text-muted-foreground hover:text-foreground"
                :aria-label="t('Hiq lokacionin')"
                @click.stop="select(null)"
            />
            <ChevronDown v-else class="size-4 shrink-0 text-muted-foreground" />
        </button>

        <div
            v-if="open"
            class="absolute left-0 top-full z-20 mt-1 w-full min-w-64 overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md"
        >
            <div class="border-b p-2">
                <input
                    v-model="search"
                    type="text"
                    :id="id"
                    :name="id"
                    :placeholder="t('Kërko komunë ose lagje…')"
                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
            </div>
            <div class="max-h-72 overflow-y-auto p-1">
                <button
                    type="button"
                    class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
                    @click="select(null)"
                >
                    {{ t('Të gjitha lokacionet') }}
                    <Check v-if="modelValue == null" class="ml-auto size-4" />
                </button>

                <template v-for="municipality in filteredLocations" :key="municipality.id">
                    <button
                        type="button"
                        class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm font-medium hover:bg-accent hover:text-accent-foreground"
                        @click="select(municipality.id)"
                    >
                        {{ municipality.name }}
                        <Check v-if="modelValue === municipality.id" class="ml-auto size-4" />
                    </button>
                    <button
                        v-for="neighborhood in municipality.neighborhoods"
                        :key="neighborhood.id"
                        type="button"
                        class="flex w-full items-center gap-2 rounded-sm py-1.5 pl-6 pr-2 text-sm text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                        @click="select(neighborhood.id)"
                    >
                        {{ neighborhood.name }}
                        <Check v-if="modelValue === neighborhood.id" class="ml-auto size-4" />
                    </button>
                </template>

                <p v-if="filteredLocations.length === 0" class="px-2 py-4 text-center text-sm text-muted-foreground">
                    {{ t('Asnjë lokacion nuk u gjet.') }}
                </p>
            </div>
        </div>
    </div>
</template>
