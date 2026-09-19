# Intervention Image Migration Assessment & Implementation Plan

**Application:** Sistem Informasi Pondok Pesantren Fatimah Az-Zahra  
**Document Type:** Technical Migration Assessment & Architectural Plan  
**Target Package:** `intervention/image` v2 $\rightarrow$ `intervention/image-laravel` (v3)  
**Baseline Framework:** Laravel `10.50.3` / PHP `8.4.16`  
**Current Test Suite Baseline:** 85 passed (362 assertions) — 100% green  
**Date:** September 19, 2026  

---

## 1. Executive Summary & Current State

The application currently relies on `intervention/image: 2.7.2` (v2 architecture, released May 2022) for image resizing, aspect-ratio scaling, and local storage operations. 

Intervention Image v2 is legacy software that is incompatible with Laravel 11 and Symfony 7 components. Upgrading to Laravel 11 requires migrating to **Intervention Image v3**, which is distributed for Laravel via the official bridge package `intervention/image-laravel`.

Intervention Image v3 is a ground-up rewrite featuring a redesigned API:
- `Image::make()` has been completely removed.
- Closure-based `$constraint->aspectRatio()` and `$constraint->upsize()` modifiers have been replaced by native, expressive methods (`scaleDown()`, `scale()`, `cover()`).
- Driver configuration now requires driver class references (`Intervention\Image\Drivers\Gd\Driver::class`) instead of strings (`'gd'`).

### Automated Test Coverage Gap
A critical audit finding is that the existing 85 passing tests currently contain **zero test coverage** for image upload and processing workflows (`UploadedFile::fake()` is not utilized in any test). Migrating this package without automated test verification introduces a severe risk of runtime fatal errors (`Call to undefined method Intervention\Image\Facades\Image::make()`) escaping into production.

---

## 2. Dependency Audit & Package Compatibility

### 2.1. Current Package State
- **Package:** `intervention/image`
- **Installed Version:** `2.7.2`
- **Release Date:** May 21, 2022
- **PHP Requirements:** `>=5.4.0`
- **Dependencies:** `ext-fileinfo`, `guzzlehttp/psr7: ~1.1 || ^2.0`
- **Status:** Incompatible with Laravel 11 / Symfony 7 components; abandoned upstream in favor of v3.

### 2.2. Target Package Ecosystem
For Laravel applications, Intervention Image v3 is split into:
1. **`intervention/image` (v3.x)**: Framework-agnostic core image processing library.
2. **`intervention/image-laravel` (v1.x)**: Official Laravel integration layer providing service provider auto-discovery, facade bindings, and configuration integration.

| Package | Target Constraint | Installed Version | Laravel Compatibility |
| :--- | :--- | :--- | :--- |
| `intervention/image-laravel` | `^1.5` | `1.5.9` | Laravel `^8.0 \| ^9.0 \| ^10.0 \| ^11.0 \| ^12.0 \| ^13.0` |
| `intervention/image` (transitive) | `^3.11` | `3.11.x` | Framework-agnostic, PHP `^8.1 \| ^8.2 \| ^8.3 \| ^8.4` |

### 2.3. System Runtime & Drivers
- **Active PHP Runtime:** PHP `8.4.16 (cli)`
- **Available Extensions:**
  - `ext-gd`: **Installed and Active**
  - `ext-imagick`: **Not Installed**
- **Selected Driver:** `Intervention\Image\Drivers\Gd\Driver::class`

---

## 3. Source Code Usage Inventory

A comprehensive repository audit identified **10 image manipulation calls across 4 controllers**, plus 2 configuration files. No services, jobs, or helpers perform image processing directly.

