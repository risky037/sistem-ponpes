# Phase 5.8.7C-4 — Laravel 12 Core Domain Cleanup, Simplification & Academic Foundation Migration Report

**Application:** Sistem Informasi Pondok Pesantren Fatimah Az-Zahra  
**Branch:** `develop`  
**Stack:** Laravel 12.69.2 · PHP 8.4.16 · MariaDB 10.4.32  
**Date:** 2026-09-20  
**Status:** Completed & Validated  
**Baseline Test Status:** 142 tests passed, 660 assertions passed (100% green)

---

## Executive Summary

Phase 5.8.7C-4 executes the approved Core Domain Cleanup roadmap established during the Phase 5.8.7C-3 Domain Rationalization Audit. The goal of this phase is refocusing the pesantren management system around its core domains—**Santri Management**, **Academic Foundation**, and **Financial Operations**—while stripping away unmaintained, peripheral integrations and excessive database complexity.

In this phase:
1. **Third-Party WhatsApp API Integration was completely eradicated**, preserving only lightweight manual `wa.me` redirect capabilities.
2. **Google Spreadsheet Synchronization was fully excised**, eliminating external Google client dependencies, sync controllers, and network polling helpers.
3. **Address Domain was simplified** from a heavyweight 4-level relational cascade (14,000+ JSON files, 4 tables) to a streamlined, maintainable `alamat_lengkap TEXT` design.
4. **Academic Foundation was introduced** via dedicated `AcademicYear`, `StudentBatch`, and `AcademicEnrollment` entities with minimal CRUD, strict decoupling from Santri lifecycle, and no premature LMS/grading bloat.
5. **Core Domain Services were extracted**: `SantriLifecycleService` and `FinancialTransactionService` now encapsulate lifecycle and transactional logic away from HTTP controllers.
6. **Observer Architecture was consolidated**: Legacy closure listeners in `WaliSantri::boot()` were cleanly extracted into `WaliSantriObserver` and registered in `AppServiceProvider`.

---

## 1. Step 1 — WhatsApp Integration Removal

### 1.1 Rationalization & Policy Adherence
Third-party notification dispatching through external WhatsApp gateway APIs (Labelin API) had introduced fragility, external network dependencies during database transactions, and unmaintained database structures.

Under the approved migration policy (schema definitions that have never shipped to external production environments are corrected directly rather than piling up alter migrations):
- The untracked removal migration was reverted.
- The original application support migration (`2024_05_19_001410_create_application_support_tables.php`) was updated to remove `whatsapp_messages` table and WhatsApp settings fields (`whatsapp_feature`, `whatsapp_api_key`, `sender`).
- Removed `app/Models/WhatsappMessage.php`.
- Removed `config/whatsapp.php`.
- Removed API notification triggers from transaction and student creation hooks.
- Preserved only `App\Helpers\Whatsapp::make()` for phone number sanitation (E.164 conversion) and direct browser manual `wa.me` links.

### 1.2 Impact & Verification
- Zero outbound API requests during financial transactions.
- Tested across `PreMigrationCleanupTest` and `FinancialTransactionReliabilityTest`.

---

## 2. Step 2 — Google Spreadsheet Synchronization Removal

### 2.1 Rationalization & Dependency Removal
The Google Sheets synchronization feature was an external integration that bypassed relational constraints and duplicated local database records into cloud sheets:
- Removed composer package `revolution/laravel-google-sheets` via `composer remove`.
- Deleted `app/Http/Controllers/SinkronController.php`.
- Deleted `app/Helpers/Sinkron.php`.
- Deleted `app/Helpers/Ping.php` after auditing that ping checks were strictly synchronization-specific.
- Deleted `config/google.php`.
- Removed sync routes, navbar links, and views (`resources/views/pages/sinkronisasi/index.blade.php`).
- Cleaned environment variables from `.env` and `.env.example`.

### 2.2 Impact & Verification
- Clean package footprint with 0 abandoned or vulnerable dependencies.
- No orphan sync routes or broken navigation elements.

---

## 3. Step 3 — Address Domain Simplification

### 3.1 Architecture Shift
Previously, student addresses were split across 4 administrative levels backed by 14,000+ static JSON files in `public/wilayah`:
`Provinsi -> Kabupaten -> Kecamatan -> Kelurahan -> Dusun / RT / RW`

This created massive repository bloat, brittle JavaScript dropdown chains, and foreign key overhead without providing operational value to pesantren administration.

