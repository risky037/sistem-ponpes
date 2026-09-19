# Phase 5.8.1 — Laravel 12 CI & Repository Modernization Assessment

**Project:** Sistem Informasi Pondok Pesantren Fatimah Az-Zahra  
**Date:** September 19, 2026  
**Current Baseline:** Laravel 12.69.2 | PHP 8.4.16 | MySQL 8.0 / SQLite in-memory  
**Active Branch:** `develop` (canonical baseline at `1c95088e`)  
**Baseline Release Tag:** `v12.0.0-security-baseline`  
**Test Suite:** 107 passing tests (531 assertions)  
**Status:** Assessment Only (No modifications executed)  

---

## 1. Executive Summary

Phase 5.8.1 assesses the repository infrastructure, CI/CD pipeline, development tooling, and codebase conventions following the stabilization of the Laravel 12.69.2 baseline. The objective is to identify technical debt, eliminate legacy artifacts from previous major version iterations (Laravel 10 & 11), evaluate CI pipeline efficiency, and prepare concrete modernization plans for database migrations, seeders, documentation, and institutional domain configurations.

---

## 2. Objective 1: GitHub Actions CI Matrix Audit

### 2.1. Current Configuration (`.github/workflows/ci.yml`)
- **Triggers:** `push` and `pull_request` on `develop` and `main` branches.
- **Runner OS:** `ubuntu-latest`.
- **PHP Matrix:** `matrix: php: ['8.2', '8.4']`.
- **Pipeline Sequence:**
  1. Setup PHP (`shivammathur/setup-php@v2` with core extensions + `sqlite, pdo_sqlite`).
  2. `composer validate --strict`.
  3. Cache Composer dependencies (`actions/cache@v4`).
  4. `composer install --prefer-dist --no-interaction --no-progress`.
  5. Copy `.env.example` & generate application key.
  6. Code style check: `vendor/bin/pint --test`.
  7. Test suite execution: `php artisan test --without-tty` with SQLite in-memory database (`DB_CONNECTION=sqlite DB_DATABASE=:memory:`).
  8. Composer vulnerability scan: `composer audit`.

### 2.2. Gaps & Modernization Opportunities
1. **Missing Concurrency Control:** Rapid consecutive commits to pull requests trigger multiple overlapping GitHub Actions workflow runs, consuming runner limits and queue time.
   - *Recommendation:* Add concurrency control with cancellation:
     ```yaml
     concurrency:
       group: ${{ github.workflow }}-${{ github.ref }}
       cancel-in-progress: true
     ```
2. **Missing Manual Dispatch Trigger:** Lacks `workflow_dispatch:`, preventing manual workflow execution from the GitHub Actions dashboard for ad-hoc verification.
3. **Absence of Frontend Asset Bundling Validation:** The application uses Vite, Bootstrap 5, jQuery, and custom CSS/JS assets. The current CI pipeline does not install Node dependencies or test frontend builds (`npm ci && npm run build`). Broken Blade asset directives, CSS/JS syntax errors, or missing NPM dependencies can merge unnoticed.
4. **Tooling Command Standardization:** Line 54 executes `vendor/bin/pint --test` directly instead of invoking the standardized composer script `composer run lint:check`.
5. **Database Test Constraints Discrepancy:**
   - In SQLite in-memory mode, 104 tests pass and 3 tests are conditionally skipped (`tabungan saldo check constraint rejects negative balance`, `transfer jumlah check constraint rejects zero or negative amount`, `transfer parties check constraint rejects self transfer`) due to SQLite constraint syntax nuances.
   - Reference project `../gakutsu.net` addresses this by spinning up a `mysql:8.0` Docker service container in its backend quality job.
6. **Auxiliary Security Scan Workflow (`security.yml`):**
   - The scheduled weekly security audit hardcodes `php-version: '8.2'` instead of aligning with the PHP 8.4 runtime baseline.

---

## 3. Objective 2: PHP 8.2 Removal / Replacement Evaluation

### 3.1. PHP Lifecycle & Ecosystem Context
| PHP Version | Initial Release | Active Support End | Security Support End (EOL) | Ecosystem Status |
| :--- | :--- | :--- | :--- | :--- |
| **PHP 8.2** | Dec 8, 2022 | Dec 31, 2024 | Dec 31, 2025 | **Security fixes only** |
| **PHP 8.3** | Nov 23, 2023 | Dec 31, 2025 | Dec 31, 2026 | Active maintenance |
| **PHP 8.4** | Nov 21, 2024 | Dec 31, 2026 | Dec 31, 2027 | Current active stable |

