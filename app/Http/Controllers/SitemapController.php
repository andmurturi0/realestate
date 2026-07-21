<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Models\Property;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const CACHE_KEY = 'sitemap.xml';

    private const CACHE_TTL = 3600;

    /**
     * Static (parameter-less) public pages included in the sitemap, in
     * every locale. `favorites` is user-specific (localStorage-backed) and
     * deliberately excluded — there's nothing for a crawler to index there.
     *
     * @var list<string>
     */
    private const STATIC_ROUTES = [
        'home',
        'properties.index',
        'about',
        'offer-property.create',
        'create-request.create',
    ];

    public function index(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn (): string => $this->build());

        return response($xml)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function build(): string
    {
        $urls = collect(self::STATIC_ROUTES)->map(fn (string $name) => $this->urlEntry($name));

        $properties = Property::query()->published()->select(['slug', 'updated_at'])->get();

        $urls = $urls->concat($properties->map(
            fn (Property $property) => $this->urlEntry('properties.show', ['property' => $property->slug], $property->updated_at)
        ));

        return view('sitemap', ['urls' => $urls])->render();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{loc: string, lastmod: ?string, alternates: array<string, string>}
     */
    private function urlEntry(string $routeName, array $params = [], ?Carbon $lastmod = null): array
    {
        $alternates = [];

        foreach (SetLocale::LOCALES as $locale) {
            $name = $locale === 'sq' ? $routeName : "locale.{$routeName}";
            $routeParams = $locale === 'sq' ? $params : ['locale' => $locale, ...$params];

            $alternates[$locale] = route($name, $routeParams);
        }

        return [
            'loc' => $alternates['sq'],
            'lastmod' => $lastmod?->toAtomString(),
            'alternates' => $alternates,
        ];
    }
}
