# Laravel 11 Compatibility Assessment Report
**System:** Sistem Informasi Pondok Pesantren Fatimah Az-Zahra  
**Document Type:** Technical Architecture Assessment  
**Author:** Senior Laravel Architect  
**Current Branch:** `bugfix/pre-laravel11-stabilization`  
**Date:** September 19, 2026  
**Status:** Complete — Ready for Review  

---

## 1. Executive Summary

This assessment evaluates the readiness of the **Sistem Informasi Pondok Pesantren Fatimah Az-Zahra** application for migration from **Laravel 10.50.3** to **Laravel 11.x**, establishing the technical foundation for future migration toward Laravel 13.

Following the successful completion of the pre-migration stabilization milestones:
- **PR #14:** Core runtime blockers (Users, Rooms, Classes)
- **PR #17:** Production debug cleanup & hardened exports
- **PR #18:** Exception handling hardening & structured logging
- **PR #20:** Financial transaction atomicity & concurrency guards
- **PR #24:** Database integrity constraints & foreign keys
- **PR #25:** Financial domain relationship refactoring (`Santri` hasOne `Tabungan`)

The application is in a stable, verified baseline state with **74 automated feature and unit tests passing green (336 assertions)**.

### Key Assessment Takeaways
1. **Platform Compatibility (Green):** The host environment runs **PHP 8.4.16**, satisfying Laravel 11's minimum requirement of **PHP >= 8.2.0**. All required PHP extensions pass platform requirement checks (`composer check-platform-reqs`).
2. **Blocking Dependencies (Action Required):** Five direct dependencies have major version constraints blocking Laravel 11 installation: `laravel/sanctum` (v3), `spatie/laravel-permission` (v5), `milon/barcode` (v10), `yajra/laravel-datatables` (v10), and `nunomaduro/collision` (v7).
3. **High-Impact Package Migration (Intervention Image):** `intervention/image: ^2.7` must be replaced with `intervention/image-laravel: ^1.2` (Intervention Image v3). Because Intervention Image v3 completely removed `Image::make()`, code-level refactoring is required across 4 controllers (`SettingController`, `SantriController`, `ProfilController`, `Api/synchronizationController`).
4. **Abandoned Package Removal (daftspunk/laravel-config-writer):** The abandoned package `daftspunk/laravel-config-writer` writes to `config/modules.php` at runtime in `SinkronController.php`. This breaks production config caching (`php artisan config:cache`) and must be removed and replaced with database/cache settings storage.
5. **Architectural Streamlining (Laravel 11 Skeleton):** Laravel 11 eliminates `app/Http/Kernel.php`, `app/Console/Kernel.php`, `app/Exceptions/Handler.php`, and default service providers (`AuthServiceProvider`, `EventServiceProvider`, `RouteServiceProvider`). The bootstrap flow will be centralized in `bootstrap/app.php` and `bootstrap/providers.php`.

---

## 2. Current Environment

| Component | Current State | Target / Laravel 11 Requirement | Assessment |
| :--- | :--- | :--- | :--- |
| **PHP Runtime** | `8.4.16 (cli)` (Debian Linux) | `^8.2 \| ^8.3 \| ^8.4` | **PASS** (Fully compatible) |
| **Framework Core** | `laravel/framework: 10.50.3` | `laravel/framework: ^11.0` | **UPGRADE REQUIRED** |
| **composer.json PHP constraint** | `"php": "^8.1"` | `"php": "^8.2"` | **UPDATE REQUIRED** |
| **Active Test Suite** | 74 passed (336 assertions) | 100% green post-upgrade | **BASELINE HEALTHY** |
| **Platform Extensions** | 22 extensions checked | `ext-ctype`, `ext-curl`, `ext-dom`, `ext-fileinfo`, `ext-gd`, `ext-libxml`, `ext-mbstring`, `ext-openssl`, `ext-pdo`, `ext-zip` | **PASS** (All present) |
| **Operating Branch** | `bugfix/pre-laravel11-stabilization` | Feature upgrade branch | **CLEAN** |

---

## 3. Dependency Compatibility Matrix

