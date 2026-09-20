# Phase 5.8.7C-3 — Laravel 12 Domain Rationalization Audit
## Core Product Alignment Assessment

**Application:** Sistem Informasi Pondok Pesantren Fatimah Az-Zahra  
**Branch:** `develop`  
**Stack:** Laravel 12.69.2 · PHP 8.4.16 · MariaDB 10.4.32  
**Audit Date:** 2026-09-20  
**Phase Author:** Senior Laravel Architect — Domain Rationalization  

---

## Executive Summary

This report delivers a comprehensive domain rationalization audit for the Pesantren Information System. The application currently operates as a **monolithic Laravel application** with **6 distinct domain concerns** sharing flat namespace structure. Based on product vision alignment (Santri lifecycle management, Academic structure, Financial operations), this audit identifies which domains are **core**, which are **peripheral**, and which carry **excessive maintenance cost** relative to their value.

**Key Findings:**
- 3 domains are **Core** and need deepening: Santri, Academic, Financial
- 2 domains are **Peripheral/High-Cost**: Google Sheets Sync, WhatsApp Notification
- 1 domain is **Auxiliary with refactor opportunity**: Geography (Wilayah)
- 1 domain needs **extraction**: System/Admin (Activity Log, Settings, Roles)
- The application **lacks** all future vision features: LMS, Question Bank, Assessment, Teacher Productivity

---

## 1. Feature Inventory

### 1.1 All Features Mapped

| # | Feature | Entry Point | Domain Classification |
|---|---------|-------------|----------------------|
| 1 | Authentication (Login/Logout) | `AuthController` | System/Auth |
| 2 | Dashboard (Stats Overview) | `DashboardController` | System/Admin |
| 3 | Santri CRUD | `SantriController` | **Core: Santri** |
| 4 | Santri Import (Excel) | `SantriController@import` + `SantriImport` | **Core: Santri** |
| 5 | Santri Export (Excel) | `SantriController@export` + `SantriExport` | **Core: Santri** |
| 6 | Santri KTS Print (Kartu Tanda Santri) | `SantriController@print_kts` | **Core: Santri** |
| 7 | Kamar (Dormitory Room) CRUD | `KamarController` | **Core: Santri** |
| 8 | Kamar Download (Template) | `KamarController@download` | **Core: Santri** |
| 9 | Kelas (Classroom) CRUD | `KelasController` | **Core: Academic** |
| 10 | Wali Kelas (Class Teacher) | `WaliKelas` model | **Core: Academic** |
| 11 | Tabungan (Savings Account) CRUD | `SaldoDebitController` | **Core: Financial** |
| 12 | Tabungan Export | `SaldoDebitController@export` | **Core: Financial** |
| 13 | Transaksi Tabungan (Debit/Credit) | `TransaksiController` | **Core: Financial** |
| 14 | Transfer Antar Santri | `TransferController` | **Core: Financial** |
| 15 | WhatsApp Notification (Setoran/Penarikan) | `TransaksiTabunganObserver` | Peripheral: WhatsApp |
| 16 | Google Sheets Sync (Santri Aktif/Alumni) | `SinkronController` + `Sinkron` helper | Peripheral: Integration |
| 17 | API Sync (Kelas + Santri) | `synchronizationController` | Peripheral: Integration |
| 18 | Geography (Provinsi/Kabupaten/Kecamatan/Kelurahan) | `AlamatController` + DB tables | Auxiliary: Geography |
| 19 | Alamat Santri (Student Address) | `AlamatSantri` model | **Core: Santri** |
| 20 | User Management | `UsersController` | System/Admin |
| 21 | Role Management | `RoleController` | System/Admin |
| 22 | Activity Log (Riwayat) | `RiwayatController` + `ActivityLog` | System/Admin |
| 23 | System Settings | `SettingController` | System/Admin |
| 24 | Profile Management | `ProfilController` | System/Auth |
| 25 | Wali Santri (Guardian Data) | `WaliSantri` model | **Core: Santri** |

### 1.2 Planned/Commented Features (from `config/modules.php`)

```php
// 'rapor' => ['rapor santri', ''],   // COMMENTED OUT — not implemented
// 'surat' => ['surat izin', ''],      // COMMENTED OUT — not implemented
```

These two modules are **planned but entirely absent** from the codebase — no controllers, no models, no views, no migrations.

---

## 2. Domain Classification Matrix

### 2.1 Classification Legend

