// Shared types + helpers for the public listing page (PLAN.md §5).
// UI strings are Albanian for now — Faza 8 moves them to lang files.

export type SortOption = 'newest' | 'price_asc' | 'price_desc' | 'surface_desc';

export const sortLabels: Record<SortOption, string> = {
    newest: 'Më të rejat',
    price_asc: 'Çmimi: më i ulëti',
    price_desc: 'Çmimi: më i larti',
    surface_desc: 'Sipërfaqja: më e madhja',
};

const sortOptions = Object.keys(sortLabels) as SortOption[];

export interface PropertyCardData {
    id: number;
    slug: string;
    reference_code: string;
    title: string;
    listing_type: 'sale' | 'rent';
    category: string;
    is_exclusive: boolean;
    price: number;
    price_negotiable: boolean;
    surface_m2: number | null;
    bedrooms: number | null;
    bathrooms: number | null;
    location: string | null;
    municipality: string | null;
    image_url: string | null;
    thumbnail_url: string | null;
}

export interface LocationOption {
    id: number;
    name: string;
    neighborhoods: { id: number; name: string }[];
}

export interface FurnishingOption {
    key: string;
    name: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    total: number;
    from: number | null;
    to: number | null;
    current_page: number;
}

/** The client-side filter state. Empty string / null / [] mean "not filtering". */
export interface ListingFilters {
    type: '' | 'sale' | 'rent';
    exclusive: boolean;
    category: string[];
    price_min: string;
    price_max: string;
    surface_min: string;
    surface_max: string;
    location: number | null;
    bedrooms: number | null;
    possession: '' | 'with' | 'without';
    documents: '' | 'notary' | 'lawyer';
    furnishing: string[];
    sort: SortOption;
}

export function emptyFilters(): ListingFilters {
    return {
        type: '',
        exclusive: false,
        category: [],
        price_min: '',
        price_max: '',
        surface_min: '',
        surface_max: '',
        location: null,
        bedrooms: null,
        possession: '',
        documents: '',
        furnishing: [],
        sort: 'newest',
    };
}

/**
 * Normalizes the server-echoed `filters` prop (PropertyFilter::active())
 * into UI state. An empty filter set arrives as a PHP `[]`, so every field
 * is read defensively.
 */
export function filtersFromProps(raw: Record<string, unknown> | unknown[] | undefined): ListingFilters {
    const source = (raw ?? {}) as Record<string, unknown>;
    const filters = emptyFilters();

    if (source.type === 'sale' || source.type === 'rent') filters.type = source.type;
    filters.exclusive = source.exclusive === '1' || source.exclusive === 1 || source.exclusive === true;
    if (Array.isArray(source.category)) filters.category = source.category.map(String);
    for (const key of ['price_min', 'price_max', 'surface_min', 'surface_max'] as const) {
        if (typeof source[key] === 'number' || typeof source[key] === 'string') filters[key] = String(source[key]);
    }
    if (typeof source.location === 'number') filters.location = source.location;
    if (typeof source.bedrooms === 'number') filters.bedrooms = source.bedrooms;
    if (source.possession === 'with' || source.possession === 'without') filters.possession = source.possession;
    if (source.documents === 'notary' || source.documents === 'lawyer') filters.documents = source.documents;
    if (Array.isArray(source.furnishing)) filters.furnishing = source.furnishing.map(String);
    if (sortOptions.includes(source.sort as SortOption)) filters.sort = source.sort as SortOption;

    return filters;
}

/** Builds the query params for a visit — inactive filters are omitted so URLs stay clean. */
export function filtersToQuery(filters: ListingFilters): Record<string, unknown> {
    const params: Record<string, unknown> = {};

    if (filters.type) params.type = filters.type;
    if (filters.exclusive) params.exclusive = '1';
    if (filters.category.length) params.category = filters.category;
    if (filters.price_min !== '') params.price_min = filters.price_min;
    if (filters.price_max !== '') params.price_max = filters.price_max;
    if (filters.surface_min !== '') params.surface_min = filters.surface_min;
    if (filters.surface_max !== '') params.surface_max = filters.surface_max;
    if (filters.location != null) params.location = filters.location;
    if (filters.bedrooms != null) params.bedrooms = filters.bedrooms;
    if (filters.possession) params.possession = filters.possession;
    if (filters.documents) params.documents = filters.documents;
    if (filters.furnishing.length) params.furnishing = filters.furnishing;
    if (filters.sort !== 'newest') params.sort = filters.sort;

    return params;
}
