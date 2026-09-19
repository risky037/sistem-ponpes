# Laravel 11 Pre-Migration Provider Audit

**Phase:** 5.5.2A — Pre-Migration Hardening  
**Branch:** `refactor/laravel11-pre-migration-hardening`  
**Date:** 2026-09-19  
**Status:** Documentation Only — Providers NOT deleted (retained for Laravel 10 compatibility)

---

## Purpose

This document audits all legacy service providers that exist solely due to Laravel 10's
bootstrapping convention. In Laravel 11, these providers are either auto-discovered or
consolidated into `bootstrap/app.php`. They will be deleted during Phase 5.5.2B
(Bootstrap Migration).

---

## 1. AuthServiceProvider

**File:** `app/Providers/AuthServiceProvider.php`

**Current Contents:**
```php
protected $policies = [
    User::class => UserPolicy::class,
];
```

**Active Usage Analysis:**
- `User::class => UserPolicy::class` is the only mapping.
- Searched codebase: `UserPolicy` is referenced only within this provider.
- No routes or controllers call `Gate::policy()` or `authorize()` using `UserPolicy` explicitly.

**Laravel 11 Migration:**
Laravel 11 auto-discovers policies by naming convention:
- Model: `App\Models\User`
- Policy (auto-discovered): `App\Policies\UserPolicy`

The naming convention already matches — `UserPolicy` will be auto-discovered
**without any additional code changes**.

**Deletion Readiness:** ✅ SAFE TO DELETE  
**Migration Requirement:** None — Laravel 11 auto-discovery handles this.  
**Planned Action (Phase 5.5.2B):** Delete file.

---

## 2. EventServiceProvider

**File:** `app/Providers/EventServiceProvider.php`

**Current Contents:**
```php
protected $listen = [
    Registered::class => [
        SendEmailVerificationNotification::class,
    ],
];

public function shouldDiscoverEvents(): bool
{
    return false;
}
```

**Active Usage Analysis:**
- Only registers the default Laravel `Registered` event listener.
- `shouldDiscoverEvents()` returns `false` — disables auto-discovery in Laravel 10.
- No custom events or listeners registered.
- No code in `app/` dispatches `Registered` events manually.

**Laravel 11 Migration:**
Laravel 11 auto-discovers events by default. The `Registered` event + `SendEmailVerificationNotification` listener are part of the Laravel core and will work without this provider. `shouldDiscoverEvents()` is not needed in Laravel 11 (discovery is opt-out via `Event::withoutListenerAutoDiscovery()`).

**Deletion Readiness:** ✅ SAFE TO DELETE  
**Migration Requirement:** None — Laravel 11 handles this via auto-discovery.  
**Planned Action (Phase 5.5.2B):** Delete file.

---

## 3. RouteServiceProvider

**File:** `app/Providers/RouteServiceProvider.php`

**Current Contents:**
```php
public const HOME = '/dashboard';

// Rate limiters:
RateLimiter::for('api', ...);           // 60 req/min by user or IP
RateLimiter::for('sync-api', ...);      // 60 req/min with custom JSON 429 response

// Route loading:
Route::middleware('api')->prefix('v1')->group(base_path('routes/api.php'));
Route::middleware('web')->group(base_path('routes/web.php'));
```

**Active Usage Analysis:**
- `RouteServiceProvider::HOME` was used in `RedirectIfAuthenticated.php`.
  **Fixed in this PR:** now uses `self::HOME = '/dashboard'` directly.
- Rate limiters (`api`, `sync-api`) are active and protect the sync API endpoints.
- Route loading is required for the application to function.

**Cross-Reference Check:**
```
grep -rn "RouteServiceProvider" app/ → 0 results (fixed)
grep -rn "RouteServiceProvider" tests/ → 0 results
```

**Laravel 11 Migration:**
- Route loading moves to `bootstrap/app.php` `->withRouting(web:..., api:..., apiPrefix: 'v1')`.
- Rate limiters move to `AppServiceProvider::boot()`.
- `HOME` constant is retired — already removed from `RedirectIfAuthenticated.php`.

**Deletion Readiness:** ✅ SAFE TO DELETE (after Phase 5.5.2B changes)  
**Migration Requirement:** Port rate limiters to `AppServiceProvider::boot()` BEFORE deleting.  
**Planned Action (Phase 5.5.2B):** Migrate contents, then delete file.

---

## 4. BroadcastServiceProvider

**File:** `app/Providers/BroadcastServiceProvider.php`

**Current Contents:**
```php
public function boot(): void
{
    Broadcast::routes();
    require base_path('routes/channels.php');
}
```

**Active Usage Analysis:**
- **Not registered** in `config/app.php` providers list (commented out or absent).
- No `routes/channels.php` references found in application code.
- Broadcasting driver is set to `log` (from `php artisan about`).
- No controllers or services use `Broadcast::` or `event()->broadcastOn()`.

**Laravel 11 Migration:**
This provider is effectively dead code. It is not loaded, not registered, and the `log`
broadcast driver means no real broadcasting occurs. Safe to delete immediately.

**Deletion Readiness:** ✅ SAFE TO DELETE IMMEDIATELY  
**Migration Requirement:** None.  
**Planned Action (Phase 5.5.2B):** Delete file.

---

## 5. Summary Table

| Provider | Contents | L11 Equivalent | Deletion Readiness | Phase |
|:---|:---|:---|:---|:---|
| `AuthServiceProvider` | Policy map: `User → UserPolicy` | L11 auto-discovery by convention | ✅ Safe | 5.5.2B |
| `EventServiceProvider` | Default `Registered` event | L11 auto-discovery | ✅ Safe | 5.5.2B |
| `RouteServiceProvider` | Routes + rate limiters + HOME | `bootstrap/app.php` + `AppServiceProvider` | ✅ Safe after rate limiter port | 5.5.2B |
| `BroadcastServiceProvider` | Broadcast routes (inactive) | Not needed | ✅ Safe immediately | 5.5.2B |

---

## 6. Pre-Conditions Completed This Phase (5.5.2A)

The following blockers have been resolved, clearing the path for Phase 5.5.2B:

- [x] `RouteServiceProvider::HOME` dependency removed from `RedirectIfAuthenticated.php`
- [x] All `env()` calls routed through `config/google.php` and `config/whatsapp.php`
- [x] `phpunit.xml` updated: `CACHE_DRIVER` → `CACHE_STORE`
- [x] `config/cache.php` updated: reads `CACHE_STORE` with `CACHE_DRIVER` fallback
- [x] `config:cache` verified: succeeds without errors
- [x] Test suite: 92+ tests passing

---

*Document generated: 2026-09-19 — ASSESSMENT ONLY. Providers NOT modified.*
