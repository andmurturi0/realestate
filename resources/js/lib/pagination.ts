/**
 * Laravel's paginator always emits "Previous"/"Next" in English regardless of
 * the app locale (no lang/xx/pagination.php override is published), plus
 * &laquo;/&raquo; entities. Detect those semantically and swap in the given
 * words instead of rendering Laravel's raw label. Numeric page labels pass
 * through unchanged.
 */
export function paginationLabel(rawLabel: string, previousText: string, nextText: string): string {
    if (rawLabel.includes('Previous')) return `« ${previousText}`;
    if (rawLabel.includes('Next')) return `${nextText} »`;

    return rawLabel;
}
