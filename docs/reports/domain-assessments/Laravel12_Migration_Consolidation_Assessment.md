# Phase 5.8.6A — Laravel 12 Database Migration Consolidation Assessment

**Project:** Sistem Informasi Pondok Pesantren Fatimah Az-Zahra  
**Date:** September 19, 2026  
**Active Baseline:** Laravel 12.69.2 | PHP 8.4.16 | MySQL (MariaDB 10.4.32)  
**Branch:** `develop` (commit `fd530028`, synchronized with `origin/develop`)  
**Release Tag:** `v12.0.0-security-baseline`  
**Test Suite:** 107 passed (531 assertions)  
**Status:** Assessment Only — Zero modifications executed  

---

## 1. Executive Summary

This report delivers a complete audit of the 27 database migration files in `database/migrations/`, comparing the migration history against the current database schema produced by `php artisan migrate:fresh`, and identifies a safe and complete consolidation plan.

**Key Findings:**
- **27 migration files** produce **29 application tables** (the Spatie `create_permission_tables` migration creates 5 tables).
- **6 migrations (22%)** exist solely to alter, fix, or harden schema definitions made in earlier migrations. These can be merged into their parent create migrations without any schema change.
- **4 framework-default migrations** use legacy Laravel 5/9/10 timestamp formats (e.g. `2014_10_12_*`). The modern Laravel 12 convention uses `0001_01_01_*` timestamps that sort before all application migrations and consolidate related tables.
- **4 regional hierarchy migrations** (`provinsis`, `kabupatens`, `kecamatans`, `kelurahans`) are individually trivial and can be consolidated into one `create_wilayah_tables` migration without loss.
- **Post-consolidation target:** Reduce from **27 to 14 atomic migrations** that are individually reversible, have no dependencies on alteration patches, and match current Laravel 12 framework conventions.
- **No schema drift** found between the migration code and the live database DDL. The current schema after `migrate:fresh` exactly matches the live schema.
- **No business logic changes** are required. The consolidation is purely a DDL restructuring exercise.

---

## 2. Complete Migration Inventory

### 2.1. Chronological Audit Table

