# Phase 5.5.3A — Laravel 11 Dependency Finalization Assessment Report

**System:** Sistem Informasi Pondok Pesantren Fatimah Az-Zahra  
**Document Type:** Dependency Compatibility & Finalization Assessment  
**Author:** Senior Laravel Framework Migration Engineer  
**Current Branch:** `bugfix/pre-laravel11-stabilization`  
**Date:** September 19, 2026  
**Status:** Complete — Awaiting Approval Before Implementation  

---

## 1. Executive Summary

This assessment completes the technical evaluation of the deferred Laravel 11 coupled dependencies:
1. **`laravel/sanctum`** (v3.x → v4.x)
2. **`yajra/laravel-datatables`** (v10.x → v11.x)
3. **`nunomaduro/collision`** (v7.x → v8.x)

Following the successful merge of **PR #35** (Dependency baseline upgrade to Laravel 11) and **PR #36** (Laravel 11 bootstrap architecture migration), the application is currently operating on:
- **Laravel Framework:** `11.56.1`
- **PHP Runtime:** `8.4.16` (CLI)
- **Active Test Suite:** `92 passed (420 assertions)` — 100% green, 0 failures.

While the `composer.json` requirements and `composer.lock` versions were already bumped during Phase 5.5.2B-1 (`laravel/sanctum: ^4.0`, `yajra/laravel-datatables: ^11.0`, `nunomaduro/collision: ^8.1`), this audit identifies latent code-level breaking changes, configuration misalignments, schema drift in migrations, and controller inconsistencies that must be finalized to ensure production reliability.

### Key Assessment Findings:
1. **Sanctum v4:** The `User` model (`HasApiTokens`) and API routes (`/v1/sync/*`) are fully functional. However, `config/sanctum.php` contains legacy Laravel 10 middleware keys (`verify_csrf_token` referencing custom app middleware) instead of Sanctum 4 contracts (`validate_csrf_token`). Furthermore, the published migration `2019_12_14_000001_create_personal_access_tokens_table.php` lacks the `expires_at` column index introduced in Sanctum 4.0.
2. **Yajra DataTables v11:** 6 of 7 controllers correctly use `Yajra\DataTables\Facades\DataTables::of()`. One controller (`TransferController.php`) uses the procedural helper `datatables()->of()`. All 7 Blade views and AJAX responses return standard DataTables JSON structures. Legacy provider `DataTablesServiceProvider::class` in `config/app.php` is redundant due to package auto-discovery.
3. **Collision v8:** Installed version `8.5.0` seamlessly integrates with PHPUnit `10.5.64` and Symfony Console `7.4.19`. Console exception handling and test execution formatting function without error.
4. **Platform & Package Conflicts:** Zero package or Illuminate component conflicts exist. All direct and transitive packages satisfy Laravel 11 requirements.

---

## 2. Current Dependency State

### 2.1 Core Environment & Platform Baseline

| Component | Active Version | Constraint / Target | Compatibility Status |
| :--- | :--- | :--- | :--- |
| **PHP** | `8.4.16` | `^8.2` | **PASS** — Exceeds minimum requirement |
| **Laravel Framework** | `11.56.1` | `^11.0` | **PASS** — Fully installed & booted |
| **Symfony Console** | `7.4.19` | `^7.0` | **PASS** — Fully compatible |
| **Symfony HttpFoundation** | `7.4.19` | `^7.0` | **PASS** — Fully compatible |
| **Test Suite Baseline** | `10.5.64` (PHPUnit) | `^10.1` | **PASS** — 92 passed, 420 assertions |

### 2.2 Direct Packages Audit

| Package | Installed Version | composer.json | Outdated Major Available? | Framework Compatibility |
| :--- | :--- | :--- | :--- | :--- |
| `laravel/sanctum` | `4.3.3` | `^4.0` | No (`4.3.3` is current v4) | **100% Compatible** |
| `yajra/laravel-datatables` | `11.0.0` | `^11.0` | Yes (v13 exists for L12/L13) | **100% Compatible** with L11 |
| `yajra/laravel-datatables-oracle` | `11.1.6` | Subpackage | Yes (v13 exists) | **100% Compatible** with L11 |
| `nunomaduro/collision` | `8.5.0` | `^8.1` | Minor (`8.9.5` available) | **100% Compatible** with L11 |
| `spatie/laravel-permission` | `6.25.0` | `^6.0` | Yes (v8 exists) | **100% Compatible** with L11 |
| `intervention/image-laravel` | `1.5.9` | `^1.5` | Yes (v4 exists) | **100% Compatible** (Migrated to v3 core) |
| `milon/barcode` | `11.0.1` | `^11.0` | Yes (v13 exists) | **100% Compatible** with L11 |
| `maatwebsite/excel` | `3.1.70` | `^3.1` | Yes (v4 exists) | **100% Compatible** with L11 |
| `revolution/laravel-google-sheets`| `6.4.0` | `^6.2` | Yes (v7 exists) | **100% Compatible** with L11 |
| `pharaonic/laravel-hijri` | `1.0` | `^1.0` | Yes (v2 exists) | **100% Compatible** (`>=6.0` constraint) |
| `spatie/laravel-flash` | `1.10.2` | `^1.9` | No (latest v1) | **100% Compatible** with L11 |
| `barryvdh/laravel-debugbar` | `3.16.5` | `^3.13` | Yes (v4 exists) | **100% Compatible** with L11 |
| `spatie/laravel-ignition` | `2.12.0` | `^2.0` | No (latest v2) | **100% Compatible** with L11 |

