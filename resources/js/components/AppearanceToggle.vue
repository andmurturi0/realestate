<script setup lang="ts">
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';

// Shared by the public header and the dashboard sidebar header, which is why
// labels come in as a prop instead of this component reading useTranslations()
// itself — the dashboard stays hardcoded Albanian and never touches lang/*.json.
const props = withDefaults(
    defineProps<{
        labels?: { light: string; dark: string; system: string; toggle: string };
    }>(),
    {
        labels: () => ({ light: 'Light', dark: 'Dark', system: 'System', toggle: 'Toggle appearance' }),
    },
);

const { appearance, updateAppearance } = useAppearance();

const options = computed(() => [
    { value: 'light' as const, label: props.labels.light, Icon: Sun },
    { value: 'dark' as const, label: props.labels.dark, Icon: Moon },
    { value: 'system' as const, label: props.labels.system, Icon: Monitor },
]);

const ActiveIcon = computed(() => options.value.find((option) => option.value === appearance.value)?.Icon ?? Sun);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                :aria-label="labels.toggle"
                class="inline-flex size-9 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            >
                <component :is="ActiveIcon" class="size-4" />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="option in options"
                :key="option.value"
                :class="option.value === appearance ? 'text-foreground' : 'text-muted-foreground'"
                @click="updateAppearance(option.value)"
            >
                <component :is="option.Icon" class="mr-2 size-4" />
                <span>{{ option.label }}</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
