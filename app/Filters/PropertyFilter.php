<?php

namespace App\Filters;

use App\Enums\DocumentType;
use App\Enums\FeatureGroup;
use App\Enums\ListingType;
use App\Enums\PropertyCategory;
use App\Http\Middleware\SetLocale;
use App\Models\Location;
use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Translates the public listing's query-string parameters into composable
 * Eloquent constraints (PLAN.md §5). Every parameter is whitelisted and
 * parsed defensively — an invalid value simply drops that filter, it never
 * errors and never widens visibility.
 */
class PropertyFilter
{
    /** @var list<string> */
    public const KEYS = [
        'type', 'exclusive', 'category', 'price_min', 'price_max',
        'surface_min', 'surface_max', 'location', 'bedrooms',
        'possession', 'documents', 'furnishing', 'sort', 'q',
    ];

    /** @var list<string> */
    public const SORTS = ['newest', 'price_asc', 'price_desc', 'surface_desc'];

    /** A pathological query ("a b c d e f g h...") can't nest an unbounded nest of subqueries. */
    private const MAX_SEARCH_WORDS = 5;

    /**
     * @param  array<string, mixed>  $params
     */
    public function __construct(private readonly array $params) {}

    public static function fromRequest(Request $request): self
    {
        return new self($request->only(self::KEYS));
    }

    /**
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    public function apply(Builder $query): Builder
    {
        return $query
            ->when($this->listingType(), fn (Builder $q, ListingType $type) => $q->where('listing_type', $type))
            ->when($this->wantsExclusive(), fn (Builder $q) => $q->where('is_exclusive', true))
            ->when($this->categories(), fn (Builder $q, array $categories) => $q->whereIn('category', $categories))
            ->when($this->priceCents('price_min'), fn (Builder $q, int $min) => $q->where('price', '>=', $min))
            ->when($this->priceCents('price_max'), fn (Builder $q, int $max) => $q->where('price', '<=', $max))
            ->when($this->numeric('surface_min'), fn (Builder $q, float $min) => $q->where('surface_m2', '>=', $min))
            ->when($this->numeric('surface_max'), fn (Builder $q, float $max) => $q->where('surface_m2', '<=', $max))
            ->when($this->locationId(), fn (Builder $q, int $id) => $this->applyLocation($q, $id))
            ->when($this->bedrooms(), fn (Builder $q, int $bedrooms) => $bedrooms >= 5
                ? $q->where('bedrooms', '>=', 5)
                : $q->where('bedrooms', $bedrooms))
            ->when($this->possession() !== null, fn (Builder $q) => $q->where('has_possession_sheet', $this->possession()))
            ->when($this->documentType(), fn (Builder $q, DocumentType $type) => $q->where('document_type', $type))
            ->when($this->furnishingKeys(), fn (Builder $q, array $keys) => $q->whereHas(
                'features',
                fn ($features) => $features->where('group', FeatureGroup::Furnishing)->whereIn('key', $keys)
            ))
            ->when($this->searchWords(), fn (Builder $q, array $words) => $this->applySearch($q, $words))
            ->tap(fn (Builder $q) => $this->applySort($q));
    }

    /**
     * The normalized, effective filter state — echoed back to the client so
     * the UI (and history entries) always reflect what actually filtered.
     *
     * @return array<string, mixed>
     */
    public function active(): array
    {
        return array_filter([
            'type' => $this->listingType()?->value,
            'exclusive' => $this->wantsExclusive() ? '1' : null,
            'category' => array_map(fn (PropertyCategory $category) => $category->value, $this->categories()),
            'price_min' => $this->numeric('price_min'),
            'price_max' => $this->numeric('price_max'),
            'surface_min' => $this->numeric('surface_min'),
            'surface_max' => $this->numeric('surface_max'),
            'location' => $this->locationId(),
            'bedrooms' => $this->bedrooms(),
            'possession' => $this->possessionParam(),
            'documents' => $this->documentType()?->value,
            'furnishing' => $this->furnishingKeys(),
            'sort' => $this->sort() === 'newest' ? null : $this->sort(),
            'q' => $this->search(),
        ], fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * A municipality matches its own id plus every neighborhood under it,
     * via a subselect so the query count stays constant.
     *
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    private function applyLocation(Builder $query, int $locationId): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('location_id', $locationId)
            ->orWhereIn('location_id', Location::query()->where('parent_id', $locationId)->select('id')));
    }

    /**
     * @param  Builder<Property>  $query
     */
    private function applySort(Builder $query): void
    {
        match ($this->sort()) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'surface_desc' => $query->orderByDesc('surface_m2'),
            default => $query->orderByDesc('published_at'),
        };

