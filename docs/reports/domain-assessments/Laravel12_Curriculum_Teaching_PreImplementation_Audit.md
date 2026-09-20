# Backup Audit Summary: Pre-Implementation State for Phase 5.8.7C-7

**Document**: `Laravel12_Curriculum_Teaching_PreImplementation_Audit.md`  
**Phase**: 5.8.7C-7 — Laravel 12 Academic Curriculum & Teaching Domain Foundation  
**Timestamp**: 2026-09-20T18:40:00+07:00  
**Baseline Git Commit**: `6ac1ed05` (`phase-5.8.7C-6-completed`)  
**PHP Version**: 8.4.16  
**Laravel Version**: 12.69.2  
**Test Baseline**: 161 tests passed, 705 assertions passed  

---

## 1. Executive Summary

Before beginning implementation of Phase 5.8.7C-7, this audit captures the exact state of all existing application files, schemas, and components that will be affected by introducing the Academic Curriculum & Teaching domain (`wali_kelas_assignments`, `mapels`, `teaching_assignments`).

---

## 2. Inventory of Affected Existing Files

### 2.1. Migration: `database/migrations/2023_09_18_031115_create_wali_kelas_table.php`
- **Current Role**: Defined an unused table `wali_kelas` with erroneous schema (`santri_id` instead of teacher `user_id`, no temporal/academic year link, cascading deletes).
- **Lines of Code**: 33 lines.
- **Current Content**:
  - `Schema::create('wali_kelas')`: `id`, `kelas_id` (cascade), `santri_id` (cascade), timestamps.
- **Planned Modification**:
  - Migration modified in-place and sequenced after `academic_years` creation (`2024_06_01_000002_create_wali_kelas_assignments_table.php`).
  - Table renamed to `wali_kelas_assignments`.
  - Foreign keys: `kelas_id` (restrictOnDelete), `user_id` (restrictOnDelete), `academic_year_id` (restrictOnDelete).
  - Nullable `notes` column.
  - Unique composite index `(kelas_id, academic_year_id)`.

### 2.2. Model: `app/Models/WaliKelas.php`
- **Current Role**: Dead Eloquent model with faulty boot logging (`$wali_kelas->santri->user->name`). Zero consumers in application controllers or routes.
- **Lines of Code**: 45 lines.
- **Planned Modification**:
  - Replaced by clean model `app/Models/WaliKelasAssignment.php`.
  - Removes faulty boot logic and santri dependency.
  - Exposes typed relationships: `belongsTo(User::class)`, `belongsTo(Kelas::class)`, `belongsTo(AcademicYear::class)`.

### 2.3. Model: `app/Models/User.php`
- **Current Role**: Authentication and user identity model.
- **Current Relationships**: `santri()` (`hasOne`).
- **Planned Modification**:
  - Add `wali_kelas_assignments()` (`hasMany(WaliKelasAssignment::class)`).
  - Add `teaching_assignments()` (`hasMany(TeachingAssignment::class)`).

### 2.4. Model: `app/Models/Kelas.php`
- **Current Role**: Academic class entity.
- **Current Relationships**: `academic_enrollments()` (`hasMany`).
- **Planned Modification**:
  - Add `wali_kelas_assignments()` (`hasMany(WaliKelasAssignment::class)`).
  - Add `mapels()` (`hasMany(Mapel::class)`).
  - Add `teaching_assignments()` (`hasMany(TeachingAssignment::class)`).

### 2.5. Model: `app/Models/AcademicYear.php`
- **Current Role**: Academic calendar year and semester management.
- **Current Relationships**: `academic_enrollments()` (`hasMany`).
- **Planned Modification**:
  - Add `wali_kelas_assignments()` (`hasMany(WaliKelasAssignment::class)`).
  - Add `teaching_assignments()` (`hasMany(TeachingAssignment::class)`).

### 2.6. Controller: `app/Http/Controllers/Kelas/KelasController.php`
- **Current Role**: CRUD for Kelas. `destroy()` currently checks only `academic_enrollments()->exists()`.
- **Planned Modification**:
  - Guard `destroy()` against classes having active `mapels`, `wali_kelas_assignments`, or `teaching_assignments`.

### 2.7. Config: `config/permission.php`
- **Current Role**: Spatie permissions config. Already contains `'mapel'` permissions.
- **Planned Modification**:
  - Ensure `'wali_kelas'` and `'pengajaran'` permissions are defined for `admin` and `pengurus` roles.

### 2.8. Routes: `routes/web.php`
- **Current Role**: Application web routing.
- **Planned Modification**:
  - Register resource routes under `role:Administrator|Pengurus`:
    - `wali-kelas-assignment.*`
    - `mapel.*`
    - `teaching-assignment.*`

### 2.9. Navigation: `resources/views/components/navbar.blade.php`
- **Current Role**: Sidebar / navigation menu.
- **Planned Modification**:
  - Add navigation items under the Academic module:
    - Wali Kelas (`wali-kelas-assignment.index`)
    - Mata Pelajaran (`mapel.index`)
    - Penugasan Mengajar (`teaching-assignment.index`)

---

## 3. Schema Comparison

| Domain Entity | Old Implementation | New Hardened Implementation |
|---|---|---|
| **Wali Kelas** | `wali_kelas` (`id`, `kelas_id`, `santri_id`, CASCADE) | `wali_kelas_assignments` (`id`, `kelas_id`, `user_id`, `academic_year_id`, `notes`, RESTRICT, `UNIQUE(kelas_id, academic_year_id)`) |
| **Mapel (Subject)** | None (only permissions existed) | `mapels` (`id`, `kelas_id`, `code` UNIQUE, `name`, `description`, `is_active`, RESTRICT) |
| **Teaching Assignment** | None | `teaching_assignments` (`id`, `kelas_id`, `mapel_id`, `user_id`, `academic_year_id`, `status`, `notes`, RESTRICT, `UNIQUE(kelas_id, mapel_id, academic_year_id)`) |

---

## 4. Preservation & Integrity Guarantees

1. **Zero LMS Feature Creep**: No attendance, grading, exams, question banks, or student-level subject enrollments are introduced.
2. **Historical Data Protection**: All foreign key constraints use `restrictOnDelete` so that no historical assignments or curricula can be silently destroyed.
3. **Architecture Conformity**: Thin controllers delegating domain mutations to dedicated service classes (`WaliKelasAssignmentService`, `TeachingAssignmentService`).
4. **UI Design System Reuse**: UI components strictly leverage existing Blade components (`<x-card-toolbar>`, `<x-status-badge>`, `<x-delete-modal>`).