### 3.2. Project Runtime & Dependency Analysis
- Local workstation runtime: **PHP 8.4.16**.
- `composer.json` declares `"php": "^8.2"`, satisfying Laravel 12 core minimum requirements.
- Running `composer check-platform-reqs` on PHP 8.4 confirms 100% compliance across all 25 extensions and runtime APIs.
- The CI matrix currently tests `['8.2', '8.4']`, skipping PHP 8.3 entirely.

### 3.3. Evaluation & Recommendations
- **Cost of Retaining PHP 8.2 in CI:**
  - Doubles CI runner minutes on every commit and PR.
  - PHP 8.2 is approaching complete End of Life (EOL) within months.
- **Recommended Strategy:**
  - **Option A (Target Single PHP 8.4 Runtime — Recommended):**
    - Transition CI matrix to `php-version: '8.4'` (as implemented in `../gakutsu.net`).
    - Halves CI execution time, eliminates duplicate runner queueing, and reflects the exact production/staging target runtime.
  - **Option B (Dual Modern Matrix: 8.3 & 8.4):**
    - Replace `['8.2', '8.4']` with `['8.3', '8.4']`. Ensures compatibility across all actively supported PHP versions while removing deprecated 8.2.
  - **`composer.json` Requirement:** Keep `"php": "^8.2"` in `composer.json` for installer tolerance unless PHP 8.3/8.4-specific syntax (e.g. typed class constants, property hooks) is introduced.

---

## 4. Objective 3: Audit of Unused Root Files

### 4.1. Inventory Analysis
The repository root currently contains 33 files and 14 subdirectories.

| Category | File | Status | Assessment / Action Plan |
| :--- | :--- | :--- | :--- |
| **Tooling Config** | `.editorconfig`, `.gitattributes`, `.gitignore`, `pint.json`, `phpunit.xml`, `vite.config.js` | Active | Essential development tooling configurations. |
| **Core Framework** | `artisan`, `composer.json`, `composer.lock`, `package.json`, `package-lock.json` | Active | Core runtime and dependency manifests. |
| **Environment** | `.env.example`, `.env` (gitignored) | Active | Required environment configuration prototypes. |
| **Documentation** | `README.md`, `CONTRIBUTING.md`, `LICENSE` | Active | Essential repository governance and developer documentation. |
| **Documentation** | `CHANGELOG.md` | Outdated | Lists v1.1.0 through v4.0.0 as "Planned"; requires updating to record completed Laravel 11/12 releases. |
| **Obsolete Tooling** | `qodana.yaml` | **Unused / Dead** | Unused JetBrains linter configuration hardcoding obsolete PHP 8.1. Candidate for deletion. |
| **Root Clutter** | 14 Migration & Assessment `.md` reports (~200 KB) | **Misplaced** | Historical markdown reports clutter the root directory. Should be moved to `docs/reports/`. |

---

## 5. Objective 4: `qodana.yaml` Usage Audit

### 5.1. File Inspection
```yaml
version: "1.0"
profile:
  name: qodana.starter
php:
  version: 8.1 #(Applied in CI/CD pipeline)
linter: jetbrains/qodana-php:latest
```

### 5.2. Audit Findings
- **Zero Usage:** Full repository search confirms `qodana` is not invoked in any GitHub Actions workflow, composer script, Dockerfile, or local development script.
- **Obsolete Version:** The file specifies `php: version: 8.1`, which is incompatible with Laravel 12 (minimum PHP 8.2).
- **Redundancy:** Code styling and static analysis are already handled natively by Laravel Pint (`pint.json`, `composer run lint`) and automated test suites.
- **Recommendation:** Safe for complete removal in the next modernization phase.

---

## 6. Objective 5: Documentation Structure Audit

### 6.1. Current State
- **Root Directory:** Overcrowded with 14 historical audit and migration reports generated during Phases 5.1 through 5.7:
  - `Laravel11_Compatibility_Assessment_Report.md`
  - `Laravel11_Dependency_Finalization_Assessment.md`
  - `Laravel11_Dependency_Finalization_Implementation.md`
  - `Laravel11_Migration_Assessment.md`
  - `Laravel11_PreMigration_Audit.md`
  - `Laravel12_Baseline_Initialization_Report.md`
  - `Laravel12_Migration_Implementation_Report.md`
  - `Laravel12_Post_Migration_Stabilization_Report.md`
  - `Laravel12_Repository_Hygiene_Report.md`
  - `Laravel12_Security_Hardening_Report.md`
  - `Laravel12_Upgrade_Assessment.md`
  - `debug_cleanup_implementation_plan.md`
  - `financial_transaction_reliability_assessment.md`
  - `intervention_image_migration_assessment.md`
- **`docs/` Directory:** Only contains `OPEN_SOURCE_NOTICE.md` and `docs/api/synchronization.md`.