| Package | Current Locked | composer.json Constraint | Laravel 11 Support | Required Action | Breaking Changes / Impact |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `laravel/framework` | `10.50.3` | `^10.10` | `11.x` available | Upgrade to `^11.0` | Bootstrap, Kernels, Exception handling, Service Providers |
| `laravel/sanctum` | `3.3.3` | `^3.2` | `^4.0` | Upgrade to `^4.0` | Sanctum 3 restricts `illuminate/*` to `^9.21\|^10.0`. Sanctum 4 is required. |
| `spatie/laravel-permission` | `5.11.1` | `^5.11` | `^6.0` | Upgrade to `^6.0` | Middleware namespace changed (`Middlewares` -> `Middleware`). Migration updates. |
| `milon/barcode` | `10.0.1` | `^10.0` | `^11.0` | Upgrade to `^11.0` | v10 only supports `illuminate/support ^10.0`. v11 adds Laravel 11 support. |
| `yajra/laravel-datatables` | `10.1.0` | `^10.0` | `^11.0` | Upgrade to `^11.0` | v10 restricts to `illuminate/* ^10`. v11 required. |
| `intervention/image` | `2.7.2` | `^2.7` | Unsupported | **Replace** with `intervention/image-laravel: ^1.2` | v2 does not support Laravel 11 / Symfony 7. Complete API rewrite (`Image::make()` -> `Image::read()`). |
| `daftspunk/laravel-config-writer` | `1.2.2` | `^1.2` | Abandoned (3 yrs) | **Remove** | Modifies PHP files at runtime. Incompatible with `config:cache`. Replace with DB/cache. |
| `revolution/laravel-google-sheets` | `6.4.0` | `^6.2` | Supported (`^10\|^11`) | Retain (`^6.4`) | Compatible with Laravel 11. Hardcoded ID in `Sinkron.php` needs cleanup. |
| `maatwebsite/excel` | `3.1.70` | `^3.1` | Supported (`^11\|^12\|^13`)| Retain (`^3.1`) | Fully compatible with Laravel 11. |
| `spatie/laravel-flash` | `1.10.2` | `^1.9` | Supported (`^11\|^12\|^13`)| Retain (`^1.10`) | Fully compatible with Laravel 11 session driver. |
| `pharaonic/laravel-hijri` | `1.0` | `^1.0` | Open (`>=6.0`) | Retain or bump to `^2.1` | Uses `Carbon::mixin()`. Verify Carbon 2/3 compatibility in tests. |
| `guzzlehttp/guzzle` | `7.15.5` | `^7.2` | Supported | Retain (`^7.2`) | Fully compatible with Symfony 7 / HTTP foundation. |
| `laravel/tinker` | `2.11.1` | `^2.8` | Supported | Update to `^2.9` | Minor version bump. |
| `nunomaduro/collision` *(dev)* | `7.12.0` | `^7.0` | `^8.1` | Upgrade to `^8.1` | v7 explicitly conflicts with `laravel/framework >= 11.0.0`. |
| `barryvdh/laravel-debugbar` *(dev)*| `3.16.5` | `^3.13` | Supported (`^10\|^11\|^12`)| Retain (`^3.16`) | Fully compatible with Laravel 11 routing and session. |
| `fakerphp/faker` *(dev)* | `1.24.1` | `^1.9.1` | Supported | Retain | Fully compatible. |
| `laravel/pint` *(dev)* | `1.24.0` | `^1.0` | Supported | Retain | Fully compatible. |
| `laravel/sail` *(dev)* | `1.41.0` | `^1.18` | Supported | Retain | Fully compatible. |
| `mockery/mockery` *(dev)* | `1.6.15` | `^1.4.4` | Supported (L11.43+) | Retain | Fully compatible with Laravel 11. |
| `phpunit/phpunit` *(dev)* | `10.5.64` | `^10.1` | Supported | Retain `^10.5` | Supported by Laravel 11 (PHPUnit 10 or 11). |
| `spatie/laravel-ignition` *(dev)* | `2.9.1` | `^2.0` | Supported (`^10\|^11\|^12`)| Retain | Compatible with Laravel 11. |

---

## 4. Breaking Changes Impact

### 4.1. Bootstrap and Application Structure
Laravel 11 introduces a unified application configuration mechanism that eliminates the boilerplate HTTP Kernel, Console Kernel, and Exception Handler classes.

