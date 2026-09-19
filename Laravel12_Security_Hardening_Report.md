# Phase 5.7 — Laravel 12 Security Hardening & Application Protection Report

## Executive Summary

**Phase 5.7** has completed comprehensive application security hardening for **Sistem Informasi Pondok Pesantren Fatimah Az-Zahra** following the stabilization of the Laravel 12 core upgrade.

- **Framework Version:** Laravel 12.69.2
- **PHP Version:** 8.4.16
- **Test Suite Result:** **107 passed (531 assertions)** — (baseline: 99 passed, +8 security tests added)
- **Code Style (Pint):** Passed (0 style violations on modified files)
- **Branch:** `feature/laravel12-core-upgrade`

---

## 1. Authentication Security Audit

### 1.1 Login Throttling & Brute Force Protection
- **Vulnerability Identified:** `AuthController@auth` previously accepted unlimited failed login attempts without any rate limiting, exposing the login endpoint to credential brute force attacks.
- **Remediation Implemented:**
  - Integrated Laravel's `Illuminate\Support\Facades\RateLimiter` into `AuthController@auth`.
  - Throttle key scoped to composite identifier: `Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip())`.
  - Configured threshold: maximum **5 attempts per minute**.
  - On lockout: dispatches `Illuminate\Auth\Events\Lockout` event and returns user to login with remaining delay (`auth.throttle`).
  - On successful authentication: immediately calls `RateLimiter::clear($throttleKey)`.
  - On failed credentials: calls `RateLimiter::hit($throttleKey, 60)`.
- **Regression Testing:** Automated in `SecurityHardeningTest`:
  - `test_login_rate_limiting_locks_out_after_five_failed_attempts`
  - `test_successful_login_clears_rate_limiter`

### 1.2 Session Management
- **Audit Findings:**
  - `AuthController@auth` securely regenerates session on successful login (`$request->session()->regenerate()`) to protect against Session Fixation.
  - `AuthController@logout` securely invalidates the session (`$request->session()->invalidate()`) and regenerates the CSRF token (`$request->session()->regenerateToken()`).
  - `config/session.php` maintains `http_only => true` and `same_site => 'lax'` defaults, preventing JavaScript access to session cookies.
  - HTTPS cookies are enabled via `SESSION_SECURE_COOKIE` in production.

### 1.3 Password Policy
- Standardized application-wide password defaults in `AppServiceProvider::boot()` via `Password::defaults(function () { return Password::min(8); });`.
- All user creation and update requests require minimum 8 characters, confirmation, and number requirements.

---

## 2. Authorization Audit

### 2.1 Role Escalation & Self-Tampering Prevention
- **Vulnerability 1 — Self User Deletion:** An authenticated administrator could previously delete their own account in `UsersController@destroy`, causing immediate session loss and potential system lockout.
  - **Fix:** In `UsersController@destroy`, added guard: `if (auth()->id() === $user->id) { ... }` which halts deletion and alerts the administrator.
- **Vulnerability 2 — Last Administrator Deletion:** If multiple administrators existed and were deleted down to the last account, or an admin deleted the sole administrator, the system would lose all admin access.
  - **Fix:** Added check ensuring `User::role('Administrator')->count() > 1` before allowing an administrator deletion.
- **Vulnerability 3 — Self-Demotion:** An administrator updating their own profile via `UsersController@update` could accidentally change their role away from Administrator, losing administrative privileges.
  - **Fix:** Added guard in `UsersController@update` preventing role modification on the currently authenticated user if they are an Administrator.
- **Vulnerability 4 — System Role Deletion:** In `RoleController@destroy`, default system roles (`Administrator`, `Keuangan`, `Santri`) could be deleted, which would break core middleware checks.
  - **Fix:** Configured `'protected_roles'` in `config/permission.php` and enforced protection in `RoleController@destroy` without hardcoding strings in the controller.

### 2.2 Gate & Policy Hardening
- Added `delete(User $currentUser, User $targetUser): bool` method to `app/Policies/UserPolicy.php` enforcing:
  - Rejection if `$currentUser->id === $targetUser->id`.
  - Rejection if `$targetUser->hasRole('Administrator') && User::role('Administrator')->count() <= 1`.
  - Acceptance if `$currentUser->hasRole('Administrator')`.

---

## 3. Data Protection & Model Security

