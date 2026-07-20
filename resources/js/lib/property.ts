// These labels are Albanian source text used both as dashboard display
// strings (unwrapped, Albanian-only by design — see CLAUDE.md) and as
// translation keys on the public site (wrapped in t() there, Faza 8).

import { type Locale } from '@/lib/trans';

export type PropertyCategory = 'house' | 'apartment' | 'office' | 'store' | 'land' | 'warehouse' | 'object';
export type ListingType = 'sale' | 'rent';
export type PropertyStatus = 'draft' | 'published' | 'reserved' | 'sold' | 'rented' | 'archived';

export const categoryLabels: Record<PropertyCategory, string> = {
    house: 'Shtëpi',
    apartment: 'Banesë',
    office: 'Zyrë',
    store: 'Lokal',
    land: 'Tokë',
    warehouse: 'Depo',
    object: 'Objekt',
};

export const listingTypeLabels: Record<ListingType, string> = {
    sale: 'Në shitje',
    rent: 'Me qira',
};

export const statusLabels: Record<PropertyStatus, string> = {
    draft: 'Draft',
    published: 'E publikuar',
    reserved: 'E rezervuar',
    sold: 'E shitur',
    rented: 'E lëshuar',
    archived: 'E arkivuar',
};

export const statusClasses: Record<PropertyStatus, string> = {
    draft: 'bg-muted text-muted-foreground',
    published: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    reserved: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    sold: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    rented: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    archived: 'bg-muted text-muted-foreground',
};

export type DetailField = 'land_surface_m2' | 'bedrooms' | 'bathrooms' | 'floor' | 'total_floors' | 'year_built' | 'parking_spaces';

/**
 * Which category-dependent detail fields each category accepts.
 * Mirror of PropertyCategory::allowedDetailFields() — keep in sync.
 * The FormRequest is the actual guard; this only drives show/hide.
 */
export const categoryDetailFields: Record<PropertyCategory, DetailField[]> = {
    house: ['land_surface_m2', 'bedrooms', 'bathrooms', 'total_floors', 'year_built', 'parking_spaces'],
    apartment: ['bedrooms', 'bathrooms', 'floor', 'total_floors', 'year_built', 'parking_spaces'],
    office: ['floor', 'total_floors', 'year_built', 'parking_spaces'],
    store: ['floor', 'total_floors', 'year_built', 'parking_spaces'],
    land: ['land_surface_m2'],
    warehouse: ['year_built', 'parking_spaces'],
    object: ['land_surface_m2', 'total_floors', 'year_built', 'parking_spaces'],
};

export const detailFieldLabels: Record<DetailField, string> = {
    land_surface_m2: 'Sipërfaqja e tokës (m²)',
    bedrooms: 'Dhoma gjumi',
    bathrooms: 'Banjo',
    floor: 'Kati',
    total_floors: 'Katet gjithsej',
    year_built: 'Viti i ndërtimit',
    parking_spaces: 'Vende parkimi',
};

const intlLocales: Record<Locale, string> = {
    sq: 'sq-AL',
    en: 'en-IE',
    de: 'de-DE',
};

export function formatPriceCents(cents: number | null | undefined, locale: Locale = 'sq'): string {
    if (cents == null) {
        return '—';
    }

    const euros = cents / 100;

    return new Intl.NumberFormat(intlLocales[locale], {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: cents % 100 === 0 ? 0 : 2,
    }).format(euros);
}
