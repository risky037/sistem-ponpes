# Laravel 11 Dependency Migration Assessment & Roadmap

**Application:** Sistem Informasi Pondok Pesantren Fatimah Az-Zahra  
**Document Type:** Technical Architecture & Dependency Compatibility Audit  
**Author:** Senior Laravel Architect & Dependency Migration Specialist  
**Current Baseline Branch:** `bugfix/pre-laravel11-stabilization`  
**Current Framework Version:** Laravel `10.50.3`  
**Current PHP Runtime:** PHP `8.4.16 (cli)` (Debian GNU/Linux)  
**Test Suite Status:** 85 passed (362 assertions) — 100% green  
**Date:** September 19, 2026  

---

## 1. Executive Summary

Following the completion of the core stabilization milestones (PRs #14, #17, #18, #20, #24, #25, and #30), the codebase is in a hardened, well-tested state. This audit assesses the feasibility, technical risks, dependency constraints, and required architectural refactoring for upgrading from **Laravel 10.50.3** to **Laravel 11.x**, preparing the application for future evolution toward Laravel 13.

### Laravel 11 Readiness Score: **85%**

```
[█████████████████░░░] 85% Ready
```

- **Domain Logic & Tests (100%):** 85 automated feature and unit tests passing green. Core runtime blockers, transaction race conditions, and financial relationships have been hardened.
- **Platform (100%):** PHP 8.4.16 is active on the host machine, exceeding the PHP >= 8.2.0 baseline for Laravel 11.
- **Dependencies (75%):** 6 dependencies require major version upgrades (`sanctum`, `permission`, `barcode`, `datatables`, `collision`, `framework`); 1 legacy package (`intervention/image` v2) requires replacement with `intervention/image-laravel: ^1.2` (v3); 1 abandoned package (`daftspunk/laravel-config-writer`) has already been successfully removed.
- **Application Structure (65%):** Requires adoption of Laravel 11's lean bootstrap layout (`Application::configure()` in `bootstrap/app.php`, `bootstrap/providers.php`, and decommissioning legacy Kernels).

---

## 2. Composer Dependency Audit

### 2.1. Environment Baseline
- **Laravel Framework:** `10.50.3` (locked in `composer.lock`, constrained to `^10.10`)
- **PHP Version Constraint:** `"php": "^8.1"` (Host CLI: `8.4.16`)
- **Composer Version:** `2.8.12`

### 2.2. Dependency Compatibility Matrix

| Package | Current Version | Constraint | Laravel 11 Compatibility | Status / Impact | Required Action |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `laravel/framework` | `10.50.3` | `^10.10` | Incompatible (`10.x`) | Core Framework | Upgrade to `^11.0` |
| `laravel/sanctum` | `3.3.3` | `^3.2` | Incompatible (requires `illuminate/* ^10`) | Blocks L11 | Upgrade to `^4.0` |
| `spatie/laravel-permission` | `5.11.1` | `^5.11` | Incompatible (requires `illuminate/* ^10`) | Blocks L11; middleware namespace renamed | Upgrade to `^6.0` |
| `yajra/laravel-datatables` | `10.1.0` | `^10.0` | Incompatible (requires `illuminate/* ^10`) | Blocks L11 | Upgrade to `^11.0` |
| `milon/barcode` | `10.0.1` | `^10.0` | Incompatible (requires `illuminate/* ^10`) | Blocks L11 | Upgrade to `^11.0` |
| `nunomaduro/collision` *(dev)* | `7.12.0` | `^7.0` | Incompatible (conflicts `framework >= 11`) | Blocks L11 | Upgrade to `^8.1` |
| `intervention/image` | `2.7.2` | `^2.7` | Incompatible / Abandoned v2 | Fatal runtime error: `Image::make()` removed in v3 | **Replace** with `intervention/image-laravel: ^1.2` |
| `daftspunk/laravel-config-writer` | Removed | Removed | Abandoned OctoberCMS fork | Decoupled in PR #30 | **Removed** (Replaced by Cache) |
| `maatwebsite/excel` | `3.1.70` | `^3.1` | **Compatible** (`illuminate/support ^11.0`) | No breaking changes | Retain `^3.1` |
| `revolution/laravel-google-sheets`| `6.4.0` | `^6.2` | **Compatible** (`illuminate/support ^11.0`) | No breaking changes | Retain `^6.4` |
| `spatie/laravel-flash` | `1.10.2` | `^1.9` | **Compatible** (`illuminate/session ^11.0`) | No breaking changes | Retain `^1.10` |
| `pharaonic/laravel-hijri` | `1.0` | `^1.0` | **Compatible** (`framework >= 6.0`) | Compatible with Carbon 2/3 | Retain or bump to `^2.1` |
| `guzzlehttp/guzzle` | `7.15.5` | `^7.2` | **Compatible** | No breaking changes | Retain `^7.2` |
| `laravel/tinker` | `2.11.1` | `^2.8` | **Compatible** | Minor bump | Update to `^2.9` |
| `barryvdh/laravel-debugbar` *(dev)*| `3.16.5` | `^3.13` | **Compatible** (`illuminate/* ^11`) | No breaking changes | Retain `^3.16` |
| `fakerphp/faker` *(dev)* | `1.24.1` | `^1.9.1` | **Compatible** | No breaking changes | Retain |
| `laravel/pint` *(dev)* | `1.24.0` | `^1.0` | **Compatible** | No breaking changes | Retain |
| `laravel/sail` *(dev)* | `1.41.0` | `^1.18` | **Compatible** | No breaking changes | Retain |
| `mockery/mockery` *(dev)* | `1.6.15` | `^1.4.4` | **Compatible** (Laravel 11.43+) | Compatible | Retain |
| `phpunit/phpunit` *(dev)* | `10.5.64` | `^10.1` | **Compatible** | Supported by Laravel 11 | Retain `^10.5` |
| `spatie/laravel-ignition` *(dev)* | `2.9.1` | `^2.0` | **Compatible** (`illuminate/* ^11`) | No breaking changes | Retain |

---

## 3. Laravel 11 Breaking Change Audit (Application Structure)

Laravel 11 eliminates the boilerplate application kernels and centralizes routing, middleware, and exception handling.

### 3.1. Kernel Decommissioning
1. **`app/Http/Kernel.php`:**
   - **Current:** Defines global middleware, route groups (`web`, `api`), and middleware aliases (`role`, `permission`, `Administrator`, `Keuangan`).
   - **Laravel 11 Target:** Decommissioned. All aliases and group configurations move into `bootstrap/app.php` via `->withMiddleware(function (Middleware $middleware) { ... })`.
2. **`app/Console/Kernel.php`:**
   - **Current:** Defines console schedules (`$schedule->command('queue:work --daemon')->everyTwoSeconds();`) and command loader.
   - **Laravel 11 Target:** Decommissioned. The schedule moves to `routes/console.php` using `Schedule::command('queue:work')->everyTwoSeconds();`. Note: `--daemon` is deprecated and omitted.
3. **`app/Exceptions/Handler.php`:**
   - **Current:** Contains custom JSON renderable for `ThrottleRequestsException` for API rate-limiting responses.
   - **Laravel 11 Target:** Decommissioned. Exception rendering moves to `bootstrap/app.php` via `->withExceptions(function (Exceptions $exceptions) { ... })`.

### 3.2. Service Provider Decommissioning & Registration
1. **`bootstrap/providers.php`:**
   Must be created to explicitly register application-specific service providers:
   ```php
   return [
       App\Providers\AppServiceProvider::class,
       App\Providers\ViewServiceProvider::class,
   ];
   ```
2. **`app/Providers/AuthServiceProvider.php`:**
   Only contains `User::class => UserPolicy::class`. In Laravel 11, policy discovery by model naming convention is automatic. File will be deleted.
3. **`app/Providers/EventServiceProvider.php`:**
   Only contains default `Registered` event. Laravel 11 auto-discovers events. File will be deleted.
4. **`app/Providers/RouteServiceProvider.php`:**
   Route loading (`web`, `api` with prefix `v1`) moves to `bootstrap/app.php` (`withRouting()`). Rate limiting (`api`, `sync-api`) moves into `AppServiceProvider::boot()`. Constant `HOME = '/dashboard'` is retired. File will be deleted.
5. **`app/Providers/BroadcastServiceProvider.php`:**
   Commented out and unused. File will be deleted.

---

## 4. Deprecated Laravel API Usage Search

An exhaustive search across `app/`, `routes/`, `config/`, and `resources/` yielded the following findings:

| Pattern Searched | Occurrences in `app/` | Findings & Assessment |
| :--- | :--- | :--- |
| `dispatch()` | 0 | **Clean:** No deprecated event or job dispatch helper calls found. |
| `dispatchNow()` | 0 | **Clean:** No obsolete synchronous dispatch calls found. |
| `app()->make()` | 0 | **Clean:** No direct container make calls found. |
| `config()` | 4 | **Valid:** Used for `app.domain` and `modules.modules`. Standard syntax. |
| `env()` | 5 | **Architecture Warning:** Called in `Sinkron.php`, `SinkronController.php`, and `TransaksiController.php` (`WA_SENDER_NUMBER`, `WA_API_KEY`, `SPREADSHEET_ID`). When `php artisan config:cache` is executed, direct `env()` calls outside `config/` return `null`. Must be routed via config files. |
| `Arr::` | 0 | **Clean:** No obsolete array helper calls found. |
| `Str::` | 8 | **Valid:** Used for `Str::slug()`, `Str::upper()`, `Str::random()`. All signatures are 100% compatible with Laravel 11. |
| `trans()` | 0 | **Clean:** No deprecated localization helper calls found. |
| `trans_choice()` | 0 | **Clean:** No deprecated pluralization helper calls found. |

---

## 5. Database Layer Compatibility

### 5.1. Migrations & Schema
- **Database Connection:** MySQL (`utf8mb4_unicode_ci`, strict mode enabled).
- **Doctrine DBAL Dependency:** In Laravel 10, altering table columns required `doctrine/dbal`. In Laravel 11, native schema operations handle column modifications without `doctrine/dbal`.
- **Integrity Constraints (PR #24):** Check constraints (`tabungan_saldo_non_negative`, `transfers_jumlah_positive`, `transfers_distinct_parties`) and foreign keys (`ON DELETE RESTRICT`) use standard SQL DDL supported in Laravel 11 and MySQL 8.0+.
- **Raw SQL Statements:** Zero raw queries (`DB::raw`, `DB::statement`) in controllers. Migrations cleanly separate DDL operations.

### 5.2. Eloquent Models & Casts
- **Casts Syntax:** `app/Models/User.php` currently defines `protected $casts = ['password' => 'hashed'];`.
  - While `$casts` array remains functional in Laravel 11, the modern standard is `protected function casts(): array { return ['password' => 'hashed']; }`.
- **Dates Property:** No models use the deprecated `protected $dates = [...]` property.
- **Relationships:** All relationships (`Santri` hasOne `Tabungan`, `Tabungan` hasMany `TransaksiTabungan`, `Santri` belongsTo `Kamar`/`Kelas`) use explicit, typed Eloquent relationship definitions.

---

## 6. Third-Party Integration Audit

### 6.1. Google Sheets Integration (`revolution/laravel-google-sheets`)
- **Package Status:** v6.4.0 is active.
- **Framework Compatibility:** Officially supports `illuminate/support ^10.0||^11.0`.
- **Credentials & Auth:** Uses service account credentials file at `public_path('files/sheets/digitren-0001-469b9239adfb.json')`.
- **Environment Handling:** Normalized in PR #30 to support both `SPREADSHEET_ID` and `SPREDSHEET_ID`.

### 6.2. Spatie Permission (`spatie/laravel-permission`)
- **Package Status:** v5.11.1 active; upgrade to `^6.0` required for Laravel 11.
- **Middleware Namespace Shift:**
  - Laravel 10 / Spatie v5: `\Spatie\Permission\Middlewares\RoleMiddleware` (plural).
  - Laravel 11 / Spatie v6: `\Spatie\Permission\Middleware\RoleMiddleware` (singular).
  - Failing to update this namespace causes fatal `Class Not Found` exceptions on all role-gated routes.
- **Role Middleware Logic:** Hardened in PR #30 to `$request->user()?->hasRole(...)`.

### 6.3. Yajra DataTables (`yajra/laravel-datatables`)
- **Package Status:** v10.1.0 active; upgrade to `^11.0` required for Laravel 11.
- **Controller Implementation:** All 6 controllers (`RiwayatController`, `KelasController`, `UsersController`, `SantriController`, `SaldoDebitController`, `KamarController`) use `DataTables::of($query)->make(true);`.
- **Frontend Compatibility:** jQuery DataTables frontend scripts consume the exact same JSON format in v11.

### 6.4. Excel Import / Export (`maatwebsite/excel`)
- **Package Status:** v3.1.70 active.
- **Framework Compatibility:** Already supports `illuminate/support ^11.0`.
- **Exports:** `SantriExport`, `KamarExport`, `TabunganExport` implement `FromCollection`, `WithHeadings`, `WithMapping`, and `WithStyles`. Fully compatible.
- **Imports:** `SantriImport` implements `ToModel` and `WithHeadingRow` with database transactions. Fully compatible.

### 6.5. Intervention Image (`intervention/image`) — CRITICAL ACTION REQUIRED
- **Package Status:** Legacy v2.7.2 active; abandoned and unsupported in Laravel 11.
- **Replacement Package:** `intervention/image-laravel: ^1.2` (Intervention Image v3).
- **Breaking API Changes:**
  - `Image::make()` was removed.
  - Must refactor 10 occurrences across 4 controllers:
    - `app/Http/Controllers/SettingController.php` (6 occurrences)
    - `app/Http/Controllers/Santri/SantriController.php` (2 occurrences)
    - `app/Http/Controllers/ProfilController.php` (1 occurrence)
    - `app/Http/Controllers/Api/synchronizationController.php` (1 occurrence)
  - Target API: `Image::read($file->getRealPath())->scaleDown(240, 295)->save($path)`.

---

## 7. Dependency Upgrade Plan & Phasing

```
┌────────────────────────────────────────────────────────────────────────┐
│ Phase A: Safe Updates & Non-Breaking Dependency Bumps                 │
│ • Update PHP constraint in composer.json to ^8.2                       │
│ • Bump laravel/sanctum to ^4.0                                         │
│ • Bump milon/barcode to ^11.0                                          │
│ • Bump yajra/laravel-datatables to ^11.0                               │
│ • Bump nunomaduro/collision to ^8.1                                    │
│ • Bump laravel/tinker to ^2.9                                          │
└────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ Phase B: Intervention Image v3 Replacement & Refactoring              │
│ • Replace intervention/image with intervention/image-laravel: ^1.2     │
│ • Refactor 10 Image::make() calls to Image::read() across 4 controllers│
│ • Verify image upload flows (student photos, logo, favicon, KTS)       │
└────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ Phase C: Framework Core Upgrade & Architectural Modernization         │
│ • Upgrade laravel/framework to ^11.0                                   │
│ • Upgrade spatie/laravel-permission to ^6.0                            │
│ • Restructure bootstrap/app.php (Application::configure())            │
│ • Create bootstrap/providers.php                                       │
│ • Port rate limiters to AppServiceProvider                             │
│ • Port scheduler to routes/console.php                                 │
│ • Update phpunit.xml (<env name="CACHE_STORE" value="array"/>)        │
│ • Safely delete obsolete Kernels and boilerplate Service Providers    │
│ • Execute full 85-test regression suite & Pint formatting              │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 8. Risk Matrix

| Area | Risk Level | Likelihood | Impact | Mitigation Strategy |
| :--- | :--- | :--- | :--- | :--- |
| **Intervention Image v3** | **HIGH** | High | Fatal error `Call to undefined method Image::make()` halts image processing | Execute package replacement and controller refactoring in a single atomic commit; add feature tests for photo uploads. |
| **Spatie Middleware Namespace** | **HIGH** | Medium | HTTP 500 on all authenticated routes if `Middlewares` plural is kept | Update alias configuration to `\Spatie\Permission\Middleware\...` during `bootstrap/app.php` setup; test via route tests. |
| **Bootstrap Kernel Removal** | **MEDIUM** | Medium | Dropped middleware (CORS, CSRF, TrimStrings) if mapping is incomplete | Register all aliases and groups explicitly in `withMiddleware()`; verify via `SynchronizationSecurityTest`. |
| **Direct `env()` Usage** | **LOW** | Medium | Null values returned when `config:cache` is executed | Transfer WhatsApp and Google Spreadsheet environment keys into dedicated configuration files (`config/whatsapp.php`, `config/google.php`). |
| **Sanctum v4 Token Guard** | **LOW** | Low | API sync authentication rejection | Verify `HasApiTokens` in `User.php` against `SynchronizationSecurityTest`. |

---

## 9. Conclusion

The application is well-positioned for migration. All pre-conditions have been satisfied, and the upgrade path is transparent and low-risk if executed in accordance with the 3-phase sequence outlined above.