### 3.1 Sensitive Data Exposure
- **Vulnerability Identified:** `app/Models/User.php` only listed `'password'` under `protected $hidden`. The `remember_token` attribute was not hidden, exposing tokens during JSON/array model serialization.
- **Fix:** Added `'remember_token'` to `protected $hidden` in `app/Models/User.php`.
- **Database Schema Audit:** Verified that the legacy `users` table schema does not store `remember_token` in the database, preventing query errors while safeguarding in-memory model serialization.
- **Regression Testing:** Added `test_user_serialization_hides_password_and_remember_token`.

### 3.2 Mass Assignment Audit
- Audited all 19 Eloquent models in `app/Models/`:
  - `ActivityLog`, `AlamatSantri`, `Kabupaten`, `Kamar`, `Kecamatan`, `Kelas`, `KelasSantri`, `Kelurahan`, `Provinsi`, `Santri`, `Setting`, `Tabungan`, `TransaksiTabungan`, `Transfer`, `User`, `WaliKelas`, `WaliSantri`, `WhatsappMessage`: strictly guarded with `protected $guarded = ['id']`.
  - `KamarSantri`: changed from insecure `$guarded = []` to `protected $guarded = ['id']`.

### 3.3 Raw Query & SQL Injection Audit
- Full codebase search for `DB::raw`, `whereRaw`, `selectRaw`, `havingRaw`, `orderByRaw`, and `unprepared`: **Zero instances found**.
- All database queries strictly utilize Eloquent ORM parameter bindings or Query Builder methods.

---

## 4. File Security & Upload Hardening

### 4.1 Directory Permissions
- **Vulnerability Identified:** Insecure `mkdir($path, 0777, true)` permissions were used across file upload handling. World-writable directory permissions (`0777`) pose a privilege escalation risk on shared hosting/servers.
- **Remediation Implemented:** Replaced all 9 instances of `0777` with secure `0755` permissions:
  - `SettingController.php`: lines 34, 47, 61, 98, 114, 130 (6 instances)
  - `SantriController.php`: lines 104, 200 (2 instances)
  - `synchronizationController.php`: line 150 (1 instance)

### 4.2 Upload Validation & MIME Verification
- **Vulnerabilities Identified:**
  1. `SettingRequest.php` completely omitted validation for `kts_master`, allowing arbitrary file uploads via `request()->file('kts_master')`.
  2. `SettingRequest.php`, `SantriRequest.php`, and `BiodataRequest.php` relied only on file extension checking (`mimes:...`) without verifying true image content.
- **Remediations Implemented:**
  - `SettingRequest.php`: Added validation rule for `kts_master` (`['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:5020']`).
  - Added strict `image` rule to `logo`, `favicon`, and `foto` across all requests to inspect magic bytes and prevent executable files disguised with image extensions.
  - Made `logo` and `favicon` nullable on update (PATCH/PUT) while retaining required status on store (POST).

---

## 5. API Security & Production Configuration

### 5.1 Sanctum Configuration & Expiration
- `config/sanctum.php`: Added environment support for token expiration:
  `'expiration' => env('SANCTUM_EXPIRATION', null),`
- Documented `SANCTUM_EXPIRATION` in `.env.example`.

### 5.2 CORS Configuration
- **Vulnerability Identified:** `config/cors.php` previously defaulted `'allowed_origins' => ['*']` (wildcard).
- **Remediation Implemented:**
  - Removed default wildcard origin.
  - Configured `allowed_origins` to fallback to `env('APP_URL', 'http://localhost')` and allow explicit configuration via `env('CORS_ALLOWED_ORIGINS')`.
  - Added `CORS_ALLOWED_ORIGINS` to `.env.example`.

### 5.3 Production Environment Configuration
- `config/app.php`: Default `env('APP_DEBUG', false)` and `env('APP_ENV', 'production')`.
- `config/logging.php`: Default channel is `'stack'` with structured loggers.
- Standardized test runner config cleaning in `tests/CreatesApplication.php` to prevent stale `config:cache` interference with PHPUnit testing environments.

---

## 6. External Laravel 13 Reference Analysis

We inspected the sibling reference projects `../sistem-pesantren` and `../gakutsu.net` (Laravel 13.x) to align with forward-looking architecture and testing conventions:
- **Adopted Patterns:**
  - Implemented the standard Laravel Breeze rate limiting pattern (`Str::transliterate(Str::lower($email).'|'.$ip)` and `event(new Lockout($request))`) from `../sistem-pesantren/app/Http/Requests/Auth/LoginRequest.php`.
  - Leveraged test assertions against `RateLimiter::attempts()` and `Event::fake([Lockout::class])`.