        $query->orderByDesc('id');
    }

    private function sort(): string
    {
        $sort = $this->params['sort'] ?? null;

        return in_array($sort, self::SORTS, true) ? $sort : 'newest';
    }

    private function listingType(): ?ListingType
    {
        return ListingType::tryFrom((string) ($this->params['type'] ?? ''));
    }

    private function wantsExclusive(): bool
    {
        return filter_var($this->params['exclusive'] ?? null, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return list<PropertyCategory>
     */
    private function categories(): array
    {
        $raw = $this->params['category'] ?? [];

        return collect(is_array($raw) ? $raw : [$raw])
            ->map(fn (mixed $value) => is_string($value) ? PropertyCategory::tryFrom($value) : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Prices travel in the URL as euros (what the user typed) and are
     * compared against the database in cents.
     */
    private function priceCents(string $key): ?int
    {
        $euros = $this->numeric($key);

        return $euros === null ? null : (int) round($euros * 100);
    }

    private function numeric(string $key): ?float
    {
        $value = $this->params[$key] ?? null;

        return is_numeric($value) && (float) $value >= 0 ? (float) $value : null;
    }

    private function locationId(): ?int
    {
        $value = $this->params['location'] ?? null;

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function bedrooms(): ?int
    {
        $value = $this->params['bedrooms'] ?? null;

        if (! is_numeric($value)) {
            return null;
        }

        $bedrooms = (int) $value;

        return $bedrooms >= 1 && $bedrooms <= 5 ? $bedrooms : null;
    }

    private function possession(): ?bool
    {
        return match ($this->possessionParam()) {
            'with' => true,
            'without' => false,
            default => null,
        };
    }

    private function possessionParam(): ?string
    {
        $value = $this->params['possession'] ?? null;

        return in_array($value, ['with', 'without'], true) ? $value : null;
    }

    private function documentType(): ?DocumentType
    {
        return DocumentType::tryFrom((string) ($this->params['documents'] ?? ''));
    }

    /**
     * Furnishing chips match ANY selected key (PLAN.md §5). Keys are matched
     * inside the furnishing group only, so arbitrary feature keys can't be
     * injected through this parameter.
     *
     * @return list<string>
     */
    private function furnishingKeys(): array
    {
        $raw = $this->params['furnishing'] ?? [];

        return collect(is_array($raw) ? $raw : [$raw])
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Each word must match at least one field (AND across words, OR across
     * fields per word) — "banesë dardani" needs both terms present
     * somewhere, but not necessarily in the same field.
     *
     * @param  Builder<Property>  $query
     * @param  list<string>  $words
     * @return Builder<Property>
     */
    private function applySearch(Builder $query, array $words): Builder
    {
        foreach ($words as $word) {
            $like = '%'.$word.'%';

            $query->where(function (Builder $q) use ($like) {
                foreach (SetLocale::LOCALES as $locale) {
                    $q->orWhereLike("title->{$locale}", $like);
                }

                $q->orWhereLike('reference_code', $like)
                    ->orWhereLike('address_line', $like)
                    ->orWhereHas('location', fn (Builder $location) => $this
                        ->matchLocationName($location, $like)
                        ->orWhereHas('parent', fn (Builder $parent) => $this->matchLocationName($parent, $like)));
            });
        }

        return $query;
    }

    /**
     * Callbacks passed to `whereHas()` are run against a query that's already
     * pre-seeded with the relation's correlation clause, via Eloquent's
     * `callScope()`: it groups every *new* where the callback adds into one
     * nested clause, using the FIRST new where's boolean for that whole
     * group. Since every locale here is `orWhereLike`, that first boolean is
     * "or" — without this method's own wrapping `where(...)`, the group
     * would attach to the correlation with "or" instead of "and", making the
     * EXISTS match any location in the table, not just the correlated one.
     *
     * @param  Builder<Location>  $query
     * @return Builder<Location>
     */
    private function matchLocationName(Builder $query, string $like): Builder
    {
        return $query->where(function (Builder $q) use ($like) {
            foreach (SetLocale::LOCALES as $locale) {
                $q->orWhereLike("name->{$locale}", $like);
            }
        });
    }

    private function search(): ?string
    {
        $value = $this->params['q'] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @return list<string>
     */
    private function searchWords(): array
    {
        $value = $this->search();

        if ($value === null) {
            return [];
        }

        return collect(preg_split('/\s+/', $value))
            ->filter(fn (string $word): bool => $word !== '')
            ->take(self::MAX_SEARCH_WORDS)
            ->values()
            ->all();
    }
}