| Symbol | Meaning |
|--------|---------|
| CORE | Essential to product vision; must be maintained and deepened |
| PERIPHERAL | Adds value but creates external coupling risk |
| AUXILIARY | Supports core but low ownership; refactor opportunity |
| TECHNICAL DEBT | Provides marginal value; high maintenance cost |

---

### 2.2 Domain Classification

| Domain | Classification | Justification |
|--------|---------------|---------------|
| **Santri (Student) Management** | CORE | Central entity — all other domains orbit around it |
| **Academic (Kelas/Kamar/WaliKelas)** | CORE | Foundational academic structure |
| **Financial (Tabungan/Transaksi/Transfer)** | CORE | Active operational feature with transactional integrity |
| **Auth & Profile** | CORE | Essential security foundation |
| **System Admin (Users/Roles/Settings/Logs)** | CORE | Required for operation; but needs service extraction |
| **Geography (Wilayah)** | AUXILIARY | Supports address lookup; high data weight, low ownership |
| **WhatsApp Notification** | PERIPHERAL | Dependent on third-party Labelin API; not critical to data integrity |
| **Google Sheets Sync** | PERIPHERAL | Synchronizes to external spreadsheet; not critical path; coupling is a liability |
| **API Sync Endpoints** | PERIPHERAL | Served via Sanctum API; consumer unknown; high maintenance surface |
| **Rapor / Surat Izin** | TECHNICAL DEBT | Mentioned in config; no implementation; abandoned roadmap |

---

## 3. Dependency Analysis

### 3.1 Santri Domain Dependency Map

```
Santri (Central Entity)
├── User (1:1 — login account)
├── WaliSantri (1:1 — guardian)
├── AlamatSantri (1:1 — address)
│   ├── Provinsi
│   ├── Kabupaten
│   ├── Kecamatan
│   └── Kelurahan
├── KamarSantri (pivot — room assignment)
│   └── Kamar (room)
├── KelasSantri (pivot — class assignment)
│   └── Kelas (classroom)
│       └── WaliKelas (class teacher)
├── Tabungan (1:1 — savings account)
│   └── TransaksiTabungan (1:N — transactions)
├── Transfer (1:N — pengirim / penerima)
└── ActivityLog (via LogActivity trait)
```

**Assessment:** The Santri model is both well-designed and appropriately central. However, it currently carries **room counter management logic** directly in its model (via `pendingKamarId`, `originalKamarId` attributes and `isKamarDirty()`). This is observer-level domain logic embedded in the model — a boundary violation.

### 3.2 External Dependency Map

```
External Integrations
├── Google Sheets API
│   ├── revolution/laravel-google-sheets ^7.0
│   ├── Requires: GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET
│   ├── Used by: SinkronController, Sinkron helper
│   └── Risk: No fallback on API failure; no retry; no queue
│
└── WhatsApp API (Labelin)
    ├── Custom HTTP call via Http::get()
    ├── Hardcoded URI: connect.labelin.co/send-message
    ├── Used by: TransaksiTabunganObserver
    └── Risk: Synchronous HTTP in observer; no timeout; no retry; no queue
```

### 3.3 Third-Party Package Dependency Classification

| Package | Usage | Classification | Risk |
|---------|-------|---------------|------|
| `laravel/sanctum ^4.0` | API token auth | KEEP | Low |
| `spatie/laravel-permission ^6.0` | RBAC roles | KEEP | Low |
| `yajra/laravel-datatables ^12.0` | Server-side tables | REVIEW | Medium — UI coupling |
| `maatwebsite/excel ^3.1` | Import/Export | KEEP | Low |
| `revolution/laravel-google-sheets ^7.0` | Google Sheets sync | ISOLATE | High — external coupling |
| `pharaonic/laravel-hijri ^1.0` | Hijri date conversion | REVIEW | Medium — specialty package |
| `intervention/image-laravel ^1.5` | Image processing | KEEP | Low |
| `milon/barcode ^12.0` | Barcode generation | REVIEW | Medium — appears underused |
| `spatie/laravel-flash ^1.9` | Flash messages | REVIEW | Low — custom Toastr wrapping |
| `guzzlehttp/guzzle ^7.2` | HTTP client | KEEP | Low |

---

## 4. Product Vision Gap Analysis

### 4.1 Current vs. Target Product Vision