1. **`bootstrap/app.php` Restructuring:**
   - The Laravel 10 pattern instantiates `Illuminate\Foundation\Application` and binds `Http\Kernel`, `Console\Kernel`, and `Exceptions\Handler` as singletons.
   - The Laravel 11 pattern uses fluent builder configuration:
     ```php
     return Application::configure(basePath: dirname(__DIR__))
         ->withRouting(
             web: __DIR__.'/../routes/web.php',
             api: __DIR__.'/../routes/api.php',
             apiPrefix: 'v1',
             commands: __DIR__.'/../routes/console.php',
             health: '/up',
         )
         ->withMiddleware(function (Middleware $middleware) {
             // Aliases and custom middleware configuration
         })
         ->withExceptions(function (Exceptions $exceptions) {
             // Exception renderers
         })->create();
     ```
2. **Kernel Decommissioning:**
   - `app/Http/Kernel.php`: Removed. Middleware aliases (`role`, `permission`, `Administrator`, `Keuangan`) migrate to `bootstrap/app.php` `$middleware->alias(...)`.
   - `app/Console/Kernel.php`: Removed. Schedules migrate to `routes/console.php`.
   - `app/Exceptions/Handler.php`: Removed. The custom JSON response renderer for `ThrottleRequestsException` migrates to `bootstrap/app.php` `$exceptions->render(...)`.

### 4.2. Service Provider Decommissioning & Registration
Laravel 11 eliminates the default boilerplate providers in favor of automated discovery and `bootstrap/providers.php`.

1. **`bootstrap/providers.php` Creation:**
   A new file `bootstrap/providers.php` must be created containing application-specific providers:
   ```php
   return [
       App\Providers\AppServiceProvider::class,
       App\Providers\ViewServiceProvider::class,
   ];
   ```
2. **Decommissioned Providers:**
   - `app/Providers/AuthServiceProvider.php`: Contains only `User::class => UserPolicy::class`. Laravel 11 auto-discovers model policies by convention. This file can be safely removed.
   - `app/Providers/EventServiceProvider.php`: Contains only default `Registered` event. Laravel 11 auto-discovers events. This file can be safely removed.
   - `app/Providers/RouteServiceProvider.php`: Route registration moves to `bootstrap/app.php`. Rate limiting (`api`, `sync-api`) moves to `AppServiceProvider::boot()`. The `HOME = '/dashboard'` constant can be retired in favor of direct route redirects. This file can be safely removed.
   - `app/Providers/BroadcastServiceProvider.php`: Already commented out and unused. Can be removed.
3. **`AppServiceProvider` Expansion:**
   - Move rate limiter definitions (`RateLimiter::for('api')`, `RateLimiter::for('sync-api')`) from `RouteServiceProvider` to `AppServiceProvider::boot()`.
   - Keep model observers (`Santri::observe()`, `TransaksiTabunganObserver::observe()`) in `AppServiceProvider::boot()`.

### 4.3. Spatie Permission v5 to v6 Breaking Changes
1. **Namespace Pluralization Fix:**
   - In Spatie Permission v5, middleware used `Middlewares` (plural):
     - `\Spatie\Permission\Middlewares\RoleMiddleware::class`
     - `\Spatie\Permission\Middlewares\PermissionMiddleware::class`
     - `\Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class`
   - In Spatie Permission v6, the namespace changed to `Middleware` (singular):
     - `\Spatie\Permission\Middleware\RoleMiddleware::class`
     - `\Spatie\Permission\Middleware\PermissionMiddleware::class`
     - `\Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class`
   - Any reference using the old plural namespace will result in a fatal `Class Not Found` error.
2. **Role Middleware Alias Registration:**
   The aliases `'role'`, `'permission'`, and `'role_or_permission'` must be registered in `bootstrap/app.php` using the updated v6 namespace.

### 4.4. Intervention Image v2 to v3 Breaking Changes
Intervention Image v3 is a complete rewrite and is incompatible with v2.
1. `intervention/image: ^2.7` must be replaced with `intervention/image-laravel: ^1.2`.
2. `Image::make()` was completely removed in v3.
3. The legacy facade method `Image::make($file->getRealPath())->resize(240, 295, function ($c) { $c->aspectRatio(); })->save($path)` must be refactored to:
   ```php
   Image::read($file)->scaleDown(240, 295)->save($path);
   ```
4. Affects 10 calls across 4 controllers:
   - `SettingController` (6 calls: logo, favicon, kts_master)
   - `SantriController` (2 calls: student photos)
   - `ProfilController` (1 call: user profile photo)
   - `Api/synchronizationController` (1 call: sync student photo)

### 4.5. Environment and Configuration Variables
1. **Cache Driver Renaming:**
   - In Laravel 11, `CACHE_DRIVER` is superseded by `CACHE_STORE`.
   - `phpunit.xml`: `<env name="CACHE_DRIVER" value="array"/>` must be updated to `<env name="CACHE_STORE" value="array"/>`.
