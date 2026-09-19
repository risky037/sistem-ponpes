# Laravel 12 Database Migration Cleanup & Consolidation Assessment

**Project:** Sistem Informasi Pondok Pesantren Fatimah Az-Zahra  
**Date:** September 19, 2026  
**Active Baseline:** Laravel 12.69.2 | PHP 8.4.16 | MySQL 8.0 / SQLite in-memory  
**Test Suite:** 107 passed (531 assertions)  
**Status:** Assessment Only (No modifications executed)  

---

## 1. Executive Summary

This assessment audits the 27 database migration files in `database/migrations/` to evaluate schema design hygiene, identify technical debt and duplicate alterations, and formulate a safe pre-production migration consolidation strategy.

The audit reveals that **6 out of 27 migrations (22%)** exist solely to alter, correct, or harden schema definitions introduced in earlier migrations. Under the repository's pre-production policy ([CONTRIBUTING.md](../../../CONTRIBUTING.md) Section 5), consolidating these fragmented definitions into atomic table creations will reduce migration count from **27 to 14**, eliminate DDL churn during `migrate:fresh`, and establish a clean Laravel 12 schema baseline matching modern framework standards.

---

## 2. Chronological Migration Inventory

The repository currently contains 27 migration files spanning over a decade of framework evolution (2014–2026):

| # | Migration File | Target Table(s) | Action | Category |
| :---: | :--- | :--- | :--- | :--- |
| 1 | `2014_10_12_000000_create_users_table.php` | `users` | Create | Framework default (Laravel 5/10) |
| 2 | `2014_10_12_100000_create_password_reset_tokens_table.php` | `password_reset_tokens` | Create | Framework default |
| 3 | `2019_08_19_000000_create_failed_jobs_table.php` | `failed_jobs` | Create | Framework default |
| 4 | `2019_12_14_000001_create_personal_access_tokens_table.php` | `personal_access_tokens` | Create | Sanctum v3 default |
| 5 | `2023_08_29_075200_create_kelas_table.php` | `kelas` | Create | Academic domain |
| 6 | `2023_08_29_075221_create_kamars_table.php` | `kamars` | Create | Residence domain |
| 7 | `2023_08_29_075223_create_santris_table.php` | `santris` | Create | Core student registry |
| 8 | `2023_08_29_075224_create_tabungans_table.php` | `tabungans` | Create | Financial domain (Flawed) |
| 9 | `2023_08_29_075224_create_wali_santris_table.php` | `wali_santris` | Create | Guardian registry |
| 10 | `2023_08_29_075235_create_transaksi_tabungans_table.php` | `transaksi_tabungans` | Create | Financial ledger (Flawed) |
| 11 | `2023_09_12_212807_create_permission_tables.php` | 5 Spatie tables | Create | RBAC infrastructure |
| 12 | `2023_09_18_031115_create_wali_kelas_table.php` | `wali_kelas` | Create | Academic assignment |
| 13 | `2023_10_30_072010_create_jobs_table.php` | `jobs` | Create | Queue default |
| 14 | `2023_10_31_083759_create_provinsis_table.php` | `provinsis` | Create | Regional dataset |
| 15 | `2023_10_31_083810_create_kabupatens_table.php` | `kabupatens` | Create | Regional dataset |
| 16 | `2023_10_31_083824_create_kecamatans_table.php` | `kecamatans` | Create | Regional dataset |
| 17 | `2023_10_31_083829_create_kelurahans_table.php` | `kelurahans` | Create | Regional dataset |
| 18 | `2024_02_06_133257_create_alamat_santris_table.php` | `alamat_santris` | Create | Address hierarchy |
| 19 | `2024_05_19_001410_create_settings_table.php` | `settings` | Create | Application settings |
| 20 | `2024_05_19_153003_create_kelas_santris_table.php` | `kelas_santris` | Create | Academic pivot |
| 21 | `2024_05_19_153011_create_kamar_santris_table.php` | `kamar_santris` | Create | Residence pivot |
| 22 | `2024_05_20_070458_create_whatsapp_messages_table.php` | `whatsapp_messages` | Create | Gateway logging |
| 23 | `2024_05_20_231600_create_transfers_table.php` | `transfers` | Create | Balance transfer (Flawed) |
| 24 | `2024_05_20_231650_add_column_to_transaksi_tabungans.php` | `transaksi_tabungans` | **Alter** | Fixup (add `saldo_sebelumnya`, `keterangan`) |
| 25 | `2024_05_21_000000_create_activity_logs_table.php` | `activity_logs` | Create | Audit trail |
| 26 | `2024_05_22_000000_harden_financial_database_constraints.php` | `tabungans`, `transaksi_tabungans`, `transfers` | **Alter** | Architectural hardening (Foreign keys, Unique, CHECK constraints) |
| 27 | `2026_09_19_170022_update_personal_access_tokens_for_sanctum_v4.php` | `personal_access_tokens` | **Alter** | Sanctum v4 upgrade fixup |

---

## 3. Duplicate Alterations & Architectural Flaws

### 3.1. Financial Domain Alteration Churn
The financial schema underwent three successive migration stages:

```
[2023_08_29 Create] ──► [2024_05_20 Add Columns] ──► [2024_05_22 Harden Constraints]
• santri_id (cascade)    • adds saldo_sebelumnya      • drops foreign keys
• no unique on santri    • adds keterangan            • adds unique(santri_id)
• no check constraints                                • restores foreign keys (restrict)
                                                      • adds CHECK(saldo >= 0)
                                                      • adds CHECK(jumlah > 0)
                                                      • adds CHECK(pengirim != penerima)
                                                      • adds 3 composite indexes
```

