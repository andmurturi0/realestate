// Favourites live in the visitor's browser only — no auth, no table (PLAN.md §5).
// `ids` is module-level (not created per-call) so every card, the header
// count, and the /favorites page all share one reactive, localStorage-backed
// list: toggling a heart anywhere updates everywhere instantly.

import { useStorage } from '@vueuse/core';
import { computed } from 'vue';

const ids = useStorage<number[]>('favourites', []);

export function useFavourites() {
    function has(id: number): boolean {
        return ids.value.includes(id);
    }

    function add(id: number): void {
        if (!has(id)) {
            ids.value = [...ids.value, id];
        }
    }

    function remove(id: number): void {
        ids.value = ids.value.filter((existing) => existing !== id);
    }

    function toggle(id: number): void {
        if (has(id)) {
            remove(id);
        } else {
            add(id);
        }
    }

    const count = computed(() => ids.value.length);

    return { ids, has, add, remove, toggle, count };
}
