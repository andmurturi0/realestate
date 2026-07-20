// Types + helpers for the public property detail page (FAZAT.md Faza 6A).

export interface PropertyImageData {
    id: number;
    url: string | null;
    thumbnail_url: string | null;
    alt: string | null;
    is_primary: boolean;
}

export interface FeatureData {
    key: string;
    name: string;
    icon: string | null;
}

export interface PriceHistoryPoint {
    date: string;
    price: number;
}

export interface PropertyLocationData {
    name: string;
    municipality: string;
    lat: number;
    lng: number;
}

export interface PropertyAgentData {
    name: string;
    phone: string | null;
    whatsapp: string | null;
    avatar_url: string | null;
}

export interface PropertyDetailData {
    id: number;
    slug: string;
    reference_code: string;
    title: string;
    description: string | null;
    listing_type: 'sale' | 'rent';
    category: string;
    is_exclusive: boolean;
    price: number;
    price_negotiable: boolean;
    surface_m2: number | null;
    land_surface_m2: number | null;
    bedrooms: number | null;
    bathrooms: number | null;
    floor: number | null;
    total_floors: number | null;
    year_built: number | null;
    parking_spaces: number | null;
    has_possession_sheet: boolean | null;
    document_type: 'notary' | 'lawyer' | null;
    published_at: string | null;
    views_count: number;
    location: PropertyLocationData | null;
    agent: PropertyAgentData | null;
    images: PropertyImageData[];
    features: Record<string, FeatureData[]>;
    price_history: PriceHistoryPoint[];
}

/** Digits-only phone, for wa.me links (which reject spaces/+/dashes inconsistently across clients). */
function digitsOnly(phone: string): string {
    return phone.replace(/[^0-9]/g, '');
}

export function whatsAppLink(phone: string, message: string): string {
    return `https://wa.me/${digitsOnly(phone)}?text=${encodeURIComponent(message)}`;
}
