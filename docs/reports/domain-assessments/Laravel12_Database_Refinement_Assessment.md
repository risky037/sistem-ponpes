# Phase 5.8.4 — Laravel 12 Database Migration & Seeder Refinement Assessment

**Project:** Sistem Informasi Pondok Pesantren Fatimah Az-Zahra  
**Date:** September 19, 2026  
**Current Baseline:** Laravel 12.69.2 | PHP 8.4.16 | MySQL 8.0 / MariaDB 10.4 / SQLite in-memory  
**Active Branch:** `develop` (`41a5214a`, synchronized with `origin/develop`)  
**Baseline Release Tag:** `v12.0.0-security-baseline`  
**Automated Tests:** 107 passed (531 assertions)  
**Deliverable Status:** Assessment Only (Zero schema or seeder modifications executed)  

---

## 1. Executive Summary

Phase 5.8.4 audits the database schema lifecycle (`database/migrations/`) and initialization seeds (`database/seeders/`) for *Sistem Informasi Pondok Pesantren Fatimah Az-Zahra*.

The objectives are:
1. Identify migration bloat, duplicate schema alterations, and historical fixup migrations.
2. Evaluate seeder coupling, remove environment-dependent email generation (`config('app.domain')`), and define predictable development credentials.
3. Verify database schema reproducibility via `php artisan migrate:fresh --seed` and evaluate native `php artisan schema:dump`.
4. Formulate an architectural decision between retaining migration history vs. executing a pre-production consolidation.

---

## 2. Database Migration Audit

### 2.1. Migration Inventory & Chronology
The repository contains **27 migration files** spanning from October 2014 to September 2026:
- 4 Framework default migrations (`users`, `password_reset_tokens`, `failed_jobs`, `jobs`).
- 1 Sanctum access token migration (`personal_access_tokens`).
- 1 Spatie RBAC migration (`permission_tables` defining 5 tables).
- 15 Core institutional and regional domain migrations.
- **6 Post-facto alteration/fixup migrations** modifying previously declared tables.

A detailed line-by-line audit is documented in [docs/reports/domain-assessments/Laravel12_Migration_Cleanup_Assessment.md](Laravel12_Migration_Cleanup_Assessment.md).

### 2.2. Duplicate & Corrective Schema Alterations
The audit identified three major areas where schema definitions are fragmented across multiple migrations:

#### 1. Financial Domain Alteration Churn (`tabungans`, `transaksi_tabungans`, `transfers`)
- **`tabungans`:**
  - `2023_08_29`: Created table with `foreignId('santri_id')->cascadeOnDelete()` without unique constraints.
  - `2024_05_22`: Dropped foreign key, enforced `UNIQUE(santri_id)`, restored foreign key with `onDelete('restrict')`, and executed `ALTER TABLE tabungans ADD CONSTRAINT chk_tabungans_saldo CHECK (saldo >= 0)`.
- **`transaksi_tabungans`:**
  - `2023_08_29`: Created base transaction table.
  - `2024_05_20`: Added nullable `saldo_sebelumnya` and `keterangan` columns.
  - `2024_05_22`: Dropped foreign key on `santri_id`, re-created it with `restrict`, and added composite index `idx_transaksi_santri_tgl_jenis`.
- **`transfers`:**
  - `2024_05_20`: Created table with cascading deletes.
  - `2024_05_22`: Dropped foreign keys, re-created with `restrict`, added CHECK constraints (`jumlah_transfer > 0`, `pengirim_id != penerima_id`), and added composite indexes `idx_transfers_pengirim_created` and `idx_transfers_penerima_created`.

#### 2. Sanctum Personal Access Tokens
- `2019_12_14`: Created `personal_access_tokens` with standard `string('name')` and no expiration index.
- `2026_09_19`: Altered `name` to `text('name')` and added an index on `expires_at` for Sanctum v4 pruning compatibility.

#### 3. Fragmented Core Framework Tables
- In modern Laravel 11/12 (as demonstrated in `../gakutsu.net` and `../sistem-pesantren`), core tables are consolidated into standard timestamps:
  - `0001_01_01_000000_create_users_table.php` (`users`, `password_reset_tokens`, `sessions`)
  - `0001_01_01_000001_create_cache_table.php` (`cache`, `cache_locks`)
  - `0001_01_01_000002_create_jobs_table.php` (`jobs`, `job_batches`, `failed_jobs`)
- In this repository, `users`, `password_reset_tokens`, `failed_jobs`, and `jobs` remain scattered across 4 legacy migrations dating back to 2014.

---

## 3. Database Seeder Audit

### 3.1. Current Seeder Architecture
Only two files exist in `database/seeders/`:
1. **`DatabaseSeeder.php`** (90 lines)
2. **`RoleSeeder.php`** (98 lines)

### 3.2. Identified Architectural Issues
1. **Environment-Dependent Email Generation:**
   `DatabaseSeeder` currently builds user emails via concatenation:
   ```php
   'email' => 'admin'.config('app.domain')      // produces admin@digitren.com
   'email' => 'keuangan'.config('app.domain')   // produces keuangan@digitren.com
   'email' => 'pengurus'.config('app.domain')   // produces pengurus@digitren.com
   ```
   - If `APP_DOMAIN` in `.env` is omitted or malformed (e.g. without leading `@`), invalid email strings are generated.
   - Developer onboarding is hindered because login credentials vary depending on `.env` configuration.
2. **Tight Seeder Coupling & Fragile Execution Order:**
   - `RoleSeeder` directly assumes that `User` IDs `1`, `2`, and `3` already exist:
     ```php
     User::find(1)->assignRole('Administrator');
     User::find(2)->assignRole('Keuangan');
     User::find(3)->assignRole('Pengurus');
     ```
   - Running `php artisan db:seed --class=RoleSeeder` independently throws fatal null-pointer exceptions.
   - If user insertion order or IDs shift, roles are silently assigned to incorrect users.
