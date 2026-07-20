<script setup lang="ts">
import { type FeatureData } from '@/lib/detail';
import { useTranslations } from '@/lib/trans';
import { Check, FileCheck2, FileQuestion, FileX2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    features: Record<string, FeatureData[]>;
    hasPossessionSheet: boolean | null;
    documentType: 'notary' | 'lawyer' | null;
}>();

const { t } = useTranslations();

const groupLabels: Record<string, string> = {
    infrastructure: 'Infrastruktura',
    furnishing: 'Mobilimi',
};

const documentTypeLabels: Record<'notary' | 'lawyer', string> = {
    notary: 'Noter',
    lawyer: 'Avokat',
};

const possessionLabel = computed(() => {
    if (props.hasPossessionSheet === true) return { text: t('Ka fletëposedim'), icon: FileCheck2 };
    if (props.hasPossessionSheet === false) return { text: t('Pa fletëposedim'), icon: FileX2 };

    return { text: t('Fletëposedimi i panjohur'), icon: FileQuestion };
});

const hasAnything = computed(
    () => Object.values(props.features).some((group) => group.length > 0) || props.hasPossessionSheet !== null || props.documentType !== null,
);
</script>

<template>
    <div v-if="hasAnything">
        <h2 class="mb-4 text-lg font-medium">{{ t('Karakteristikat dhe informacione për pronën') }}</h2>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div v-for="(items, group) in features" :key="group">
                <template v-if="items.length">
                    <h3 class="mb-2 text-sm font-medium text-muted-foreground">{{ t(groupLabels[group] ?? group) }}</h3>
                    <ul class="space-y-1.5">
                        <li v-for="feature in items" :key="feature.key" class="flex items-center gap-2 text-sm">
                            <Check class="size-4 shrink-0 text-primary" />
                            {{ feature.name }}
                        </li>
                    </ul>
                </template>
            </div>
        </div>

        <div v-if="hasPossessionSheet !== null || documentType !== null" class="mt-4 flex flex-wrap gap-4 border-t pt-4 text-sm">
            <span class="flex items-center gap-2">
                <component :is="possessionLabel.icon" class="size-4 text-muted-foreground" />
                {{ possessionLabel.text }}
            </span>
            <span v-if="documentType" class="flex items-center gap-2">
                <FileCheck2 class="size-4 text-muted-foreground" />
                {{ t('Dokumentacioni: {type}', { type: t(documentTypeLabels[documentType]) }) }}
            </span>
        </div>
    </div>
</template>