- **Integrity Compliance:**
  - Zero business logic, models, migrations, routes, or domain logic was copied from reference projects.

---

## 7. Dependency Review: `spatie/laravel-ignition`

- **Package:** `spatie/laravel-ignition` (`^2.0`)
- **Current Placement:** `require-dev` in `composer.json`.
- **Evaluation:**
  - In development mode (`APP_ENV=local`, `APP_DEBUG=true`), Ignition provides a rich stack trace and interactive debugging GUI.
  - In Laravel 13 reference projects, `spatie/laravel-ignition` is no longer required because Laravel 11/12/13 provides a modern default exception renderer, and Collision 8 handles CLI error output.
  - In production, when deployed using `composer install --no-dev --optimize-autoloader`, `spatie/laravel-ignition` is never installed or executed.
- **Recommendation:**
  - **Retain for Phase 5.7:** Do not remove now to avoid disrupting the local developer debugging workflow in Laravel 12.
  - **Production Guideline:** Ensure `APP_DEBUG=false` and always run `composer install --no-dev` in production CI/CD pipelines to ensure Ignition is completely excluded from production environments.
  - **Future Upgrade:** Candidate for deprecation and removal during future Laravel 13 migration.

---

## 8. Verification Results

### 8.1 Automated Test Suite
```bash
php artisan test
```
```
Tests:    107 passed (531 assertions)
Duration: 29.50s
```
All 99 baseline regression tests and all 8 new security regression tests passed cleanly.

### 8.2 Code Formatting & Style
```bash
./vendor/bin/pint --test <modified files>
```
```json
{"tool":"pint","result":"passed"}
```
All modified codebase files adhere to PSR-12 and Laravel Pint standards.

---

## 9. Modified Files Summary

| Component | File | Changes |
| :--- | :--- | :--- |
| **Authentication** | `app/Http/Controllers/Auth/AuthController.php` | Added `RateLimiter` login throttling, `Lockout` event, and rate clearing on success. |
| **Authorization** | `app/Policies/UserPolicy.php` | Added `delete` policy checking self-deletion and last admin protection. |
| **Authorization** | `app/Http/Controllers/Users/UsersController.php` | Prevented self user deletion, last admin deletion, and self role downgrade. |
| **Authorization** | `app/Http/Controllers/Role/RoleController.php` | Protected system roles (`Administrator`, `Keuangan`, `Santri`) from deletion. |
| **Authorization** | `config/permission.php` | Defined configurable `protected_roles` list. |
| **Data Protection** | `app/Models/User.php` | Added `remember_token` to `$hidden`. |
| **Data Protection** | `app/Models/KamarSantri.php` | Set `$guarded = ['id']` for mass assignment safety. |
| **File Security** | `app/Http/Controllers/SettingController.php` | Replaced 6 instances of `0777` with `0755`. |
| **File Security** | `app/Http/Controllers/Santri/SantriController.php` | Replaced 2 instances of `0777` with `0755`. |
| **File Security** | `app/Http/Controllers/Api/synchronizationController.php` | Replaced 1 instance of `0777` with `0755`. |
| **Upload Validation** | `app/Http/Requests/SettingRequest.php` | Added validation for `kts_master`, `image` rule for logo/favicon. |
| **Upload Validation** | `app/Http/Requests/SantriRequest.php` | Added `image` rule to `foto`. |
| **Upload Validation** | `app/Http/Requests/Profil/BiodataRequest.php` | Added `image` rule to `foto`. |
| **API & Config** | `config/sanctum.php` | Added `SANCTUM_EXPIRATION` env support. |
| **API & Config** | `config/cors.php` | Replaced wildcard origin with configurable `CORS_ALLOWED_ORIGINS` / `APP_URL`. |
| **API & Config** | `app/Providers/AppServiceProvider.php` | Registered `Password::defaults()`. |
| **Environment** | `.env.example` | Documented `SANCTUM_EXPIRATION` and `CORS_ALLOWED_ORIGINS`. |
| **Testing** | `tests/CreatesApplication.php` | Cleared stale cached config during test boot to guarantee testing env. |
| **Testing** | `tests/Feature/Security/SecurityHardeningTest.php` | Created 8 automated security regression tests (53 assertions). |
