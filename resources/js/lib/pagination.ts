/**
 * No lang/xx/pagination.php is published, so Laravel's paginator falls back
 * to the raw translation key itself — "pagination.previous"/"pagination.next"
 * (see LengthAwarePaginator::previous/nextPageUrl), not literal English text.
 * Detect those semantically and swap in the given words instead of rendering
 * Laravel's raw label. Numeric page labels pass through unchanged.
 */
export function paginationLabel(rawLabel: string, previousText: string, nextText: string): string {
    if (rawLabel.includes('pagination.previous')) return `« ${previousText}`;
    if (rawLabel.includes('pagination.next')) return `${nextText} »`;

    return rawLabel;
}