3. **Dead / Bloated Code:**
   - `DatabaseSeeder.php` lines 58–87 contain 30 lines of commented-out JSON loops parsing ~83,000 wilayah records from `public/wilayah/*.json`.
4. **Lack of Idempotency:**
   - Using `User::create` and `Role::create` causes unique constraint crashes if seeders are run multiple times.

### 3.3. Standardized Seeder Blueprint
Following Laravel best practices (modeled after `../gakutsu.net`):

1. **Predictable Development Credentials:**
   Remove `config('app.domain')` entirely and standardize on canonical, deterministic development credentials:
   - **Administrator:** `admin@gmail.com` / `password`
   - **Keuangan (Finance):** `keuangan@gmail.com` / `password`
   - **Pengurus (Board):** `pengurus@gmail.com` / `password`
2. **Modular Seeder Class Structure:**
   - **`RolePermissionSeeder.php`:** Creates permissions from `config('permission.*')` and creates system roles (`Administrator`, `Keuangan`, `Pengurus`, `Santri`, `Alumni`). Assigns permissions to roles independently of any user records.
   - **`UserSeeder.php`:** Creates default system users using `User::firstOrCreate(...)` and assigns roles by name (`$admin->assignRole('Administrator')`).
   - **`AcademicStructureSeeder.php`:** Creates default initial `Kelas` and `Kamar` entities using `firstOrCreate`.
   - **`SettingSeeder.php`:** Seeds default application settings (`Setting::firstOrCreate(...)`).
   - **`WilayahImporterCommand.php`:** Move the 83k JSON loops to a dedicated Artisan command (`php artisan wilayah:import`) with chunking, progress bars, and transactions.
   - **`DatabaseSeeder.php`:** Orchestrates child seeders cleanly:
     ```php
     public function run(): void
     {
         $this->call([
             RolePermissionSeeder::class,
             UserSeeder::class,
             AcademicStructureSeeder::class,
             SettingSeeder::class,
         ]);
     }
     ```

---

## 4. Schema Verification & Export Audit

### 4.1. Verification of `php artisan migrate:fresh --seed`
Execution of `php artisan migrate:fresh --seed` drops and successfully recreates all 29 database tables in **4.26 seconds**:
- All 27 migrations execute without errors.
- Default roles and initial seed users are populated.
- Automated test suite verification: **107 passed (531 assertions)**.

### 4.2. Evaluation of `php artisan schema:dump`
Testing `php artisan schema:dump` revealed an environment incompatibility:
```text
mysqldump: Couldn't execute 'SHOW FUNCTION STATUS WHERE Db = 'db_sistempesantren_v2'': 
Column count of mysql.proc is wrong. Expected 21, found 20. Created with MariaDB 100108, now running 100432.
Please use mysql_upgrade to fix this error (1558)
```
- **Finding:** Native `schema:dump` relies on `mysqldump` system binaries. In environments running MariaDB or mismatched client/server versions, it throws fatal utility exceptions.
- **CI Impact:** A MySQL SQL schema dump cannot be loaded natively by SQLite in-memory CI runners (`DB_CONNECTION=sqlite DB_DATABASE=:memory:`) without database emulation or specialized converters.
- **Conclusion:** Standardizing on clean, consolidated PHP migration files is far more portable, robust, and CI-friendly than relying on `schema:dump`.

---

## 5. Migration Strategy Decision: Trade-Off Analysis

| Criteria | Option A: Keep History Unchanged | Option B: Consolidate Migrations (Recommended) |
| :--- | :--- | :--- |
| **Total Migration Files** | 27 files | **14 atomic files** |
| **Historical Provenance** | Preserves all intermediate git history from 2014 to 2026. | Historical phases are archived in `docs/reports/` and git commit logs. |
| **DDL Efficiency** | Executes multiple drops, alters, and constraint additions during `migrate:fresh`. | Creates tables with all columns, indexes, and constraints in a single `CREATE TABLE` pass. |
| **Developer Clarity** | Requires inspecting up to 3 migrations to understand a single table schema. | Every table has a single, authoritative definition file. |
| **Production Risk** | Zero risk to existing deployed databases. | Existing local/dev databases require `migrate:fresh`. (Zero production impact since project is pre-production). |
| **Repository Policy Alignment** | Retains technical debt contrary to Section 5. | Fully aligned with `CONTRIBUTING.md` Section 5 (*Pre-Production Migration Consolidation*). |
| **CI / SQLite Parity** | Supported. | Supported; reduces CI migration execution overhead. |

### Recommendation
**We strongly recommend Option B (Consolidation to 14 Migrations).**
- The project is currently in an active pre-production development baseline (`v12.0.0-security-baseline`).
- `CONTRIBUTING.md` explicitly permits consolidation during this phase.
- Consolidating now establishes a clean, modern foundation for all subsequent feature development (Phase 6+), preventing cumulative technical debt.

---

## 6. Next Steps & Phase Roadmap

1. **Phase 5.8.5 — Database Seeder Modularization & Credential Standardization:**
   - Decouple `DatabaseSeeder` into `RolePermissionSeeder`, `UserSeeder`, `AcademicStructureSeeder`, `SettingSeeder`.
   - Implement predictable credentials (`admin@gmail.com` / `password`).
   - Eliminate `config('app.domain')` from seeder logic.
2. **Phase 5.8.6 — Pre-Production Migration Consolidation Implementation:**
   - Consolidate legacy table alterations into 14 canonical migration files.
   - Verify zero schema drift via full DDL comparison.
   - Validate 107 passing tests on both MySQL and SQLite.