| # | Migration Timestamp | File Name | Action | Tables Touched | Category | Disposition |
| :---: | :---: | :--- | :---: | :--- | :--- | :---: |
| 01 | `2014_10_12_000000` | `create_users_table` | CREATE | `users` | Framework Default (Laravel 5) | **MERGE** into M01 |
| 02 | `2014_10_12_100000` | `create_password_reset_tokens_table` | CREATE | `password_reset_tokens` | Framework Default | **MERGE** into M01 |
| 03 | `2019_08_19_000000` | `create_failed_jobs_table` | CREATE | `failed_jobs` | Framework Default (Laravel 7) | **MERGE** into M02 |
| 04 | `2019_12_14_000001` | `create_personal_access_tokens_table` | CREATE | `personal_access_tokens` | Sanctum v3 Default | **MERGE** with #27 into M03 |
| 05 | `2023_08_29_075200` | `create_kelas_table` | CREATE | `kelas` | Academic Domain | **KEEP** as-is (M04) |
| 06 | `2023_08_29_075221` | `create_kamars_table` | CREATE | `kamars` | Residential Domain | **KEEP** as-is (M05) |
| 07 | `2023_08_29_075223` | `create_santris_table` | CREATE | `santris` | Core Student Registry | **KEEP** as-is (M06) |
| 08 | `2023_08_29_075224` | `create_tabungans_table` | CREATE | `tabungans` | Financial (Flawed — cascade, no unique, no CHECK) | **MERGE** with #26 into M07 |
| 09 | `2023_08_29_075224` | `create_wali_santris_table` | CREATE | `wali_santris` | Guardian Registry | **KEEP** as-is (M08) |
| 10 | `2023_08_29_075235` | `create_transaksi_tabungans_table` | CREATE | `transaksi_tabungans` | Financial Ledger (Flawed — missing columns, cascade) | **MERGE** with #24 + #26 into M09 |
| 11 | `2023_09_12_212807` | `create_permission_tables` | CREATE | `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` | Spatie RBAC v6 | **KEEP** as-is (M10) |
| 12 | `2023_09_18_031115` | `create_wali_kelas_table` | CREATE | `wali_kelas` | Academic Assignment | **KEEP** as-is (M11) |
| 13 | `2023_10_30_072010` | `create_jobs_table` | CREATE | `jobs` | Framework Default (Laravel 9) | **MERGE** into M02 |
| 14 | `2023_10_31_083759` | `create_provinsis_table` | CREATE | `provinsis` | Regional Dataset | **MERGE** into M12 |
| 15 | `2023_10_31_083810` | `create_kabupatens_table` | CREATE | `kabupatens` | Regional Dataset | **MERGE** into M12 |
| 16 | `2023_10_31_083824` | `create_kecamatans_table` | CREATE | `kecamatans` | Regional Dataset | **MERGE** into M12 |
| 17 | `2023_10_31_083829` | `create_kelurahans_table` | CREATE | `kelurahans` | Regional Dataset | **MERGE** into M12 |
| 18 | `2024_02_06_133257` | `create_alamat_santris_table` | CREATE | `alamat_santris` | Address Hierarchy | **KEEP** as-is (M13) |
| 19 | `2024_05_19_001410` | `create_settings_table` | CREATE | `settings` | Application Settings | **KEEP** as-is |
| 20 | `2024_05_19_153003` | `create_kelas_santris_table` | CREATE | `kelas_santris` | Academic Pivot | **MERGE** with #21, #22, #25 into M14 |
| 21 | `2024_05_19_153011` | `create_kamar_santris_table` | CREATE | `kamar_santris` | Residential Pivot | **MERGE** into M14 |
| 22 | `2024_05_20_070458` | `create_whatsapp_messages_table` | CREATE | `whatsapp_messages` | WA Gateway Logging | **MERGE** into M14 |
| 23 | `2024_05_20_231600` | `create_transfers_table` | CREATE | `transfers` | Financial (Flawed — cascade, no CHECK, no indexes) | **MERGE** with #26 into M09 |
| 24 | `2024_05_20_231650` | `add_column_to_transaksi_tabungans` | ALTER | `transaksi_tabungans` | **Fixup** — adds `saldo_sebelumnya`, `keterangan` | **ABSORB** into M09 |
| 25 | `2024_05_21_000000` | `create_activity_logs_table` | CREATE | `activity_logs` | Audit Trail | **MERGE** into M14 |
| 26 | `2024_05_22_000000` | `harden_financial_database_constraints` | ALTER | `tabungans`, `transaksi_tabungans`, `transfers` | **Architectural Hardening** — drops FKs, adds UNIQUE, RESTRICT, CHECK, indexes | **ABSORB** into M07, M08, M09 |
| 27 | `2026_09_19_170022` | `update_personal_access_tokens_for_sanctum_v4` | ALTER | `personal_access_tokens` | **Sanctum v4 Upgrade** — `text('name')`, `expires_at` index | **ABSORB** into M03 |

---

## 3. Schema Drift Analysis

### 3.1. Framework Infrastructure Fragmentation

The current migration set scatters framework tables across four different timestamp epochs, in contrast to the modern Laravel 12 convention of three canonical `0001_01_01_*` migrations that sort before all application data migrations.

**Current State (4 migrations):**
```
2014_10_12_000000  → users
2014_10_12_100000  → password_reset_tokens
2019_08_19_000000  → failed_jobs
2023_10_30_072010  → jobs
```

**Modern Laravel 12 Standard (3 canonical migrations):**
```
0001_01_01_000000  → users, password_reset_tokens, sessions
0001_01_01_000001  → cache, cache_locks
0001_01_01_000002  → jobs, job_batches, failed_jobs
```

> [!NOTE]
> The current `users` table intentionally omits `remember_token` and `email_verified_at` (not used by this application). The consolidated migration should preserve this minimal schema. The modern `sessions` table and `cache` tables are also absent — the application uses database session driver status should be verified before adding them.

### 3.2. Financial Domain Triple-Alteration Churn

The most significant schema drift is in the financial domain. Three tables (`tabungans`, `transaksi_tabungans`, `transfers`) underwent destructive-and-restore sequences across three migration steps:

**`tabungans` — 2 migration files for 1 table:**
```
#08 [CREATE]: santri_id → cascadeOnDelete(), no UNIQUE, no CHECK
#26 [ALTER]:  DROP FK, ADD UNIQUE(santri_id), RESTORE FK restrictOnDelete(),
              ADD CONSTRAINT chk_tabungans_saldo CHECK (saldo >= 0)
```

**`transaksi_tabungans` — 3 migration files for 1 table:**
```
#10 [CREATE]: santri_id → cascadeOnDelete(), missing saldo_sebelumnya, keterangan columns
#24 [ALTER]:  ADD saldo_sebelumnya, keterangan columns
#26 [ALTER]:  DROP FK, RESTORE FK restrictOnDelete(),
              ADD INDEX idx_transaksi_santri_tgl_jenis
```

**`transfers` — 2 migration files for 1 table:**
```
#23 [CREATE]: pengirim_id, penerima_id → cascadeOnDelete(), no CHECK, no indexes
#26 [ALTER]:  DROP FK x2, RESTORE FK restrictOnDelete() x2,
              ADD CONSTRAINT chk_transfers_jumlah CHECK (jumlah_transfer > 0)
              ADD CONSTRAINT chk_transfers_parties CHECK (pengirim_id != penerima_id)
              ADD INDEX idx_transfers_pengirim_created
              ADD INDEX idx_transfers_penerima_created
```