| Capability Area | Current State | Target Vision | Gap |
|-----------------|--------------|---------------|-----|
| Santri Registration & Lifecycle | Implemented | Core Feature | None — mature |
| Academic Class Management | Implemented (basic) | Core Feature | Needs deepening |
| Dormitory Room Management | Implemented | Core Feature | None — stable |
| Financial (Savings/Transactions) | Implemented | Core Feature | Minor: service layer missing |
| Guardian (Wali Santri) Data | Implemented (minimal) | Core Feature | Minimal fields; needs expansion |
| Activity Audit Log | Implemented | Core Feature | Single flat table; needs structure |
| **Learning Management (LMS)** | Not implemented | **Target Vision** | **Full build required** |
| **Teaching Material Repository** | Not implemented | **Target Vision** | **Full build required** |
| **Question Bank** | Not implemented | **Target Vision** | **Full build required** |
| **Assessment / Exam** | Not implemented | **Target Vision** | **Full build required** |
| **Teacher Productivity Tools** | Not implemented | **Target Vision** | **Full build required** |
| **Rapor (Report Card)** | Commented out in config | **Target Vision** | **Config stub only** |
| **Surat Izin (Permission Letter)** | Commented out in config | Potentially useful | **Config stub only** |
| Student Card Print (KTS) | Implemented | Support Feature | None |
| WhatsApp Notifications | Implemented | Non-core | Peripheral — not in vision |
| Google Sheets Export | Implemented | Non-core | Peripheral — not in vision |

### 4.2 Critical Gap Assessment

> **The application is fundamentally a student registry and basic financial system. None of the stated product vision pillars (LMS, Question Bank, Assessment, Teacher Productivity) are implemented or even scaffolded.**

This represents a **major product-architecture gap**: the current codebase is built to manage student administration, while the target product vision is a **Pesantren Learning Management System**.

---

## 5. Architecture Assessment by Domain

### 5.1 Santri Domain — Architecture Quality

**Strengths:**
- Proper Eloquent relationships (`hasOne`, `hasMany`, `hasOneThrough`)
- Observer-based lifecycle hooks (Phase 5.8.7C-2B1 completed)
- Activity logging via `LogActivity` trait
- Import/Export via `maatwebsite/excel`

**Weaknesses:**
- Model contains virtual attributes (`pendingKamarId`, `originalKamarId`) — state machine fragments that belong in a dedicated service
- `SantriController@store` and `@update` are **fat** — contain formatting logic, Hijri conversion, image processing, cascade relation creation
- Duplicate logic between `SantriController` and `synchronizationController` (both create Users and Santri in same pattern)
- No soft deletes — physical deletion cascades and loses history

**Recommended Extraction (Future Issues):**
- `SantriLifecycleService` — encapsulate store/update business logic
- `SantriRegistrationService` — deduplicate registration logic between web and API

---

### 5.2 Academic Domain — Architecture Quality

**Strengths:**
- `Kelas` and `Kamar` are properly separated entities
- Pivot tables `kelas_santris` and `kamar_santris` are correct M:M bridge tables
- `WaliKelas` model exists

**Weaknesses:**
- `WaliKelas` model has no associated controller or routes — **dead domain entity**
- No academic year/period concept — assignment is permanent, no `tahun_ajaran` boundary
- Room counter (`jumlah_santri` column on `kamars`) is a denormalized counter that can drift

**Recommended Extraction (Future Issues):**
- `AcademicPeriod` model — introduce concept of academic year
- `WaliKelas` route activation — or remove dead model

---

### 5.3 Financial Domain — Architecture Quality

**Strengths:**
- `Tabungan` → `TransaksiTabungan` relationship is sound
- `CHECK CONSTRAINT` on `saldo >= 0` at DB level is excellent
- `TransferController` uses deterministic lock ordering to prevent deadlock
- DB::transaction wrapping on all mutations
- Observer-based activity logging (Phase 5.8.7C-2B2A)

**Weaknesses:**
- `SaldoDebitController` is misnamed — manages savings accounts (debit AND credit)
- `TransferController@transfer` is a **public method on a controller** — should be a service
- Balance ledger has no double-entry accounting model
- WhatsApp notification call is **synchronous inside observer** — any API failure blocks transaction

**Recommended Extraction (Future Issues):**
- `FinancialTransactionService` — extract transfer and deposit logic
- Async WhatsApp via Laravel Queues

---

### 5.4 WhatsApp Domain — Architecture Quality

**Strengths:**
- Feature toggle via `Setting::whatsapp_feature` flag
- Message templates configurable via `WhatsappMessage` model
- API key stored in DB

