// Minimal lookup over lang/{sq,en,de}.json for frontend-only components that
// need translated labels ahead of Faza 8 (full SQ/EN/DE localisation with URL
// prefixes and a locale switcher). Once that lands, `locale` should be driven
// by the shared Inertia prop it introduces instead of the sq default below.
import de from '../../../lang/de.json';
import en from '../../../lang/en.json';
import sq from '../../../lang/sq.json';

export type Locale = 'sq' | 'en' | 'de';

type Dictionary = Record<string, string>;

const dictionaries: Record<Locale, Dictionary> = { sq, en, de };

const DEFAULT_LOCALE: Locale = 'sq';

export function useTranslations(locale: Locale = DEFAULT_LOCALE) {
    const dictionary = dictionaries[locale];

    function t(key: string): string {
        return dictionary[key] ?? key;
    }

    return { t };
}