```
┌────────────────────────────────────────────────────────────────────────┐
│                        IMAGE USAGE INVENTORY                           │
├────────────────────────────────────────────────────────────────────────┤
│ 1. SantriController.php                                                │
│    ├── Line 22:  use Intervention\Image\Facades\Image;                 │
│    ├── Line 107: store()  -> resize(240, 295, aspectRatio + upsize)    │
│    └── Line 203: update() -> resize(240, 295, aspectRatio + upsize)    │
│                                                                        │
│ 2. ProfilController.php                                                │
│    ├── Line 11:  use Intervention\Image\Facades\Image;                 │
│    └── Line 60:  biodata()-> resize(240, 295, aspectRatio + upsize)    │
│                                                                        │
│ 3. SettingController.php                                               │
│    ├── Line 11:  use Intervention\Image\Facades\Image;                 │
│    ├── Line 36:  store()  -> Logo upload       (240x295)               │
│    ├── Line 51:  store()  -> Favicon upload    (240x295)               │
│    ├── Line 66:  store()  -> KTS Master upload (240x295)               │
│    ├── Line 103: update() -> Logo upload       (240x295)               │
│    ├── Line 120: update() -> Favicon upload    (240x295)               │
│    └── Line 137: update() -> KTS Master upload (240x295)               │
│                                                                        │
│ 4. SynchronizationController.php (Api)                                 │
│    ├── Line 18:  use Intervention\Image\Facades\Image;                 │
│    └── Line 156: syncSantriStore() -> resize(400, 400, aspect+upsize)  │
│                                                                        │
│ 5. Configuration Files                                                 │
│    ├── config/app.php:   Provider import, alias registration           │
│    └── config/image.php: Driver configuration array                    │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 4. API Compatibility & Code Transformation Mapping

### 4.1. Namespace & Imports
| Component | Laravel 10 / Intervention v2 | Laravel 11 / Intervention v3 |
| :--- | :--- | :--- |
| **Facade Namespace** | `Intervention\Image\Facades\Image` | `Intervention\Image\Laravel\Facades\Image` |
| **Provider Class** | `Intervention\Image\ImageServiceProvider` | `Intervention\Image\Laravel\ServiceProvider` *(or removed via auto-discovery)* |

### 4.2. Configuration Mapping (`config/image.php`)

**Current (v2):**
```php
return [
    'driver' => 'gd',
];
```

**Target (v3):**
```php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Image Driver
    |--------------------------------------------------------------------------
    | Supported:
    | - "Intervention\Image\Drivers\Gd\Driver::class"
    | - "Intervention\Image\Drivers\Imagick\Driver::class"
    */
    'driver' => Intervention\Image\Drivers\Gd\Driver::class,
    'options' => [],
];
```

### 4.3. Registration Mapping (`config/app.php`)
- **Remove line 10:** `use Intervention\Image\Facades\Image;`
- **Remove line 11:** `use Intervention\Image\ImageServiceProvider;`
- **Remove line 186:** `ImageServiceProvider::class,` (handled by package discovery)
- **Update line 205:** `'Image' => Intervention\Image\Laravel\Facades\Image::class,`

### 4.4. Image Manipulation API Mapping

#### Santri Photo & Profile Biodata (240x295, Proportional, No Upscale)
**Legacy (v2):**
```php
Image::make($foto->getRealPath())->resize(240, 295, function ($constraint) {
    $constraint->upsize();
    $constraint->aspectRatio();
})->save($path.$filename);
```

**Modern (v3):**
```php
Image::read($foto->getRealPath())
    ->scaleDown(width: 240, height: 295)
    ->save($path.$filename);
```

#### Settings Uploads: Logo, Favicon, KTS Master (240x295)
**Legacy (v2):**
```php
Image::make($file->getRealPath())->resize(240, 295, function ($constraint) {
    $constraint->upsize();
    $constraint->aspectRatio();
})->save($path.$filename);
```

**Modern (v3):**
```php
Image::read($file->getRealPath())
    ->scaleDown(width: 240, height: 295)
    ->save($path.$filename);
```

#### Synchronization API: Santri Photo Sync (400x400)
**Legacy (v2):**
```php
Image::make($foto->getRealPath())->resize(400, 400, function ($constraint) {
    $constraint->upsize();
    $constraint->aspectRatio();
})->save($path.$filename);
```

**Modern (v3):**
```php
Image::read($foto->getRealPath())
    ->scaleDown(width: 400, height: 400)
    ->save($path.$filename);
