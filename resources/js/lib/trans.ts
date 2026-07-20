// Lookup over lang/{sq,en,de}.json for the public site (Faza 8). Dashboard,
// auth and settings pages stay hardcoded Albanian and never call this.
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, type ComputedRef } from 'vue';
import de from '../../../lang/de.json';
import en from '../../../lang/en.json';
import sq from '../../../lang/sq.json';

export type Locale = 'sq' | 'en' | 'de';

type Dictionary = Record<string, string>;

const dictionaries: Record<Locale, Dictionary> = { sq, en, de };

/**
 * `t()` reads the current page's locale from the shared Inertia prop, so
 * every public component gets the right dictionary without passing it down.
 * `{name}` placeholders in the translated string are replaced from `params`.
 *
 * `locale` MUST stay a computed ref, not a plain value snapshotted at setup
 * time: switching locale via the header switcher is a client-side Inertia
 * visit (no full page reload), and components like PublicLayout persist
 * across it — a plain `const locale = usePage().props.locale` would freeze
 * at whatever locale first mounted the component instead of tracking changes.
 */
export function useTranslations(): { t: (key: string, params?: Record<string, string | number>) => string; locale: ComputedRef<Locale> } {
    const page = usePage<SharedData>();
    const locale = computed(() => page.props.locale);

    function t(key: string, params?: Record<string, string | number>): string {
        let text = dictionaries[locale.value][key] ?? key;

        if (params) {
            for (const [name, value] of Object.entries(params)) {
                text = text.replaceAll(`{${name}}`, String(value));
            }
        }

        return text;
    }

    return { t, locale };
}
