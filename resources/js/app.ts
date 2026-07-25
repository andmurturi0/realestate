import '../css/app.css';

import { createInertiaApp, usePage } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { initializeTheme } from './composables/useAppearance';
import type { SharedData } from './types';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

createInertiaApp({
    // The agency name is never hardcoded (CLAUDE.md's single-tenant rule) — it
    // comes from the live `settings` share on every page, not a build-time env
    // var, so a rename in Settings → Branding takes effect immediately with no
    // rebuild, and an unconfigured agency gets a plain page title instead of a
    // stray product name.
    title: (title) => {
        const agencyName = usePage<SharedData>().props.settings?.agency_name;

        if (!agencyName) {
            return title;
        }

        return title ? `${title} - ${agencyName}` : agencyName;
    },
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
