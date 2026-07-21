<?php

namespace App\Services;

use App\Enums\FeatureGroup;
use App\Enums\LocationType;
use App\Http\Middleware\SetLocale;
use App\Models\Feature;
use App\Models\Location;
use Illuminate\Support\Facades\Cache;

/**
 * Location/feature dropdown options shared by every filter- and form-facing
 * page. Was previously duplicated (and re-queried on every request) across
 * Site\PropertyController, Site\PropertyOfferController,
 * Site\PropertyRequestController, Dashboard\PropertyController and
 * Dashboard\Inbox\OfferController — Faza 10 §5.
 */
class FacetOptionsService
{
    private const CACHE_PREFIX = 'facets';

    /**
     * Municipalities + neighborhoods for the public site, in the current
     * locale. Cached per locale since the translated names differ.
     *
     * @return list<array<string, mixed>>
     */
    public function municipalities(string $locale): array
    {
        return Cache::rememberForever(
            self::CACHE_PREFIX.'.municipalities.'.$locale,
            fn (): array => Location::query()
                ->where('type', LocationType::Municipality)
                ->with(['children' => fn ($query) => $query->orderBy('slug')])
                ->orderBy('slug')
                ->get()
                ->map(fn (Location $municipality): array => [
                    'id' => $municipality->id,
                    'name' => $municipality->getTranslation('name', $locale),
                    'neighborhoods' => $municipality->children->map(fn (Location $neighborhood): array => [
                        'id' => $neighborhood->id,
                        'name' => $neighborhood->getTranslation('name', $locale),
                    ])->all(),
                ])
                ->all()
        );
    }

    /**
     * The same municipalities/neighborhoods, plus coordinates, for the
     * (Albanian-only, CLAUDE.md) dashboard map picker.
     *
     * @return list<array<string, mixed>>
     */
    public function municipalitiesWithCoordinates(): array
    {
        return Cache::rememberForever(
            self::CACHE_PREFIX.'.municipalities.dashboard',
            fn (): array => Location::query()
                ->where('type', LocationType::Municipality)
                ->with(['children' => fn ($query) => $query->orderBy('slug')])
                ->orderBy('slug')
                ->get()
                ->map(fn (Location $municipality): array => [
                    'id' => $municipality->id,
                    'name' => $municipality->getTranslation('name', 'sq'),
                    'lat' => $municipality->lat,
                    'lng' => $municipality->lng,
                    'neighborhoods' => $municipality->children->map(fn (Location $neighborhood): array => [
                        'id' => $neighborhood->id,
                        'name' => $neighborhood->getTranslation('name', 'sq'),
                        'lat' => $neighborhood->lat,
                        'lng' => $neighborhood->lng,
                    ])->all(),
                ])
                ->all()
        );
    }

    /**
     * Furnishing features for the public listing filter, in the current
     * locale.
     *
     * @return list<array{key: string, name: string}>
     */
    public function furnishingFeatures(string $locale): array
    {
        return Cache::rememberForever(
            self::CACHE_PREFIX.'.furnishing.'.$locale,
            fn (): array => Feature::query()
                ->where('group', FeatureGroup::Furnishing)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Feature $feature): array => [
                    'key' => $feature->key,
                    'name' => $feature->getTranslation('name', $locale),
                ])
                ->all()
        );
    }

    /**
     * All features (every group), grouped by group, for the dashboard
     * property form.
     *
     * @return array<string, list<array{id: int, name: string}>>
     */
    public function featuresGroupedForDashboard(): array
    {
        return Cache::rememberForever(
            self::CACHE_PREFIX.'.features.dashboard',
            fn (): array => Feature::query()
                ->orderBy('sort_order')
                ->get()
                ->groupBy(fn (Feature $feature): string => $feature->group->value)
                ->map(fn ($group) => $group->map(fn (Feature $feature): array => [
                    'id' => $feature->id,
                    'name' => $feature->getTranslation('name', 'sq'),
                ])->values()->all())
                ->all()
        );
    }

    /**
     * Forgets every cached facet, across all locales. Called whenever a
     * Location or Feature is saved/deleted.
     */
    public static function flush(): void
    {
        foreach (SetLocale::LOCALES as $locale) {
            Cache::forget(self::CACHE_PREFIX.'.municipalities.'.$locale);
            Cache::forget(self::CACHE_PREFIX.'.furnishing.'.$locale);
        }

        Cache::forget(self::CACHE_PREFIX.'.municipalities.dashboard');
        Cache::forget(self::CACHE_PREFIX.'.features.dashboard');
    }
}