**Weaknesses:**
- **HTTP call is synchronous inside an Eloquent observer** — blocks the transaction cycle
- No retry mechanism — if Labelin API is down, transaction notification is silently lost
- API URI is **hardcoded** in the observer: `'https://connect.labelin.co/send-message'`
- No fallback or circuit breaker
- Observer loads `Setting` and `WhatsappMessage` on **every transaction created** — N+1 pattern on batch operations

**Risk Level: HIGH** — synchronous third-party call in Eloquent observer is a production stability risk

**Recommended Architecture:**
```
TransaksiTabunganObserver::created()
    -> dispatch(SendWhatsappNotification::class)  [queued job]
        -> WhatsappNotificationService::send()
```

---

### 5.5 Google Sheets Sync Domain — Architecture Quality

**Strengths:**
- Isolated in `SinkronController` and `Sinkron` helper
- Protected by `role:Administrator|Pengurus` middleware
- UI toggle for sync modules

**Weaknesses:**
- **Duplicate code**: `SinkronController::sync()` and `Sinkron::santri()` implement the **identical algorithm** with no shared abstraction
- Sync uses raw column names from `join()` queries — **fragile to schema changes**

**Critical Schema Bug:**
```php
// Sinkron::alumni() references columns that DO NOT EXIST in current schema:
->select('no_induk', 'name', 'provinsi', 'kabupaten', 'kecamatan', 'desa', 'dusun', ...)
// 'provinsi', 'kabupaten', etc are in alamat_santris table, not santris
```

This code is currently **broken by design** — the `Sinkron::alumni()` method will throw a SQL error at runtime because the `santris` table no longer has these address columns (address was migrated to `alamat_santris` in the 2024 migration).

**Risk Level: CRITICAL** — dead code with silent schema mismatch; will fail in production

---

### 5.6 Geography (Wilayah) Domain — Architecture Quality

**Dual Implementation Problem:**
```
Path 1 (Modern): DB tables -> AlamatController -> AJAX
Path 2 (Legacy): public/wilayah/*.json -> Helper::prov/kab/kec/kel()
```

Both paths exist simultaneously. The JSON-based helpers in `Helper.php` are **legacy dead code** — they iterate all records to find a match (O(n) linear scan on file).

**Scale Problem:**
- Indonesian wilayah = 83,000+ village records
- Current migration creates tables but no seeder exists for wilayah data
- Source of truth for wilayah data is unclear

**Recommended Architecture:**
- Remove `Helper::prov()`, `kab()`, `kec()`, `kel()` — dead legacy code
- Evaluate: external API vs. `laravolt/indonesia` package vs. custom seeder
- Resolve wilayah data source before production

---

### 5.7 System/Admin Domain — Architecture Quality

**Strengths:**
- `ActivityLog` table for audit trail
- `LogActivity` trait for consistent logging
- Spatie RBAC with 4 roles: Administrator, Pengurus, Keuangan, Santri

**Weaknesses:**
- `ActivityLog::activity` is a **free-text string** — no structured fields for querying by action, resource, or user
- Logs lack: `resource_type`, `resource_id`, `action_verb` — making them unsearchable at scale
- `SettingController@store` and `@update` have **identical** file upload logic blocks (200+ LOC duplication)
- `WaliSantri::boot()` still uses inline `creating/updating/deleting` closures — not migrated to Observer pattern

**Recommended Extraction (Future Issues):**
- Structured `ActivityLog` schema: add `action`, `resource_type`, `resource_id` columns
- Extract file upload logic to `SettingFileUploadService`
- Migrate `WaliSantri` to Observer

---

## 6. Removed/Dead Features Inventory

| Feature | Evidence | Reason |
|---------|----------|--------|
| `Rapor Santri` | Commented out in `config/modules.php` | Never implemented |
| `Surat Izin` | Commented out in `config/modules.php` | Never implemented |
| `Sinkron::alumni()` | In `Helpers/Sinkron.php` | Broken — references non-existent columns |
| `Helper::prov/kab/kec/kel()` | In `Helpers/Helper.php` | Superseded by DB tables; O(n) JSON scan |
| `WaliKelas` controller/routes | `WaliKelas` model exists, no routes/controller | Dead model |
| `get_tabungan`, `store_tabungan` | Absent from API routes | Removed per PR #13 decision |

---

## 7. Migration Planning: Toward Product Vision

### 7.1 Phase Roadmap (Recommended)