---

## 3. Upgrade Compatibility Matrix

### 3.1 Focused Dependencies Matrix

| Target Package | Requires PHP | Requires Illuminate | Symfony Constraint | Conflicts | Installed Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **`laravel/sanctum: ^4.0`** | `^8.2` | `^11.0\|^12.0\|^13.0` | `symfony/console: ^7.0\|^8.0` | None | `v4.3.3` (Installed) |
| **`yajra/laravel-datatables: ^11.0`** | `^8.2` | `^11` (all illuminate packages) | `symfony/*: ^7.0` | None | `v11.0.0` (Installed) |
| **`nunomaduro/collision: ^8.1`** | `^8.2.0`| Compatible via testbench | `symfony/console: ^7.1.5` | `laravel/framework <11.0.0 \| >=12.0.0`<br>`phpunit <10.5.1 \| >=12.0.0` | `v8.5.0` (Installed, exact fit) |

### 3.2 Transitive Dependency Footprint
- `yajra/laravel-datatables: ^11.0` is a meta-package requiring:
  - `yajra/laravel-datatables-oracle: ^11` (Active engine used in controllers)
  - `yajra/laravel-datatables-buttons: ^11` (Unused)
  - `yajra/laravel-datatables-editor: ^11` (Unused)
  - `yajra/laravel-datatables-export: ^11` (Unused; requires `livewire/livewire: ^3.5.6`)
  - `yajra/laravel-datatables-fractal: ^11` (Unused)
  - `yajra/laravel-datatables-html: ^11` (Unused)
- **Finding:** Because `yajra/laravel-datatables` is installed instead of just `yajra/laravel-datatables-oracle`, `livewire/livewire: v3.8.9` is pulled in transitively. This has zero negative impact on tests or routes, but represents unused footprint.

---

## 4. Deep Package Audit

### 4.1 Laravel Sanctum v4 Audit

#### A. Model Usage (`app/Models/User.php`)
```php
use Laravel\Sanctum\HasApiTokens;
...
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, LogActivity, Notifiable;
```
- **Analysis:** `Laravel\Sanctum\HasApiTokens` remains the canonical trait in Sanctum v4. Method signatures for `tokens()`, `tokenCan()`, `tokenCant()`, and `createToken()` are completely identical to v3.
- **Verdict:** No code modification required in `User.php`.

#### B. Route & Middleware Usage (`routes/api.php`)
```php
Route::middleware(['api', 'throttle:sync-api', 'auth:sanctum', 'role:Administrator|Pengurus'])->group(function () {
    Route::controller(synchronizationController::class)->group(function () {
        Route::get('/sync/kelas', 'get_kelas')->name('get.kelas');
        Route::post('/sync/kelas', 'store_kelas')->name('store.kelas');
        Route::get('/sync/santri', 'get_santri')->name('get.santri');
        Route::post('/sync/santri', 'store_santri')->name('store.santri');
    });
});
```
- **Analysis:** Route registration uses `auth:sanctum` which binds to the `sanctum` guard defined by `SanctumServiceProvider`.
- **Verdict:** Fully functional and verified in `SynchronizationSecurityTest`.

#### C. Configuration Audit (`config/sanctum.php`)
```php
// Existing config/sanctum.php (Lines 62-65):
'middleware' => [
    'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
],
```
- **Breaking Change in Sanctum 4.0:**
  1. Key `verify_csrf_token` was renamed to `validate_csrf_token`.
  2. Middleware classes should reference framework middleware rather than obsolete application middleware classes.
  3. Key `authenticate_session` must be added:
```php
// Required Sanctum v4 configuration:
'middleware' => [
    'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
    'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
    'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
],
```

