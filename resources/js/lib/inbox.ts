// Shared types + helpers for the dashboard Inbox (PLAN.md §6, FAZAT.md Faza 7B).
// Dashboard UI stays Albanian-only by design (see CLAUDE.md).

export type LeadStatus = 'new' | 'contacted' | 'in_progress' | 'closed';
export type OfferStatus = 'new' | 'contacted' | 'in_progress' | 'converted' | 'rejected';

export const leadStatusLabels: Record<LeadStatus, string> = {
    new: 'E re',
    contacted: 'Kontaktuar',
    in_progress: 'Në proces',
    closed: 'Mbyllur',
};

export const offerStatusLabels: Record<OfferStatus, string> = {
    new: 'E re',
    contacted: 'Kontaktuar',
    in_progress: 'Në proces',
    converted: 'Konvertuar',
    rejected: 'Refuzuar',
};

export const leadStatusClasses: Record<string, string> = {
    new: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    contacted: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    in_progress: 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300',
    closed: 'bg-muted text-muted-foreground',
    converted: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
};

export interface AgentOption {
    id: number;
    name: string;
}

export interface LeadNoteData {
    id: number;
    body: string;
    author: string;
    created_at: string | null;
}

export interface LeadFilterState {
    status: string;
    assigned_to: string;
    date_from: string;
    date_to: string;
    search: string;
}

export function emptyLeadFilters(): LeadFilterState {
    return { status: '', assigned_to: '', date_from: '', date_to: '', search: '' };
}

export function leadFiltersToQuery(filters: LeadFilterState): Record<string, string> {
    const params: Record<string, string> = {};

    if (filters.status) params.status = filters.status;
    if (filters.assigned_to) params.assigned_to = filters.assigned_to;
    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;
    if (filters.search) params.search = filters.search;

    return params;
}

const relativeTimeFormatter = new Intl.RelativeTimeFormat('sq-AL', { numeric: 'auto' });

/** A compact "X min more parë" style relative timestamp for list rows. */
export function relativeTime(iso: string | null): string {
    if (!iso) return '—';

    const diffMs = new Date(iso).getTime() - Date.now();
    const diffMinutes = Math.round(diffMs / 60000);

    if (Math.abs(diffMinutes) < 60) return relativeTimeFormatter.format(diffMinutes, 'minute');

    const diffHours = Math.round(diffMinutes / 60);
    if (Math.abs(diffHours) < 24) return relativeTimeFormatter.format(diffHours, 'hour');

    const diffDays = Math.round(diffHours / 24);
    return relativeTimeFormatter.format(diffDays, 'day');
}

export function whatsAppMessageLink(phone: string, message: string): string {
    const digits = phone.replace(/[^0-9]/g, '');

    return `https://wa.me/${digits}?text=${encodeURIComponent(message)}`;
}