```
Phase 5.8.7C-3 (Current)
└── Domain Rationalization Audit [THIS DOCUMENT]

Phase 5.8.7C-4 — Domain Service Layer Extraction
├── Extract SantriLifecycleService
├── Extract FinancialTransactionService
├── Move WhatsApp to queued jobs
└── Remove Helper::prov/kab/kec/kel() dead code

Phase 5.8.7C-5 — Integration Hardening
├── Fix Sinkron::alumni() schema bug
├── Deduplicate SinkronController vs Sinkron helper
├── Add retry/queue for Google Sheets sync
└── Resolve dual wilayah implementation

Phase 5.8.7D — Academic Domain Deepening
├── Introduce AcademicPeriod model (tahun ajaran)
├── Activate or remove WaliKelas
├── Implement Rapor scaffold
└── Implement Surat Izin scaffold

Phase 5.8.7E — LMS Foundation (Future Vision)
├── TeachingMaterial domain
├── QuestionBank domain
├── Assessment/Exam domain
└── TeacherProductivity domain
```

### 7.2 Issue Creation Recommendations

| Priority | Issue Title | Domain | Type |
|----------|-------------|--------|------|
| P1 — Critical | `bug: fix Sinkron::alumni() broken column references` | Integration | Bug |
| P1 — Critical | `refactor: move WhatsApp notification to queued job` | WhatsApp | Reliability |
| P2 — High | `refactor: extract SantriLifecycleService from fat controller` | Santri | Refactor |
| P2 — High | `refactor: extract FinancialTransactionService from TransferController` | Financial | Refactor |
| P2 — High | `cleanup: remove legacy Helper::prov/kab/kec/kel() JSON helpers` | Geography | Cleanup |
| P2 — High | `refactor: deduplicate SinkronController and Sinkron helper` | Integration | Refactor |
| P3 — Medium | `refactor: structured ActivityLog schema (action, resource_type, resource_id)` | System | Refactor |
| P3 — Medium | `refactor: migrate WaliSantri boot() closures to Observer` | Santri | Refactor |
| P3 — Medium | `refactor: extract SettingController file upload to dedicated service` | System | Refactor |
| P3 — Medium | `feat: introduce AcademicPeriod model for academic year management` | Academic | Feature |
| P3 — Medium | `chore: resolve dual wilayah implementation (DB vs JSON)` | Geography | Cleanup |
| P4 — Low | `chore: activate or remove dead WaliKelas model` | Academic | Cleanup |
| P4 — Low | `feat: scaffold Rapor Santri domain` | Academic | Feature |
| P4 — Low | `feat: scaffold Surat Izin domain` | Admin | Feature |

---

## 8. Architecture Modernization Readiness Score

| Dimension | Current State | Score (1–5) | Notes |
|-----------|--------------|-------------|-------|
| Test Coverage | 138 tests, 647 assertions | 4/5 | Good foundation |
| Observer Architecture | Fully migrated (Phase C-2) | 5/5 | Excellent |
| Service Layer | 1 service (`SantriRoomCounterService`) | 2/5 | Needs expansion |
| API Security | Sanctum + role middleware | 4/5 | Well implemented |
| Code Duplication | Multiple duplicate blocks | 2/5 | Needs extraction |
| External Coupling | Sync HTTP in observers | 2/5 | High risk |
| Schema Integrity | CHECK constraints, FK constraints | 4/5 | Good |
| Domain Separation | Flat namespace, some coupling | 2/5 | Needs structuring |
| Dead Code | Broken helpers, dead models | 2/5 | Needs cleanup |
| Product Vision Alignment | 0/5 LMS features built | 1/5 | Major gap |

**Overall Readiness Score: 28/50 — Partial**

---

## 9. Constraints & Audit Boundaries

> **NOTE:** This document is a read-only audit. No application files were modified during this phase.
>
> All findings are based on static analysis of the current `develop` branch at commit `f50724e2`.

> **WARNING:** The `Sinkron::alumni()` method (Section 5.5) is a live production bug — alumni synchronization to Google Sheets will fail at runtime with a SQL error referencing non-existent columns. This must be addressed before this feature is triggered in production.

---

## 10. Sign-Off

| Attribute | Value |
|-----------|-------|
| Phase | 5.8.7C-3 |
| Type | Audit — No code modifications |
| Report Author | Senior Laravel Architect |
| Base Commit | `f50724e2` |
| Branch | `develop` |
| Test Baseline | 138 tests / 647 assertions |
| Next Phase | 5.8.7C-4 — Domain Service Layer Extraction |