### 6.2. Proposed Documentation Restructuring Plan
Relocate historical and technical reports into a clean, hierarchical `docs/reports/` architecture:
```
docs/
├── OPEN_SOURCE_NOTICE.md
├── api/
│   └── synchronization.md
└── reports/
    ├── README.md                           (Index of all historical audits)
    ├── laravel11/
    │   ├── 01_compatibility_assessment.md
    │   ├── 02_premigration_audit.md
    │   ├── 03_migration_assessment.md
    │   ├── 04_dependency_assessment.md
    │   └── 05_dependency_implementation.md
    ├── laravel12/
    │   ├── 01_upgrade_assessment.md
    │   ├── 02_core_upgrade_report.md
    │   ├── 03_post_migration_stabilization.md
    │   ├── 04_security_hardening_report.md
    │   ├── 05_repository_hygiene_report.md
    │   └── 06_baseline_initialization_report.md
    └── domain-assessments/
        ├── financial_transaction_reliability.md
        ├── intervention_image_v3_migration.md
        └── debug_statement_cleanup.md
```
**Benefits:**
- Restores a pristine root directory containing only standard repository files (`README.md`, `CONTRIBUTING.md`, `CHANGELOG.md`, `LICENSE`).
- Preserves complete historical provenance in a logical, navigable hierarchy.

---

## 7. Objective 6: Migration Consolidation Plan

### 7.1. Current Migration Health
There are currently 27 migration files dating from 2014 to 2026. Multiple tables suffer from fragmented lifecycle definitions:

1. **`personal_access_tokens`**:
   - Created in `2019_12_14_000001_create_personal_access_tokens_table.php`.
   - Altered in `2026_09_19_170022_update_personal_access_tokens_for_sanctum_v4.php` (broadens `name` column and adds index on `expires_at`).
2. **`transaksi_tabungans`**:
   - Created in `2023_08_29_075235_create_transaksi_tabungans_table.php`.
   - Altered in `2024_05_20_231650_add_column_to_transaksi_tabungans.php` (adds nullable `transfer_id`).
   - Altered in `2024_05_22_000000_harden_financial_database_constraints.php` (adds foreign key constraints and `onDelete('restrict')`).
3. **`tabungans` & `transfers`**:
   - Created in `2023_08_29` and `2024_05_20`.
   - Altered in `2024_05_22` with check constraints (`saldo >= 0`, `jumlah > 0`, `pengirim_id != penerima_id`) and unique indexes.
4. **Default Framework Tables**:
   - `users`, `password_reset_tokens`, `failed_jobs`, `jobs` use legacy separate migrations spanning 2014–2023.

### 7.2. Proposed Migration Consolidation Strategy
According to `CONTRIBUTING.md` Section 5: *"During Active Development / Pre-Production: Existing migrations may be consolidated and cleaned up when appropriate to minimize migration bloat."*

- **Consolidation Target Structure:**
  - Fold `update_personal_access_tokens_for_sanctum_v4` directly into the base `create_personal_access_tokens_table`.
  - Fold `add_column_to_transaksi_tabungans` (`transfer_id`) and financial integrity constraints directly into `create_transaksi_tabungans_table`, `create_tabungans_table`, and `create_transfers_table`.
  - Reduce total migrations from 27 down to ~18 clean, atomic table definitions.
- **Verification & Safeguards:**
  - Run full migration rollback and re-apply cycle: `php artisan migrate:fresh --seed`.
  - Verify all 107 tests in `DatabaseIntegrityConstraintsTest` and `FinancialRelationshipIntegrityTest` pass without regressions.

---

## 8. Objective 7: Seeder Refactor Plan

### 8.1. Current Seeder Health
Only two files exist in `database/seeders`:
1. **`DatabaseSeeder.php`**:
   - Performs mixed responsibilities: creates users, classes, rooms, settings, invokes `RoleSeeder`, and contains 30 lines of commented-out wilayah JSON iterations (~83,000 records).
2. **`RoleSeeder.php`**:
   - Highly coupled: directly calls `User::find(1)->assignRole('Administrator')`, `User::find(2)->assignRole('Keuangan')`, `User::find(3)->assignRole('Pengurus')`.
   - Will throw fatal exceptions if run in isolation without prior user creation.

### 8.2. Proposed Modular Seeder Architecture
Deconstruct into single-responsibility seeders:

| Seeder Class | Responsibility | Idempotency |
| :--- | :--- | :--- |
| **`RolePermissionSeeder.php`** | Creates permissions from `config('permission.*')` and creates system roles (`Administrator`, `Keuangan`, `Pengurus`, `Santri`, `Alumni`). Assigns permissions to roles. Completely decoupled from users. | `Permission::firstOrCreate`, `Role::firstOrCreate` |
| **`UserSeeder.php`** | Creates default institutional accounts (Admin, Keuangan, Pengurus) and assigns roles by name. | `User::firstOrCreate` |
| **`AcademicStructureSeeder.php`** | Creates baseline default `Kelas` and `Kamar` entities. | `Kelas::firstOrCreate`, `Kamar::firstOrCreate` |
| **`SettingSeeder.php`** | Seeds default application configuration records. | `Setting::firstOrCreate` |
| **`WilayahImporterCommand.php`** | Convert commented-out ~83k JSON loops into a dedicated Artisan command (`php artisan wilayah:import` or `--chunk=500`) with progress bar and database transactions, rather than freezing `DatabaseSeeder`. | Chunked batch insert |

**Modernized `DatabaseSeeder.php`:**
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

## 9. Objective 8: Audit of Hardcoded Organization & Domain Values

### 9.1. Hardcoded Institutional & Domain References
A comprehensive grep audit revealed widespread hardcoded values across views, controllers, configuration, and helpers:

| Value / Domain | Locations | Issue / Risk |
| :--- | :--- | :--- |
| **`DIGITREN` / `Digitren`** | 17 Blade page titles (`@section('title', '... | DIGITREN')`), `components/navbar.blade.php`, `components/footer.blade.php` | Page titles, sidebar branding, and footer hardcode upstream project name instead of dynamic `config('app.name')` ("Sistem Ponpes Fatimah Az-Zahra") or setting. |
| **`@digitren.com`** | `config/app.php` line 13 (`'domain' => env('APP_DOMAIN', '@digitren.com')`) | Hardcoded fallback domain includes leading `@` sign, creating inconsistency when concatenated. |
| **`@digitren.net`** | `synchronizationController.php` lines 129 & 160 | Hardcoded domain in email generation instead of using `config('app.domain')`. |
| **`admin@digitren.net`** | `README.md` lines 213 & 233 | Security contact email hardcodes upstream domain. |
| **`https://connect.labelin.co/send-message`** | `TransaksiTabunganObserver.php` (line 12), `TransaksiController.php` (line 201) | WhatsApp Gateway endpoint is hardcoded in application logic instead of being retrieved from `config('whatsapp.endpoint')`. |
| **`digitren-0001-469b9239adfb.json`** | `config/google.php` line 60 | Hardcoded path to non-existent JSON file in `public/files/sheets/` while `GOOGLE_SERVICE_ACCOUNT_JSON_LOCATION` env is commented out. |
| **`db_sistempesantren_v2`** | `.env.example` line 14 | Database name in `.env.example` contradicts `README.md` (`digitren`). |
| **`6281234567890`** | `config/whatsapp.php` line 15 | Hardcoded dummy sender number fallback. |

### 9.2. Email Generation Inconsistency & Collision Vulnerability
- **`SantriController`**: `'email' => 'santri_'.Str::slug($nama).config('app.domain')`
- **`SantriImport`**: `'email' => Str::slug($nama).config('app.domain')` (Missing `santri_` prefix)
- **`synchronizationController`**: `'email' => 'santri_'.Str::slug($nama).'@digitren.net'` (Hardcoded domain)
- **High Risk:** In all three locations, student emails are generated solely using `Str::slug($nama)`. If two students share the same name (common in institutional pesantren data), a fatal SQL unique constraint error occurs on `users.email`.
- **Recommendation:** Standardize email generation to include student ID or student registration number (`'santri_'.$santri->no_induk.'@'.config('app.domain_name')`).

---

## 10. Prioritized Modernization Roadmap

| Phase | Milestone | Priority | Scope |
| :--- | :--- | :--- | :--- |
| **5.8.2** | **Repository Hygiene & Documentation Reorganization** | High | Move 14 root reports to `docs/reports/`, update `CHANGELOG.md`, delete obsolete `qodana.yaml`. |
| **5.8.3** | **CI Pipeline Modernization** | High | Add concurrency, frontend build check (`npm ci && npm run build`), standardize on PHP 8.4, and use `composer run lint:check`. |
| **5.8.4** | **Configuration & Domain Decoupling** | High | Unify `config/app.php` domain, remove hardcoded Labelin URL and Google Sheets JSON path, bind view titles to `config('app.name')`. |
| **5.8.5** | **Database Seeder Modularization** | Medium | Separate seeders into `RolePermissionSeeder`, `UserSeeder`, `AcademicStructureSeeder`, `SettingSeeder`. |
| **5.8.6** | **Pre-Production Migration Consolidation** | Medium | Consolidate table alterations and constraints into base migration schemas. |