1. **`tabungans`**:
   - Migration #8 created `tabungans` with `foreignId('santri_id')->cascadeOnDelete()`.
   - Migration #26 had to drop the foreign key, enforce `UNIQUE(santri_id)`, re-add foreign key with `onDelete('restrict')`, and add database CHECK constraint `chk_tabungans_saldo CHECK (saldo >= 0)`.
2. **`transaksi_tabungans`**:
   - Migration #10 created the table with basic transaction columns.
   - Migration #24 added `saldo_sebelumnya` and `keterangan`.
   - Migration #26 dropped the foreign key on `santri_id`, re-created it with `restrict`, and added composite index `idx_transaksi_santri_tgl_jenis`.
3. **`transfers`**:
   - Migration #23 created `transfers` with cascading deletes.
   - Migration #26 dropped foreign keys, re-added with `restrict`, added CHECK constraints (`jumlah_transfer > 0`, `pengirim_id != penerima_id`), and added composite indexes `idx_transfers_pengirim_created` and `idx_transfers_penerima_created`.

### 3.2. Sanctum Personal Access Tokens
- Migration #4 created `personal_access_tokens` with `string('name')` and no expiration index.
- Migration #27 altered `name` to `text('name')` and added an index on `expires_at` for Sanctum v4 pruning.

### 3.3. Framework Infrastructure Fragmentation
- Laravel 11 and 12 standardize core framework tables into three canonical migrations:
  - `0001_01_01_000000_create_users_table.php` (creates `users`, `password_reset_tokens`, and `sessions`).
  - `0001_01_01_000001_create_cache_table.php` (creates `cache`, `cache_locks`).
  - `0001_01_01_000002_create_jobs_table.php` (creates `jobs`, `job_batches`, `failed_jobs`).
- In this repository, `users`, `password_reset_tokens`, `failed_jobs`, and `jobs` are scattered across 4 migrations dating back to 2014.

---

## 4. Proposed Migration Consolidation Plan

### 4.1. Target Schema Architecture (14 Consolidated Migrations)

| New Migration Order | Filename / Schema | Incorporated Legacy Migrations | Rationale |
| :---: | :--- | :--- | :--- |
| **01** | `0001_01_01_000000_create_users_table.php` | #1 (`users`), #2 (`password_reset_tokens`) | Laravel 12 standard user foundation |
| **02** | `0001_01_01_000001_create_jobs_table.php` | #3 (`failed_jobs`), #13 (`jobs`) | Laravel 12 standard queue foundation |
| **03** | `0001_01_01_000002_create_personal_access_tokens_table.php` | #4, #27 | Single Sanctum v4 token definition with `text('name')` and `expires_at` index |
| **04** | `2023_08_29_075200_create_kelas_table.php` | #5 | Academic class structure |
| **05** | `2023_08_29_075221_create_kamars_table.php` | #6 | Residential dormitory structure |
| **06** | `2023_08_29_075223_create_santris_table.php` | #7 | Student registry with biographical records |
| **07** | `2023_08_29_075224_create_wali_santris_table.php` | #9 | Student guardian records |
| **08** | `2023_08_29_075224_create_tabungans_table.php` | #8, #26 (partial) | Atomic tabungan definition with `UNIQUE(santri_id)`, `restrictOnDelete()`, and `CHECK(saldo >= 0)` |
| **09** | `2023_08_29_075235_create_transfers_table.php` | #23, #26 (partial) | Atomic transfer definition with `restrictOnDelete()`, `CHECK(jumlah > 0)`, `CHECK(pengirim != penerima)`, and composite indexes |
| **10** | `2023_08_29_075236_create_transaksi_tabungans_table.php` | #10, #24, #26 (partial) | Atomic ledger definition with `saldo_sebelumnya`, `keterangan`, `restrictOnDelete()`, and `idx_transaksi_santri_tgl_jenis` |
| **11** | `2023_09_12_212807_create_permission_tables.php` | #11 | Spatie Permission v6 schema |
| **12** | `2023_09_18_031115_create_wali_kelas_table.php` | #12 | Class guardian assignments |
| **13** | `2023_10_31_083759_create_wilayah_tables.php` | #14, #15, #16, #17 | Consolidated Indonesian administrative hierarchy (`provinsis`, `kabupatens`, `kecamatans`, `kelurahans`) |
| **14** | `2024_02_06_133257_create_institutional_auxiliary_tables.php` | #18, #19, #20, #21, #22, #25 | Auxiliary tables (`alamat_santris`, `settings`, `kelas_santris`, `kamar_santris`, `whatsapp_messages`, `activity_logs`) |

---

## 5. Verification & Safety Safeguards

1. **Schema Dump Verification:** Prior to consolidation, export the current DDL for all 29 tables via `SHOW CREATE TABLE`.
2. **Post-Consolidation Schema Comparison:** Verify that `php artisan migrate:fresh` on the consolidated schema produces the exact same column types, nullable attributes, indexes, foreign keys, and CHECK constraints.
3. **Automated Regression Testing:** Execute the full 107-test PHPUnit suite to guarantee zero application or constraint regressions:
   - `DatabaseIntegrityConstraintsTest` (8 tests)
   - `FinancialRelationshipIntegrityTest` (8 tests)
   - `FinancialTransactionReliabilityTest` (12 tests)
