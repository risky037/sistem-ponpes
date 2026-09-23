<?php

namespace App\Providers;

use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Setting;
use App\Models\Tabungan;
use App\Models\TransaksiTabungan;
use App\Models\Transfer;
use App\Models\User;
use App\Models\WaliSantri;
use App\Observers\KamarObserver;
use App\Observers\KelasObserver;
use App\Observers\SantriObserver;
use App\Observers\SettingObserver;
use App\Observers\TabunganObserver;
use App\Observers\TransaksiTabunganActivityObserver;
use App\Observers\TransferObserver;
use App\Observers\UserObserver;
use App\Observers\WaliSantriObserver;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Paginator::useBootstrapFive();

        Blade::directive('formatDate', function ($expression) {
            return "<?php echo \App\Helpers\Helper::formatDate($expression); ?>";
        });

        Blade::directive('formatDateTime', function ($expression) {
            return "<?php echo \App\Helpers\Helper::formatDateTime($expression); ?>";
        });

        User::observe(UserObserver::class);
        Setting::observe(SettingObserver::class);
        Kelas::observe(KelasObserver::class);
        Kamar::observe(KamarObserver::class);
        Tabungan::observe(TabunganObserver::class);
        Transfer::observe(TransferObserver::class);
        Santri::observe(SantriObserver::class);
        TransaksiTabungan::observe(TransaksiTabunganActivityObserver::class);
        WaliSantri::observe(WaliSantriObserver::class);

        Gate::policy(User::class, UserPolicy::class);

        Password::defaults(function () {
            return Password::min(8);
        });

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