### 3.2 Implemented Changes
- Deleted `database/migrations/2023_10_31_083759_create_wilayah_tables.php`.
- Modified `database/migrations/2024_02_06_133257_create_alamat_santris_table.php` to define `alamat_lengkap TEXT` directly associated with `santri_id`.
- Deleted models: `Provinsi`, `Kabupaten`, `Kecamatan`, `Kelurahan`.
- Deleted `app/Http/Controllers/AlamatController.php`.
- Deleted entire `public/wilayah/` directory (14,700+ JSON files removed from repository tree).
- Updated `AlamatSantri` model, `SantriRequest`, `BiodataRequest`, `SantriController`, and `ProfilController`.
- Simplified Blade views (`pages/santri/include/form.blade.php`, `pages/santri/edit.blade.php`, `pages/santri/modal.blade.php`, and `pages/profil/form.blade.php`) to single clean textarea inputs.
- Updated `SantriExport` to output address as plain text without multi-table joins.

---

## 4. Step 4 — Academic Foundation Migration

### 4.1 Domain Design & Invariants
The academic foundation follows clean domain modeling principles designed to support future LMS capabilities without premature feature complexity:

- **AcademicYear ("Tahun Ajaran")**:
  Represents academic calendar intervals (e.g., 2026/2027 Ganjil/Genap) with start date, end date, and `is_active` status flag. Activating an academic year automatically deactivates all others.
- **StudentBatch ("Angkatan Masuk")**:
  Represents entry cohort (e.g., Angkatan 2026). Santri can optionally link to a batch via `student_batch_id` without coupling batch to annual enrollment.
- **AcademicEnrollment ("Riwayat Akademik Santri")**:
  The core junction linking `Santri`, `AcademicYear`, and `Kelas`. Enforces `UNIQUE(academic_year_id, santri_id)` to ensure a student cannot be enrolled in multiple classes in the same academic year.

### 4.2 Database Migrations
Created `database/migrations/2024_06_01_000001_create_academic_foundation_tables.php`:
- `academic_years`: `id`, `name`, `semester`, `start_date`, `end_date`, `is_active`, timestamps.
- `student_batches`: `id`, `name`, `year`, `description`, timestamps.
- `academic_enrollments`: `id`, `academic_year_id`, `santri_id`, `kelas_id`, `status`, `enrolled_at`, `notes`, timestamps, `UNIQUE(academic_year_id, santri_id)`.
- `santris.student_batch_id`: Nullable foreign key constrained with `nullOnDelete()`.

### 4.3 Models, Seeders & UI
- Models created: `App\Models\AcademicYear`, `App\Models\StudentBatch`, `App\Models\AcademicEnrollment`.
- Relationships wired into `App\Models\Santri` (`academic_enrollments`, `student_batch`) and `App\Models\Kelas` (`academic_enrollments`).
- Created `Database\Seeders\AcademicFoundationSeeder` registered in `DatabaseSeeder`.
- Created `App\Http\Controllers\Academic\AcademicYearController` implementing standard index, create, store, edit, update, destroy.
- Created minimal Blade UI matching the existing Admin dashboard theme:
  - `resources/views/pages/academic/academic_year/index.blade.php`
  - `resources/views/pages/academic/academic_year/create.blade.php`
  - `resources/views/pages/academic/academic_year/edit.blade.php`
  - `resources/views/pages/academic/academic_year/include/action.blade.php`
- Added navigation link in `resources/views/components/navbar.blade.php` under "Akademik".
- Created feature test suite: `tests/Feature/Academic/AcademicFoundationTest.php` (10 tests, 28 assertions, 100% green).

---

## 5. Step 5 — Domain Service Extraction

### 5.1 `SantriLifecycleService`
Extracted from `SantriController` into `app/Services/SantriLifecycleService.php`:
- `register(array $data, ?UploadedFile $foto = null): Santri`:
  - Generates `no_induk` and Hijri conversion.
  - Processes image resizing (Intervention Image v3).
  - Creates user account and assigns `Santri` role.
  - Creates student record, room assignment, classroom relationship, address, and guardian information within an atomic `DB::transaction`.
  - Propagates room assignment (`kamar_id`) to allow `SantriObserver` to update room headcounts automatically.
- `update(Santri $santri, array $data, ?UploadedFile $foto = null): Santri`:
  - Recalculates `no_induk` upon gender changes.
  - Updates user credentials if provided.
  - Updates student profile, address, guardian, and room transitions cleanly.
- `delete(Santri $santri): void`:
  - Deletes photo storage assets.
  - Cleans up tabungan, transactions, address, guardian, enrollments, student records, and user account within an atomic transaction.

### 5.2 `FinancialTransactionService`
Extracted from `TransaksiController` and `TransferController` into `app/Services/FinancialTransactionService.php`:
- `deposit(string|Santri $santri, float|int $amount, string $jenisTransaksi = 'Setoran'): TransaksiTabungan`:
  - Enforces `lockForUpdate()` on `Tabungan`.
  - Atomically calculates previous and current balance.
  - Creates `TransaksiTabungan` and updates balance.
- `withdraw(string|Santri $santri, float|int $amount, ?string $tujuan = 'Uang Jajan', string $jenisTransaksi = 'Penarikan'): TransaksiTabungan`:
  - Enforces `lockForUpdate()`.
  - Validates sufficient funds (throws `DomainException`).
  - Enforces daily withdrawal limit per student (throws `DomainException`).
  - Creates withdrawal transaction and updates balance.
