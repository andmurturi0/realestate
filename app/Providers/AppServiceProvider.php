<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('contact', fn (Request $request) => Limit::perHour(3)->by($request->ip()));
        RateLimiter::for('offer', fn (Request $request) => Limit::perHour(3)->by($request->ip()));
        RateLimiter::for('request', fn (Request $request) => Limit::perHour(3)->by($request->ip()));

        // Faza 10 §4: surface N+1s as exceptions everywhere except production,
        // where a lazy-loaded relation should degrade to an extra query, not a 500.
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