```

> [!NOTE]
> **Why `scaleDown()` instead of `resize()` or `cover()`?**  
> In Intervention v2, combining `aspectRatio()` and `upsize()` ensured that images were scaled proportionally without exceeding bounding dimensions and without enlarging images smaller than the bounding box.  
> In Intervention v3, `scaleDown(width, height)` executes this exact mathematical operation natively in one method call.

---

## 5. Risk Assessment

| Functional Area | Impacted Route / Feature | Risk Level | Rationale & Mitigation |
| :--- | :--- | :---: | :--- |
| **Santri Management** | `POST /santri`<br>`PUT /santri/{id}` | **HIGH** | Core business domain. Failure breaks student registration and editing. Mitigate with dedicated feature tests simulating photo uploads. |
| **Profile & Biodata** | `PUT /profil/biodata/{id}` | **HIGH** | Self-service student/guardian biodata update. Failure produces HTTP 500 error. Mitigate with authorization + upload feature tests. |
| **Synchronization API** | `POST /api/sync/santri` | **HIGH** | External sync integration. Failure breaks bulk student import with images. Mitigate with API multipart upload tests. |
| **System Settings** | `POST /setting`<br>`PUT /setting/{id}` | **MEDIUM** | Admin branding (logo, favicon, KTS master card). Mitigate with setting upload test suite. |
| **Barcode Generation** | `GET /santri/print/{id}` | **NONE** | Uses `milon/barcode` (DNS1D) which outputs independent inline PNG/SVG data. Zero dependency on Intervention Image. |
| **Storage & File Paths** | `storage/app/public/uploads/` | **LOW** | Storage directory paths, symlinks, and file naming conventions (`$file->hashName()`) remain 100% identical. |

---

## 6. Migration Strategy

### Option 1: Pre-Upgrade Atomic Migration on Laravel 10 (RECOMMENDED)
Because `intervention/image-laravel: ^1.5` officially supports `illuminate/support: ^10.0` as well as `^11.0`, this migration can be executed **now on Laravel 10** before bumping the core framework.

**Advantages:**
- Completely isolates image manipulation refactoring into its own tested pull request.
- Prevents coupling image processing errors with Laravel 11 bootstrap, exception handler, or middleware rewrites.
- Ensures all photo upload features are verified green against the baseline before touching `laravel/framework`.

### Option 2: Coupled Framework Upgrade
Execute `intervention/image-laravel` replacement simultaneously with `laravel/framework: ^11.0`.

**Disadvantages:**
- Increases pull request surface area and failure modes during Laravel 11 rollout.
- Harder to bisect regressions.

---

## 7. Comprehensive Testing Strategy

To eliminate the existing test coverage gap, an automated test suite must be built and executed prior to merging the migration:

### Test Suite: `Tests\Feature\Image\ImageProcessingSecurityTest`

1. **`test_santri_store_processes_and_resizes_uploaded_photo`**:
   - Upload fake image `UploadedFile::fake()->image('photo.jpg', 600, 800)`
   - Assert response redirected with success toastr.
   - Assert file exists in `storage/app/public/uploads/santri/`
   - Inspect stored image with `getimagesize()` to assert width $\le 240$ and height $\le 295$.

2. **`test_santri_store_preserves_smaller_photo_without_upscaling`**:
   - Upload fake image `UploadedFile::fake()->image('small.jpg', 150, 150)`
   - Assert stored dimensions remain $150 \times 150$ (verifying `scaleDown` behavior).

3. **`test_santri_update_replaces_photo_and_cleans_storage`**:
   - Update santri with a new fake photo.
   - Assert new photo is processed and stored.

4. **`test_profil_biodata_processes_photo_upload`**:
   - Authenticate as student user.
   - Submit `PUT /profil/biodata/{id}` with fake photo.
   - Assert database record updated and photo file persisted.

5. **`test_setting_processes_logo_favicon_and_kts_master`**:
   - Authenticate as administrator.
   - Submit setting store/update with fake logo, favicon, and KTS master.
   - Assert all 3 files are stored under `storage/app/public/uploads/setting/`.

6. **`test_sync_api_santri_processes_photo`**:
   - Post to `/api/sync/santri` with bearer token and fake image.
   - Assert HTTP 200/201 and file saved in uploads directory.

7. **`test_invalid_file_type_rejected_by_validation`**:
   - Submit fake PDF/text file disguised as photo.
   - Assert HTTP 422 Unprocessable Entity and no image processing occurs.

---

## 8. Recommended PR Breakdown

| Step | Branch | Scope | Description |
| :---: | :--- | :--- | :--- |
| **PR A** | `refactor/intervention-image-v3` | Package & Config | Replace `intervention/image` with `intervention/image-laravel: ^1.5`, update `config/image.php`, adjust `config/app.php`. |
| **PR B** | *(same PR A)* | Code Refactoring | Refactor 10 `Image::make()` calls to `Image::read()->scaleDown()` across 4 controllers. |
| **PR C** | *(same PR A)* | Test Coverage | Add `ImageProcessingSecurityTest` covering all 7 test scenarios (bringing total tests to 92+). |

*Note: Steps A, B, and C MUST be committed in a single atomic PR to prevent breaking the application between dependency change and code update.*

---

## 9. Conclusion & Readiness

The Intervention Image migration is fully mapped, with clear 1:1 API equivalents for all 10 usage sites. Executing this migration in an atomic PR on `bugfix/pre-laravel11-stabilization` will remove the final major architectural blocker for upgrading `laravel/framework` to Laravel 11.

**Implementation Status:** Assessment completed. Standing by for user review and explicit approval.
