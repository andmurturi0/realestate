<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-card">
        <Skeleton class="aspect-[4/3] w-full rounded-none" />
        <div class="flex flex-col gap-2 p-4">
            <Skeleton class="h-7 w-28" />
            <Skeleton class="h-4 w-full" />
            <Skeleton class="h-4 w-2/3" />
            <div class="mt-1 flex items-center justify-between border-t pt-2.5">
                <Skeleton class="h-4 w-32" />
                <Skeleton class="h-4 w-16" />
            </div>
        </div>
    </div>
</template>