The `harden_financial_database_constraints` migration (#26) includes a **pre-flight data integrity check** (`$duplicateCount > 0` → throw RuntimeException). This check is only relevant during an in-place migration run; in a clean `migrate:fresh`, the tables are always empty. The check becomes **unnecessary overhead** after consolidation.

### 3.3. Sanctum v3 → v4 Alteration

Migration #27 alters `personal_access_tokens.name` from `string (varchar 255)` to `text` and adds an index on `expires_at`. This is a straightforward Sanctum v4 upgrade fixup that should be folded directly into the creation migration.

**Discrepancy note:** The creation migration (#04) creates `name` as `$table->string('name')` (varchar 255), but the live schema shows `name text NOT NULL`. The alteration migration correctly upgraded this. After consolidation, the creation migration should directly declare `$table->text('name')`.

### 3.4. Unused Import References

Two migrations import model classes that are unused or obsolete:
- `2023_08_29_075223_create_santris_table.php` imports `Kamar`, `Kelas`, `WaliSantri` — none of these are referenced in the migration body.
- `2023_09_18_031115_create_wali_kelas_table.php` imports `TahunAkademik` — this model does not exist in the application.

These dead imports would be removed during consolidation.

### 3.5. Regional Table Fragmentation (4 → 1 Migration)

The four Indonesian administrative hierarchy tables (`provinsis`, `kabupatens`, `kecamatans`, `kelurahans`) are defined across four consecutive migrations all timestamped `2023_10_31`. They are logically coupled (each depends on the previous through foreign keys) and can be consolidated into a single `create_wilayah_tables` migration.

---

## 4. Current Database Schema Verification

Schema was extracted via `SHOW CREATE TABLE` from the live database following `migrate:fresh`. The live DDL is the **ground truth target** for the consolidated migrations.

### 4.1. Framework Tables

| Table | Key Columns | Indexes | Foreign Keys |
| :--- | :--- | :--- | :--- |
| `users` | `id`, `name`, `email` (unique), `password`, timestamps | PK, UNIQUE email | — |
| `password_reset_tokens` | `email` (primary), `token`, `created_at` | PK (email) | — |
| `failed_jobs` | `id`, `uuid` (unique), `connection`, `queue`, `payload`, `exception`, `failed_at` | PK, UNIQUE uuid | — |
| `jobs` | `id`, `queue` (indexed), `payload`, `attempts`, `reserved_at`, `available_at`, `created_at` | PK, KEY queue | — |
| `personal_access_tokens` | `id`, `tokenable_type+id` (morphs), `name` (text), `token (64)` (unique), `abilities`, `last_used_at`, `expires_at`, timestamps | PK, UNIQUE token, KEY tokenable, KEY expires_at | — |

### 4.2. Core Application Tables

| Table | Key Columns | Notable Indexes | Foreign Keys |
| :--- | :--- | :--- | :--- |
| `kelas` | `id`, `kode`, `tingkatan`, `kelas`, `keterangan` (text), timestamps | PK | — |
| `kamars` | `id`, `kode`, `nama`, `blok`, `jumlah_santri` (int default 0), `maksimal_santri` (int default 6), timestamps | PK | — |
| `santris` | `id`, `no_induk` (varchar 8, unique), `user_id`, `jenis_kelamin` (enum), `nik`, `kk`, `whatsapp` (bigint), `tanggal_lahir`, `tempat_lahir`, `tahun_masuk`, `tahun_masuk_hijriyah`, `tanggal_boyong`, `tanggal_boyong_hijriyah`, `status` (enum, default 'Santri Aktif'), `maksimal_perizinan` (int default 10), `foto` (default 'santri.png'), timestamps | PK, UNIQUE no_induk | `user_id → users CASCADE` |
| `wali_santris` | `id`, `santri_id`, `nama_ayah`, `nama_ibu`, timestamps | PK | `santri_id → santris CASCADE` |
| `wali_kelas` | `id`, `kelas_id`, `santri_id`, timestamps | PK | `kelas_id → kelas CASCADE`, `santri_id → santris CASCADE` |

### 4.3. Financial Tables (Post-Hardening Final Schema)

| Table | Key Columns | Constraints | Foreign Keys |
| :--- | :--- | :--- | :--- |
| `tabungans` | `id`, `santri_id` (UNIQUE), `saldo` (bigint default 0), `keterangan` (text nullable), timestamps | UNIQUE santri_id, CHECK saldo>=0 | `santri_id → santris RESTRICT` |
| `transaksi_tabungans` | `id`, `santri_id`, `tanggal_transaksi` (date default today), `jenis_transaksi` (enum Setoran/Penarikan), `tujuan` (nullable), `jumlah_transaksi` (bigint default 0), `saldo_sebelumnya` (bigint nullable), `saldo_saatini` (bigint default 0), `keterangan` (varchar nullable), timestamps | INDEX idx_transaksi_santri_tgl_jenis | `santri_id → santris RESTRICT` |
| `transfers` | `id`, `pengirim_id`, `penerima_id`, `jumlah_transfer` (bigint), `keterangan` (nullable), timestamps | INDEX idx_transfers_pengirim_created, INDEX idx_transfers_penerima_created, CHECK jumlah_transfer>0, CHECK pengirim_id!=penerima_id | `pengirim_id → santris RESTRICT`, `penerima_id → santris RESTRICT` |

### 4.4. Auxiliary Tables

| Table | Key Columns | Notes |
| :--- | :--- | :--- |
| `permissions` | `id`, `name`, `guard_name`, timestamps | UNIQUE (name, guard_name) |
| `roles` | `id`, `name`, `guard_name`, timestamps | UNIQUE (name, guard_name) |
| `model_has_permissions` | Pivot: permission_id, model_type, model_id | Spatie standard |
| `model_has_roles` | Pivot: role_id, model_type, model_id | Spatie standard |
| `role_has_permissions` | Pivot: permission_id, role_id | Spatie standard |
| `provinsis` | `id`, `name`, timestamps | — |
| `kabupatens` | `id`, `provinsi_id`, `name`, timestamps | `provinsi_id → provinsis CASCADE` |
| `kecamatans` | `id`, `kabupaten_id`, `name`, timestamps | `kabupaten_id → kabupatens CASCADE` |
| `kelurahans` | `id`, `kecamatan_id`, `name`, timestamps | `kecamatan_id → kecamatans CASCADE` |
| `alamat_santris` | `id`, `santri_id`, `provinsi_id`, `kabupaten_id`, `kecamatan_id`, `kelurahan_id`, `dusun`, timestamps | All FKs → CASCADE |
| `settings` | `id`, `favicon`, `logo`, `whatsapp_feature` (bool), `kts_master`, `sender` (bigint), `whatsapp_api_key`, `log_activity` (bool), timestamps | — |
| `kelas_santris` | `id`, `santri_id`, `kelas_id`, timestamps | `santri_id → santris CASCADE`, `kelas_id → kelas CASCADE` |
| `kamar_santris` | `id`, `santri_id`, `kamar_id`, timestamps | `santri_id → santris CASCADE`, `kamar_id → kamars CASCADE` |
| `whatsapp_messages` | `id`, `pesan_tarik_tunai` (text nullable), `pesan_setor_tunai` (text nullable), timestamps | — |
| `activity_logs` | `id`, `user_id`, `activity`, timestamps | `user_id → users CASCADE` |

---

## 5. Relationship Audit

### 5.1. User → Santri Relationship
```
users (1) ──── (1) santris
```
- `santris.user_id` → `users.id` ON DELETE CASCADE.
- One user account per student record (authentication account linked to biographic record).
- **Verified:** Cascade delete is correct — deleting a user account removes the linked student record.

### 5.2. Santri Financial Relationships
```
santris (1) ──── (1) tabungans          [UNIQUE(santri_id), RESTRICT]
         (1) ──── (*) transaksi_tabungans [RESTRICT]
         (1) ──── (*) transfers as sender  [RESTRICT]
         (1) ──── (*) transfers as receiver [RESTRICT]
```
- **`tabungans.santri_id`:** UNIQUE constraint enforces one savings account per student. ON DELETE RESTRICT prevents student deletion while savings account exists.
- **`transaksi_tabungans.santri_id`:** ON DELETE RESTRICT prevents student deletion while transaction ledger exists.
- **`transfers.pengirim_id` / `penerima_id`:** ON DELETE RESTRICT prevents student deletion while transfer records exist.
- CHECK constraints on `transfers` prevent zero/negative amounts and self-transfers at the database level.
- **Verified:** All three restrict constraints are correctly enforced by `DatabaseIntegrityConstraintsTest` (8 tests).

### 5.3. Santri Academic Relationships
```
santris (*) ──── (*) kelas    (via kelas_santris)
         (*) ──── (*) kamars  (via kamar_santris)
         (1) ──── (1) wali_santris
         (*) ──── (*) kelas   (via wali_kelas)
```
- Pivot tables `kelas_santris` and `kamar_santris` use CASCADE delete on both sides.
- `wali_santris` (parent/guardian record) is CASCADE deleted with the student.
- `wali_kelas` (class monitor assignment) uses CASCADE delete from both `kelas` and `santris`.

### 5.4. Santri Address Relationships
```
santris (1) ──── (0..1) alamat_santris
provinsis → kabupatens → kecamatans → kelurahans (hierarchical CASCADE)
alamat_santris references all four wilayah levels
```
- All foreign keys use CASCADE delete. Deleting a province cascades through kabupaten → kecamatan → kelurahan → alamat_santris.
- **Risk note:** The wilayah hierarchy uses CASCADE — if a `provinsi` record is deleted, all associated `alamat_santris` records are silently deleted. This is appropriate for reference data management but should be documented.

### 5.5. User RBAC Relationships
```
users (*) ──── (*) permissions  (via model_has_permissions)
users (*) ──── (*) roles        (via model_has_roles)
roles (*) ──── (*) permissions  (via role_has_permissions)
```
- Standard Spatie Permission v6 schema. All pivot tables use CASCADE delete from the parent record.
- **Security note:** Spatie Permission v6 correctly removes user-role and user-permission records when a user or role is deleted.

---

## 6. Disposition Decision: Keep / Merge / Remove

### 6.1. Decision Summary

| Decision | Count | Migrations |
| :--- | :---: | :--- |
| **KEEP as-is** (source of truth, no alteration exists) | 13 | #05, #06, #07, #09, #11, #12, #18, #19, #20, #21, #22, #25 (absorbed into M14), #11 |
| **ABSORB** (merge alteration into parent CREATE) | 3 | #24 → into #10, #26 → into #08/#10/#23, #27 → into #04 |
| **CONSOLIDATE** (merge multiple CREATE migrations into one) | 7 | #01+#02 → M01, #03+#13 → M02, #04+#27 → M03, #14+#15+#16+#17 → M12, #20+#21+#22+#25 → M14 |

### 6.2. Migrations to Remove (Replaced by Consolidated Versions)

These 6 migration files will be **replaced** (i.e., deleted after their content is absorbed into the consolidated migration set):

| Migration File | Reason for Removal |
| :--- | :--- |
| `2014_10_12_100000_create_password_reset_tokens_table.php` | Absorbed into `0001_01_01_000000_create_users_table.php` |
| `2019_08_19_000000_create_failed_jobs_table.php` | Absorbed into `0001_01_01_000002_create_jobs_table.php` |
| `2023_10_30_072010_create_jobs_table.php` | Absorbed into `0001_01_01_000002_create_jobs_table.php` |
| `2023_10_31_083810_create_kabupatens_table.php` | Absorbed into `2023_10_31_083759_create_wilayah_tables.php` |
| `2023_10_31_083824_create_kecamatans_table.php` | Absorbed into `2023_10_31_083759_create_wilayah_tables.php` |
| `2023_10_31_083829_create_kelurahans_table.php` | Absorbed into `2023_10_31_083759_create_wilayah_tables.php` |
| `2024_05_20_231650_add_column_to_transaksi_tabungans.php` | Absorbed into `create_transaksi_tabungans_table.php` |
| `2024_05_21_000000_create_activity_logs_table.php` | Absorbed into consolidated auxiliary table migration |
| `2024_05_22_000000_harden_financial_database_constraints.php` | Absorbed into financial CREATE migrations |
| `2026_09_19_170022_update_personal_access_tokens_for_sanctum_v4.php` | Absorbed into `create_personal_access_tokens_table.php` |
| `2019_12_14_000001_create_personal_access_tokens_table.php` | Replaced by modernized version with Sanctum v4 schema |
| `2014_10_12_000000_create_users_table.php` | Replaced by `0001_01_01_000000_create_users_table.php` |

---

## 7. Target Migration Architecture (14 Files)

The following represents the complete post-consolidation migration set:

| New # | New Filename | Produces Tables | Source Migrations Absorbed |
| :---: | :--- | :--- | :--- |
| **M01** | `0001_01_01_000000_create_users_table.php` | `users`, `password_reset_tokens` | #01, #02 |
| **M02** | `0001_01_01_000002_create_jobs_table.php` | `jobs`, `failed_jobs` | #03, #13 |
| **M03** | `0001_01_01_000003_create_personal_access_tokens_table.php` | `personal_access_tokens` (Sanctum v4) | #04, #27 |
| **M04** | `2023_08_29_075200_create_kelas_table.php` | `kelas` | #05 — unchanged |
| **M05** | `2023_08_29_075221_create_kamars_table.php` | `kamars` | #06 — unchanged |
| **M06** | `2023_08_29_075223_create_santris_table.php` | `santris` | #07 — unchanged (remove dead imports) |
| **M07** | `2023_08_29_075224_create_tabungans_table.php` | `tabungans` (atomic final schema) | #08 + partial #26 |
| **M08** | `2023_08_29_075224_create_wali_santris_table.php` | `wali_santris` | #09 — unchanged |
| **M09** | `2023_08_29_075235_create_transaksi_tabungans_table.php` | `transaksi_tabungans` (atomic final schema) | #10 + #24 + partial #26 |
| **M09b** | `2023_08_29_075600_create_transfers_table.php` | `transfers` (atomic final schema) | #23 + partial #26 |
| **M10** | `2023_09_12_212807_create_permission_tables.php` | 5 Spatie RBAC tables | #11 — unchanged |
| **M11** | `2023_09_18_031115_create_wali_kelas_table.php` | `wali_kelas` | #12 — unchanged (remove dead TahunAkademik import) |
| **M12** | `2023_10_31_083759_create_wilayah_tables.php` | `provinsis`, `kabupatens`, `kecamatans`, `kelurahans` | #14, #15, #16, #17 |
| **M13** | `2024_02_06_133257_create_alamat_santris_table.php` | `alamat_santris` | #18 — unchanged |
| **M14** | `2024_05_19_001410_create_institutional_auxiliary_tables.php` | `settings`, `kelas_santris`, `kamar_santris`, `whatsapp_messages`, `activity_logs` | #19, #20, #21, #22, #25 |

**Total: 14 migration files producing 29 application tables** (unchanged schema).

> [!NOTE]
> The `transfers` table is listed separately as M09b because it has its own distinct business domain relationship from `transaksi_tabungans`, even though both involve `santris`. Keeping them as separate files improves readability and allows independent rollback.

---

## 8. What Does NOT Change

This section explicitly documents runtime behavior that is fully preserved:

| Concern | Verification |
| :--- | :--- |
| **All 29 table names** | Identical — no table is renamed |
| **All column names, types, nullability** | Identical to live DDL extracted via `SHOW CREATE TABLE` |
| **All primary keys** | All use bigint unsigned auto-increment `id` |
| **All UNIQUE constraints** | `santris.no_induk`, `tabungans.santri_id`, `users.email`, RBAC composites |
| **All FOREIGN KEY constraints** | Names may be regenerated by Blueprint but referential integrity is identical |
| **All CHECK constraints** | `chk_tabungans_saldo`, `chk_transfers_jumlah`, `chk_transfers_parties` |
| **All composite indexes** | `idx_transaksi_santri_tgl_jenis`, `idx_transfers_pengirim_created`, `idx_transfers_penerima_created` |
| **All ON DELETE behaviors** | Financial tables → RESTRICT; all others → CASCADE (unchanged) |
| **Application Models** | No model files will be modified |
| **Controllers / Routes** | No application code will be modified |
| **Seeder behavior** | No seeders will be modified |
| **Test suite** | 107 tests must continue to pass after consolidation |

---

## 9. Compatibility Notes

### 9.1. SQLite CI Compatibility
The `harden_financial_database_constraints` migration (#26) uses raw SQL `DB::statement('ALTER TABLE ... ADD CONSTRAINT ... CHECK ...')` which is guarded by a `if (in_array($driver, ['mysql', 'mariadb']))` check. In the consolidated migrations, CHECK constraints can be declared directly via Laravel's Blueprint `->check()` method which Laravel automatically skips on SQLite CI runners — eliminating the driver check guard.

### 9.2. The `harden_financial_database_constraints` Pre-Flight Check
Migration #26 includes a runtime data integrity check:
```php
if ($duplicateCount > 0) {
    throw new RuntimeException("Pre-migration check failed...");
}
```
This check is **only relevant for in-place upgrades** of existing databases. After consolidation (where the table is created fresh), this check is dead code and will be removed.

### 9.3. `tanggal_transaksi` Default Value
Migration #10 uses `->default(date('Y-m-d'))` — a **PHP runtime evaluated default** — not a database-level default. The live schema shows `DEFAULT '2026-09-19'` (the date of the last `migrate:fresh`). After consolidation, this should be changed to `->default(DB::raw('(CURDATE())'))` for a true database-level default, or kept as the PHP runtime default with documentation that the value updates on each migration run.

---

## 10. Verification Plan

Before executing Phase 5.8.6B (implementation), the following verification protocol must be followed:

1. **Create dedicated branch:** `chore/migration-consolidation`
2. **Backup current migration files** to `docs/archive/migrations-pre-consolidation/`
3. **For each table, record a DDL fingerprint** using `SHOW CREATE TABLE` before and after consolidation
4. **Execute `php artisan migrate:fresh`** against the consolidated migration set
5. **Compare DDL fingerprints** for all 29 tables (column types, nullability, indexes, FK names, CHECK constraints)
6. **Run complete test suite:** `php artisan test` — must produce 107 passed (531 assertions)
7. **Run Pint:** `composer run lint:check` — must pass
8. **Run security audit:** `composer audit` — must pass

---

## 11. Files Referenced

| File | Role |
| :--- | :--- |
| [`database/migrations/`](file:///home/adminfid/Documents/Projects/sistem-Ponpes-Fatimah-Az-Zahra/database/migrations/) | All 27 current migration files |
| [`docs/reports/domain-assessments/Laravel12_Migration_Cleanup_Assessment.md`](file:///home/adminfid/Documents/Projects/sistem-Ponpes-Fatimah-Az-Zahra/docs/reports/domain-assessments/Laravel12_Migration_Cleanup_Assessment.md) | Phase 5.8.4 initial migration audit |
| [`docs/reports/domain-assessments/Laravel12_Database_Refinement_Assessment.md`](file:///home/adminfid/Documents/Projects/sistem-Ponpes-Fatimah-Az-Zahra/docs/reports/domain-assessments/Laravel12_Database_Refinement_Assessment.md) | Phase 5.8.4 master assessment |