#### D. Database Migrations Audit
- **Sanctum 4 Breaking Change:** Sanctum v4 no longer automatically loads migrations from its vendor directory. Applications must have migrations published in `database/migrations/`.
- **Current State:** `database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php` is already published in the repository.
- **Schema Diff vs Sanctum 4 Schema:**
```diff
 Schema::create('personal_access_tokens', function (Blueprint $table) {
     $table->id();
     $table->morphs('tokenable');
-    $table->string('name');
+    $table->text('name');
     $table->string('token', 64)->unique();
     $table->text('abilities')->nullable();
     $table->timestamp('last_used_at')->nullable();
-    $table->timestamp('expires_at')->nullable();
+    $table->timestamp('expires_at')->nullable()->index();
     $table->timestamps();
 });
```
- **Migration Risk:** Without the `expires_at` index, running token expiration pruning (`php artisan sanctum:prune-expired`) on large token tables causes sequential table scans. A dedicated migration to add this index aligns schema with Sanctum 4.

---

### 4.2 Yajra DataTables v11 Audit

#### A. Controller Usage Audit

| Controller | DataTables Invocation | Source Type | Status |
| :--- | :--- | :--- | :--- |
| `app/Http/Controllers/Users/UsersController.php` | `DataTables::of($users)` | Collection (`->get()`) | Compliant |
| `app/Http/Controllers/Kelas/KelasController.php` | `DataTables::of($kelas)` | Eloquent Collection (`all()`) | Compliant |
| `app/Http/Controllers/Santri/SantriController.php` | `DataTables::of($santri)` | Eloquent Builder | Compliant |
| `app/Http/Controllers/Kamar/KamarController.php` | `DataTables::of($kamar)` | Collection (`->get()`) | Compliant |
| `app/Http/Controllers/Riwayat/RiwayatController.php` | `DataTables::of($riwayat)` | Collection (`->get()`) | Compliant |
| `app/Http/Controllers/Tabungan/SaldoDebitController.php` | `DataTables::of($tabungan)` | Collection (`->get()`) | Compliant |
| `app/Http/Controllers/TransferController.php` | `datatables()->of($data)` | Collection (`->get()`) | **Inconsistent** |

- **Inconsistency in `TransferController.php` (Line 21):**
  Uses the global procedural helper `datatables()->of($data)` instead of `use Yajra\DataTables\Facades\DataTables;` and `DataTables::of($data)`.
  While the helper exists in `vendor/yajra/laravel-datatables-oracle/src/helper.php`, modern Laravel standards favor importing the facade for IDE autocompletion, static analysis, and type safety.

#### B. Breaking Changes in Yajra v11
1. `ApiResourceDataTable` support was dropped/deprecated in favor of `CollectionDataTable`. In `config/datatables.php` (Line 51):
   `'resource' => Yajra\DataTables\ApiResourceDataTable::class`
   Should be modernized or left as is (the class still aliases CollectionDataTable for backward compatibility).
2. `queryBuilder()` method was removed in favor of `query()`. None of our controllers use `queryBuilder()`.
3. All method signatures updated to PHP 8.2+ strict types.

#### C. Service Provider Audit (`config/app.php`)
- `config/app.php` contains:
```php
'providers' => ServiceProvider::defaultProviders()->merge([
    ...
    DataTablesServiceProvider::class,
    ...
])->toArray(),
```
- In Laravel 11, `Yajra\DataTables\DataTablesServiceProvider` is registered automatically via `extra.laravel.providers` package auto-discovery. Declaring it explicitly in `config/app.php` is legacy leftover from Laravel 10.

#### D. Frontend & Views Audit
7 Blade views initialize DataTables:
- `resources/views/pages/users/index.blade.php`
- `resources/views/pages/kelas/index.blade.php`
- `resources/views/pages/kamar/index.blade.php`
- `resources/views/pages/santri/index.blade.php`
- `resources/views/pages/transfer/index.blade.php`
- `resources/views/pages/saldo_debit/index.blade.php`
- `resources/views/pages/riwayat/index.blade.php`

All frontend AJAX calls expect standard DataTables keys: `data`, `DT_RowIndex`, `action`, plus columns. All controller methods terminate in `->toJson()`, ensuring full schema compatibility.

---

### 4.3 NunoMaduro Collision v8 Audit

#### A. Compatibility & Constraints
- `nunomaduro/collision: 8.5.0` requires:
  - `php: ^8.2.0` (System: `8.4.16`)
  - `symfony/console: ^7.1.5` (System: `7.4.19`)
  - `filp/whoops: ^2.16.0` (System: `2.18.4`)
  - `nunomaduro/termwind: ^2.1.0` (System: `2.4.0`)
- Explicit package conflicts declared by Collision:
  - `laravel/framework <11.0.0 || >=12.0.0` → System has `11.56.1` (**PASS**)
  - `phpunit/phpunit <10.5.1 || >=12.0.0` → System has `10.5.64` (**PASS**)

