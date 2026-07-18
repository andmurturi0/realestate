import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

/**
 * Convert a #rrggbb hex colour to the "H S% L%" triplet format used by the
 * theme's CSS variables (consumed as hsl(var(--primary))).
 */
function hexToHslTriplet(hex: string): { triplet: string; lightness: number } | null {
    const match = /^#([0-9a-f]{6})$/i.exec(hex.trim());

    if (!match) {
        return null;
    }

    const r = parseInt(match[1].slice(0, 2), 16) / 255;
    const g = parseInt(match[1].slice(2, 4), 16) / 255;
    const b = parseInt(match[1].slice(4, 6), 16) / 255;

    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const l = (max + min) / 2;

    let h = 0;
    let s = 0;

    if (max !== min) {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);

        if (max === r) {
            h = (g - b) / d + (g < b ? 6 : 0);
        } else if (max === g) {
            h = (b - r) / d + 2;
        } else {
            h = (r - g) / d + 4;
        }

        h /= 6;
    }

    const hDeg = Math.round(h * 360);
    const sPct = Math.round(s * 100);
    const lPct = Math.round(l * 100);

    return { triplet: `${hDeg} ${sPct}% ${lPct}%`, lightness: lPct };
}

/**
 * Apply settings.primary_color to the theme's --primary CSS variable so every
 * bg-primary / text-primary element follows the agency's brand colour.
 */
export function useBrandColor(): void {
    const page = usePage<SharedData>();

    watchEffect(() => {
        if (typeof document === 'undefined') {
            return;
        }

        const color = page.props.settings?.primary_color;

        if (typeof color !== 'string') {
            return;
        }

        const hsl = hexToHslTriplet(color);

        if (!hsl) {
            return;
        }

        const root = document.documentElement;
        root.style.setProperty('--primary', hsl.triplet);
        root.style.setProperty('--primary-foreground', hsl.lightness > 60 ? '0 0% 9%' : '0 0% 98%');
    });
}