2. **Queue Schedule:**
   - `app/Console/Kernel.php` contains:
     ```php
     $schedule->command('queue:work --daemon')->everyTwoSeconds();
     ```
   - In modern Laravel, `--daemon` is deprecated and unneeded (`queue:work` is a daemon by default).
   - In Laravel 11, this definition moves to `routes/console.php` using the `Schedule` facade:
     ```php
     Schedule::command('queue:work')->everyTwoSeconds();
     ```

---

## 5. Required Refactoring List

### 5.1. Pre-Upgrade Code Preparation (Before `composer update`)
- [ ] **[MODIFY] `app/Http/Middleware/Admin.php`:**
  - Replace brittle single-role check `auth()->user()->roles->first()->name == 'Administrator'` with `$request->user()?->hasRole('Administrator')`.
- [ ] **[MODIFY] `app/Http/Middleware/Keuangan.php`:**
  - Replace `auth()->user()->roles->first()->name == 'Keuangan'` with `$request->user()?->hasRole('Keuangan')`.
- [ ] **[MODIFY] `app/Http/Middleware/Santri.php`:**
  - Replace `auth()->user()->roles->first()->name == 'Santri'` with `$request->user()?->hasRole('Santri')`.
- [ ] **[MODIFY] `app/Http/Controllers/Sinkron/SinkronController.php`:**
  - Remove `\Config::write('modules.modules.santri', $request->data);` (line 103).
  - Store module sync timestamp in database `Setting` model or application cache.
- [ ] **[MODIFY] `app/Helpers/Sinkron.php`:**
  - Replace hardcoded placeholder `'YOUR_SPREADSHEET_ID'` on lines 26, 88, and 118 with `$sheet_id`.

### 5.2. Dependency Upgrades (`composer.json`)
- [ ] **[MODIFY] `composer.json`:**
  - `"php": "^8.2"`
  - `"laravel/framework": "^11.0"`
  - `"laravel/sanctum": "^4.0"`
  - `"spatie/laravel-permission": "^6.0"`
  - `"milon/barcode": "^11.0"`
  - `"yajra/laravel-datatables": "^11.0"`
  - Replace `"intervention/image": "^2.7"` with `"intervention/image-laravel": "^1.2"`
  - Remove `"daftspunk/laravel-config-writer": "^1.2"`
  - `"nunomaduro/collision": "^8.1"` (require-dev)

### 5.3. Bootstrap & Kernel Architecture Refactoring
- [ ] **[MODIFY] `bootstrap/app.php`:**
  - Implement `Application::configure(basePath: dirname(__DIR__))`.
  - Configure `withRouting(web: ..., api: ..., apiPrefix: 'v1', commands: ..., health: '/up')`.
  - Configure `withMiddleware()`: register aliases (`role`, `permission`, `role_or_permission`, `Administrator`, `Keuangan`).
  - Configure `withExceptions()`: register `ThrottleRequestsException` custom JSON renderer.
- [ ] **[NEW] `bootstrap/providers.php`:**
  - Register `App\Providers\AppServiceProvider::class` and `App\Providers\ViewServiceProvider::class`.
- [ ] **[MODIFY] `app/Providers/AppServiceProvider.php`:**
  - Port rate limiters (`api` and `sync-api`) from `RouteServiceProvider`.
  - Maintain existing paginator setup and observer bindings.
- [ ] **[MODIFY] `routes/console.php`:**
  - Port scheduler declaration: `Schedule::command('queue:work')->everyTwoSeconds();`.
- [ ] **[DELETE] `app/Http/Kernel.php`**
- [ ] **[DELETE] `app/Console/Kernel.php`**
- [ ] **[DELETE] `app/Exceptions/Handler.php`**
- [ ] **[DELETE] `app/Providers/AuthServiceProvider.php`**
- [ ] **[DELETE] `app/Providers/EventServiceProvider.php`**
- [ ] **[DELETE] `app/Providers/RouteServiceProvider.php`**
- [ ] **[DELETE] `app/Providers/BroadcastServiceProvider.php`**

### 5.4. Image Manipulation API Migration (Intervention Image v3)
- [ ] **[MODIFY] `app/Http/Controllers/SettingController.php`:**
  - Refactor lines 36, 51, 66, 103, 120, 137 from `Image::make(...)->resize(...)` to `Image::read(...)->scaleDown(...)`.