#### B. PHPUnit Integration
- In Laravel 11 / Collision v8, test reporting utilizes PHPUnit 10 event subscribers (`NunoMaduro\Collision\Adapters\Phpunit\Subscribers\...`).
- Verified: `php artisan test` runs in `25.69s` executing 92 tests without deprecation warnings or runner errors.

#### C. Artisan CLI Exception Handling
- Verified: executing invalid commands (e.g. `php artisan non:existent`) triggers Collision's styled error card:
  `ERROR  There are no commands defined in the "non" namespace.`
- Exception logging in `storage/logs/laravel.log` remains clean and structured.

---

## 5. Risk Classification

| Risk Level | Item | Impact | Mitigation Plan |
| :--- | :--- | :--- | :--- |
| **LOW** | `User` model Sanctum trait compatibility | Minimal | Trait methods are identical; verified via tests. |
| **LOW** | Collision v8 test runner & console formatting | None | Fully verified on PHP 8.4 and PHPUnit 10. |
| **LOW** | DataTables query logic in controllers | Minimal | 6/7 controllers already follow facade pattern. |
| **MEDIUM** | `config/sanctum.php` middleware references | Medium | Update keys from `verify_csrf_token` to `validate_csrf_token` and reference framework classes. |
| **MEDIUM** | `TransferController.php` helper usage | Low | Refactor `datatables()->of()` to `DataTables::of()` with facade import. |
| **MEDIUM** | `personal_access_tokens` schema parity | Low-Medium | Create migration to add `index` to `expires_at` and widen `name` to `text`. |
| **MEDIUM** | Redundant providers in `config/app.php` | Low | Clean up `DataTablesServiceProvider` and other auto-discovered providers from `config/app.php`. |
| **HIGH** | Architectural or blocking dependency conflicts | None | No high-risk items detected. |

---

## 6. Required Code Modifications

### 1. `config/sanctum.php`
- Replace legacy middleware mapping with Sanctum v4 standards:
```php
'middleware' => [
    'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
    'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
    'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
],
```

### 2. `app/Http/Controllers/TransferController.php`
- Add import:
```php
use Yajra\DataTables\Facades\DataTables;
```
- Replace line 21:
```php
- return datatables()->of($data)
+ return DataTables::of($data)
```

### 3. `config/app.php`
- Remove redundant package providers that are handled by Laravel 11 auto-discovery:
```php
- BarcodeServiceProvider::class,
- PermissionServiceProvider::class,
- DataTablesServiceProvider::class,
- Barryvdh\Debugbar\ServiceProvider::class,
```

### 4. Database Schema Migration (Sanctum v4 Parity)
- Create migration `database/migrations/2024_05_23_000000_update_personal_access_tokens_for_sanctum_v4.php`:
```php
Schema::table('personal_access_tokens', function (Blueprint $table) {
    $table->text('name')->change();
    $table->index('expires_at');
});
```

### 5. Automated DataTables Regression Testing
- Create dedicated feature test `tests/Feature/DataTables/DataTablesAjaxResponseTest.php` to explicitly verify JSON structures and HTTP 200 responses for:
  - `GET /users` (AJAX)
  - `GET /kelas` (AJAX)
  - `GET /santri` (AJAX)
  - `GET /kamar` (AJAX)
  - `GET /transfer` (AJAX)
  - `GET /riwayat` (AJAX)
  - `GET /saldo-debit` (AJAX)

---

## 7. Recommended PR Breakdown

To maintain disciplined, verifiable changes, we recommend implementing the finalization in a single, cohesive PR:

### **PR #37 — `refactor(deps): finalize Laravel 11 coupled package integration`**
**Branch:** `refactor/laravel11-dependency-finalization`  
**Base:** `bugfix/pre-laravel11-stabilization`  

**Scope of Changes:**
1. **Sanctum v4 Configuration & Migration:**
   - Update `config/sanctum.php` middleware array to v4 specification.
   - Add schema migration for `personal_access_tokens` (`expires_at` index, `name` text).
2. **Yajra DataTables Modernization:**
   - Align `TransferController` to use `DataTables` facade.
   - Clean up redundant package providers in `config/app.php`.
3. **Automated Verification:**
   - Add `DataTablesAjaxResponseTest` covering all 7 DataTables endpoints.
   - Verify `92+` tests pass green (all assertions verified).

---

## 8. Verification & Approval Gate

Execution of Phase 5.5.3B (Implementation) must remain paused until explicit user approval is granted.

- [x] Composer compatibility verified
- [x] Sanctum requirements, model, routes, middleware, and schema audited
- [x] Yajra DataTables controllers, responses, and views audited
- [x] Collision v8 CLI and PHPUnit integration verified
- [x] Report generated: `Laravel11_Dependency_Finalization_Assessment.md`
- [ ] **Awaiting user authorization to create branch `refactor/laravel11-dependency-finalization` and begin implementation.**
