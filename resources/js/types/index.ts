import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
    badge?: number | null;
}

export interface TranslatedContent {
    sq: string | null;
    en: string | null;
    de: string | null;
}

export interface AgencySettings {
    agency_name?: string | null;
    logo_path?: string | null;
    logo_dark_path?: string | null;
    favicon_path?: string | null;
    logo_url?: string | null;
    logo_dark_url?: string | null;
    favicon_url?: string | null;
    primary_color?: string | null;
    watermark_enabled?: boolean;
    phone?: string | null;
    whatsapp?: string | null;
    email?: string | null;
    address?: string | null;
    office_lat?: number | string | null;
    office_lng?: number | string | null;
    facebook?: string | null;
    instagram?: string | null;
    tiktok?: string | null;
    linkedin?: string | null;
    youtube?: string | null;
    app_store_url?: string | null;
    play_store_url?: string | null;
    [key: string]: unknown;
}

export interface InboxBadges {
    messages: number;
    offers: number;
    requests: number;
}

export interface SharedData {
    settings: AgencySettings;
    auth: Auth;
    can: {
        manageAgents: boolean;
        manageSettings: boolean;
    };
    badges: InboxBadges | null;
    flash: {
        success: string | null;
    };
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'agent';
    phone?: string | null;
    whatsapp?: string | null;
    avatar_path?: string | null;
    is_active?: boolean;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