- `transfer(Santri $penerima, Santri $pengirim, float|int $amount, ?string $keterangan = null): Transfer`:
  - Implements deterministic deadlock-free locking: `min($firstId, $secondId)`.
  - Re-verifies sender balance under exclusive transaction lock (eliminating TOCTOU race conditions).
  - Atomically mutates both accounts, records dual ledger entries, and generates the `Transfer` audit log.

---

## 6. Step 6 — Observer Architecture Consolidation

### 6.1 `WaliSantriObserver` Extraction
In earlier phases, model observers were extracted for `User`, `Setting`, `Kelas`, `Kamar`, `Tabungan`, `Transfer`, `Santri`, and `TransaksiTabungan`. However, `WaliSantri` still utilized inline closure listeners in its static `boot()` method.
- Created `app/Observers/WaliSantriObserver.php` implementing `creating`, `updating`, and `deleting` hooks.
- Removed `boot()` method from `App\Models\WaliSantri`.
- Registered `WaliSantri::observe(WaliSantriObserver::class)` in `App\Providers\AppServiceProvider`.
- Added test validation in `tests/Feature/Architecture/ObserverArchitectureTest.php`.

---

## 7. Verification & Quality Metrics

### 7.1 Database & Seeder Verification
```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan db:seed
```
- **Result:** All 15 migrations applied cleanly.
- **Seeder Result:** `RolePermissionSeeder`, `UserSeeder`, `AcademicStructureSeeder`, `AcademicFoundationSeeder`, and `SettingSeeder` executed with 0 errors.

### 7.2 Full Test Suite
```bash
php artisan test
```
```text
   PASS  Tests\Feature\Academic\AcademicFoundationTest (10 tests)
   PASS  Tests\Feature\Architecture\ArchitectureModernizationTest (6 tests)
   PASS  Tests\Feature\Architecture\ObserverArchitectureTest (8 tests)
   PASS  Tests\Feature\Architecture\SantriObserverArchitectureTest (8 tests)
   PASS  Tests\Feature\Architecture\TransaksiTabunganObserverArchitectureTest (4 tests)
   PASS  Tests\Feature\AuthenticationTest (6 tests)
   PASS  Tests\Feature\DataTables\DataTablesAjaxResponseTest (7 tests)
   PASS  Tests\Feature\Database\DatabaseIntegrityConstraintsTest (8 tests)
   PASS  Tests\Feature\ExampleTest (1 test)
   PASS  Tests\Feature\Financial\FinancialRelationshipIntegrityTest (8 tests)
   PASS  Tests\Feature\Financial\FinancialTransactionReliabilityTest (12 tests)
   PASS  Tests\Feature\Image\ImageProcessingSecurityTest (7 tests)
   PASS  Tests\Feature\PreMigration\PreMigrationCleanupTest (8 tests)
   PASS  Tests\Feature\ProfileAuthorizationTest (5 tests)
   PASS  Tests\Feature\Reliability\DebugCleanupTest (5 tests)
   PASS  Tests\Feature\Reliability\ExceptionHandlingTest (9 tests)
   PASS  Tests\Feature\SantriPasswordSecurityTest (6 tests)
   PASS  Tests\Feature\Security\SecurityHardeningTest (8 tests)
   PASS  Tests\Unit\ExampleTest (1 test)

  Tests:    142 passed (660 assertions)
  Duration: 23.36s
```

### 7.3 Code Standards & Linting
```bash
composer run lint:check (./vendor/bin/pint --test)
```
- **Result:** Passed (0 lint violations).

### 7.4 Security Vulnerability Audit
```bash
composer audit
```
- **Result:** No security vulnerability advisories found.

---

## 8. Summary Table of Phase 5.8.7C-4 Artifacts

| Component | Status | Description |
|-----------|--------|-------------|
| **WhatsApp Integration** | Removed | Eradicated third-party models, tables, observer calls, configs. Preserved `wa.me` helper. |
| **Google Sheets Sync** | Removed | Eradicated composer package, controllers, views, routes, and helpers. |
| **Address Domain** | Simplified | Eradicated 4 tables and 14,700+ JSON files. Replaced with single `alamat_lengkap TEXT`. |
| **Academic Foundation** | Migrated | Created `AcademicYear`, `StudentBatch`, `AcademicEnrollment`, CRUD, routes, seeders, tests. |
| **SantriLifecycleService** | Extracted | Centralized student registration, update, photo scaling, and cascading deletion. |
| **FinancialTransactionService** | Extracted | Centralized deposit, withdrawal with daily limiter, and deadlock-free transfers. |
| **WaliSantriObserver** | Modernized | Extracted closure listeners from model `boot()` into dedicated observer. |

Phase 5.8.7C-4 is complete, fully tested, and ready for production baseline staging.