- [ ] **[MODIFY] `app/Http/Controllers/Santri/SantriController.php`:**
  - Refactor lines 107, 203 to `Image::read(...)->scaleDown(...)`.
- [ ] **[MODIFY] `app/Http/Controllers/ProfilController.php`:**
  - Refactor line 60 to `Image::read(...)->scaleDown(...)`.
- [ ] **[MODIFY] `app/Http/Controllers/Api/synchronizationController.php`:**
  - Refactor line 156 to `Image::read(...)->scaleDown(...)`.

### 5.5. Configuration, Testing & Model Modernization
- [ ] **[MODIFY] `phpunit.xml`:**
  - Update `<env name="CACHE_DRIVER" value="array"/>` to `<env name="CACHE_STORE" value="array"/>`.
- [ ] **[MODIFY] `app/Models/User.php`:**
  - Convert `protected $casts = [...]` to `protected function casts(): array`.
- [ ] **[MODIFY] `app/Http/Middleware/RedirectIfAuthenticated.php`:**
  - Remove `RouteServiceProvider::HOME` reference and redirect to `/dashboard` directly.

---

## 6. Risk Assessment

| Risk Item | Severity | Likelihood | Impact Description | Mitigation Strategy |
| :--- | :--- | :--- | :--- | :--- |
| **Intervention Image v3 Fatal Errors** | **HIGH** | **HIGH** | `Image::make()` fatal method call errors on photo uploads across 4 controllers. | Complete controller refactoring to `Image::read()` in the exact same PR as package update; add automated image upload regression tests. |
| **Spatie Permission v6 Namespace Break** | **HIGH** | **MEDIUM** | If `Middlewares` (plural) is retained, any role-gated web route or sync API returns HTTP 500. | Update alias configuration in `bootstrap/app.php` to `\Spatie\Permission\Middleware\...`; run route test suite. |
| **Daftspunk Config Writer Runtime Failure** | **MEDIUM** | **LOW** | Attempting to rewrite config files in cached/containerized production environments causes failures. | Eliminate package and switch to saving sync module timestamps in database or cache. |
| **Bootstrap Middleware Misconfiguration** | **MEDIUM** | **MEDIUM** | Missing CSRF, session, or route aliases after deleting `app/Http/Kernel.php`. | Carefully map all aliases into `bootstrap/app.php`; execute `SynchronizationSecurityTest` and `LoginSecurityTest`. |
| **Rate Limiter Displacement** | **LOW** | **LOW** | If `sync-api` rate limiter is lost during `RouteServiceProvider` deletion, API brute-force protection drops. | Transfer rate limiter definitions directly into `AppServiceProvider::boot()`; verify via `SynchronizationSecurityTest`. |

---

## 7. Migration Sequence Recommendation

To minimize risk and ensure full test traceability, the migration should be executed in **three sequential, isolated stages**:

```
Stage 1: Pre-Upgrade Refactoring
├── Refactor custom role middleware (Admin, Keuangan, Santri)
├── Decouple SinkronController from daftspunk/laravel-config-writer
└── Fix placeholder spreadsheet ID in Sinkron.php
    └── VERIFY: All 74 tests pass green

Stage 2: Core Framework & Dependencies Upgrade
├── Bump composer.json (PHP ^8.2, Laravel 11, Sanctum 4, Spatie 6, Barcode 11, DataTables 11, Collision 8)
├── Replace intervention/image with intervention/image-laravel
├── Refactor 4 controllers to Intervention Image v3 API
└── Run composer update
    └── VERIFY: composer update --dry-run & composer update succeed

Stage 3: Architectural Skeleton Modernization & Validation
├── Implement Application::configure() in bootstrap/app.php
├── Create bootstrap/providers.php
├── Migrate rate limiters to AppServiceProvider
├── Migrate scheduler to routes/console.php
├── Update phpunit.xml (CACHE_STORE)
├── Safely remove legacy Kernels and redundant Service Providers
└── Full test suite & Pint formatting
    └── VERIFY: php artisan test (100% green) & ./vendor/bin/pint --test
```

---

## 8. Conclusion

The application is in an ideal position for Laravel 11 migration thanks to previous stabilization work. The path to Laravel 11 is well-defined and carries no unresolvable architectural blockers.

With the approval of this assessment report, implementation will proceed systematically according to the three stages outlined above.
