import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';

/**
 * Locale-aware equivalent of Ziggy's bare `route()` for the public site.
 * sq routes keep their plain names ("properties.show"); en/de are mirrored
 * server-side under "locale.properties.show" (routes/web.php). This picks
 * the right one for the current page's locale so every internal link stays
 * on the same locale without each call site juggling the "locale." prefix.
 *
 * `params` follows the same shapes call sites already use with the bare
 * `route()` helper: omitted, a single scalar (e.g. a property slug), or a
 * named params object.
 */
export function publicRoute(name: string, params?: Record<string, unknown> | string | number): string {
    const locale = usePage<SharedData>().props.locale;

    if (locale === 'sq') {
        return route(name, params);
    }

    const namedParams = params === undefined ? {} : typeof params === 'object' ? params : { property: params };

    return route(`locale.${name}`, { locale, ...namedParams });
}
