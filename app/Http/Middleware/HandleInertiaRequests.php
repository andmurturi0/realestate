<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Settings keys holding whole page contents — too heavy to ship with every
     * response. Pages that need them receive them as their own props.
     *
     * @var list<string>
     */
    protected const CONTENT_KEYS = ['about_content', 'terms_content', 'privacy_content', 'faq_content'];

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'settings' => $this->sharedSettings(),
            'auth' => [
                'user' => $user,
            ],
            'can' => [
                'manageAgents' => $user?->can('viewAny', User::class) ?? false,
                'manageSettings' => $user?->isAdmin() ?? false,
            ],
            'badges' => $user ? $this->dashboardService->inboxBadgeCounts($user) : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            // Deferred: HandleInertiaRequests is global 'web' group middleware and
            // therefore runs before route-scoped SetLocale, so App::getLocale() must
            // be read lazily — at response-build time, after the full pipeline (SetLocale
            // included) has already run — not eagerly here in share().
            'locale' => fn () => App::getLocale(),
            'locales' => SetLocale::LOCALES,
        ];
    }

    /**
     * The cached settings array (one cache hit, zero queries), minus the heavy
     * content keys, with resolved URLs for the stored image paths.
     *
     * @return array<string, mixed>
     */
    protected function sharedSettings(): array
    {
        $settings = Arr::except(Setting::allAsArray(), self::CONTENT_KEYS);

        return $this->settingsService->withImageUrls($settings);
    }
}
