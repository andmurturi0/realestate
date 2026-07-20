import { onMounted, ref } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

const COOKIE_NAME = 'appearance';
const COOKIE_MAX_AGE = 60 * 60 * 24 * 365;

// Plain (unencrypted) cookie, same convention as LanguageSwitcher.vue's `locale`
// cookie — app.blade.php reads it server-side to set the `dark` class before
// Vue mounts, so it must be excluded from EncryptCookies (see bootstrap/app.php).
function setCookie(value: Appearance) {
    document.cookie = `${COOKIE_NAME}=${value};path=/;max-age=${COOKIE_MAX_AGE};SameSite=Lax`;
}

function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
    return match ? decodeURIComponent(match[1]) : null;
}

function readStoredAppearance(): Appearance | null {
    return (getCookie(COOKIE_NAME) as Appearance | null) ?? (localStorage.getItem(COOKIE_NAME) as Appearance | null);
}

export function updateTheme(value: Appearance) {
    if (value === 'system') {
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.classList.toggle('dark', systemTheme === 'dark');
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
    }
}

const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

const handleSystemThemeChange = () => {
    updateTheme(readStoredAppearance() || 'light');
};

export function initializeTheme() {
    // Light is the default for first-time visitors (no cookie, no localStorage
    // yet) — deliberately not "system", so a visitor with a dark OS preference
    // still lands on light until they explicitly choose Dark or System.
    updateTheme(readStoredAppearance() || 'light');

    // Set up system theme change listener...
    mediaQuery.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    const appearance = ref<Appearance>('light');

    onMounted(() => {
        initializeTheme();

        const savedAppearance = readStoredAppearance();

        if (savedAppearance) {
            appearance.value = savedAppearance;
        }
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;
        localStorage.setItem(COOKIE_NAME, value);
        setCookie(value);
        updateTheme(value);
    }

    return {
        appearance,
        updateAppearance,
    };
}
