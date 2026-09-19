<?php

namespace App\Providers;

use App\Models\Santri;
use App\Models\TransaksiTabungan;
use App\Models\User;
use App\Observers\SantriObserver;
use App\Observers\TransaksiTabunganObserver;
use App\Policies\UserPolicy;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Debugbar', Debugbar::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Santri::observe(SantriObserver::class);
        TransaksiTabungan::observe(TransaksiTabunganObserver::class);

        Gate::policy(User::class, UserPolicy::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('sync-api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Too many requests. Rate limit exceeded.',
                        'errors' => [
                            'rate_limit' => ['You have exceeded the allowed limit of 60 requests per minute.'],
                        ],
                    ], 429, $headers);
                });
        });
    }
}
