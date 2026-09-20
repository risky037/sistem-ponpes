# Release Notes — Phase 5.8.7C-7: Laravel 12 Academic Curriculum & Teaching Domain Foundation

**Release Tag**: `phase-5.8.7C-7-completed`  
**Date**: 2026-09-20  
**Branch**: `develop`  
**System Baseline**: Laravel 12.69.2, PHP 8.4.16, MariaDB 10.4.32, Bootstrap 5.1.3, Vite 4.4.9  

---

## 1. Release Objectives

Phase 5.8.7C-7 establishes the foundational curriculum ownership and teaching assignment architecture for the **Sistem Informasi Pondok Pesantren Fatimah Az-Zahra**.

Primary objectives accomplished:
1. **Wali Kelas Domain Replacement**: Replaced the legacy, broken, and unused `wali_kelas` domain (`santri_id` instead of teacher `user_id`, lack of temporal dimension, cascading deletes) with a hardened `wali_kelas_assignments` table.
2. **Class-Scoped Curriculum (Mapel)**: Introduced `Mapel` scoped to `Kelas` with unique subject codes, class-level uniqueness, and active status filtering.
3. **Teaching Assignments (`teaching_assignments`)**: Built a temporal assignment model linking teachers, subjects, classes, and academic years with a composite unique constraint `(kelas_id, mapel_id, academic_year_id)` and a soft-deactivation lifecycle.
4. **Service Layer Isolation**: Encapsulated all business invariants in dedicated service classes (`WaliKelasAssignmentService`, `TeachingAssignmentService`), keeping controllers thin and maintainable.
5. **Deletion Protection Guards**: Hardened `KelasController`, `AcademicYearController`, and `MapelController` against cascading data loss using model relationship guards and database `restrictOnDelete` foreign keys.
6. **Administrative UI Modernization**: Implemented complete DataTables management interfaces for Mata Pelajaran, Wali Kelas, and Penugasan Mengajar using the established Pesantren Green design system and reusable Blade components.
7. **Strict LMS Boundary**: Preserved clear separation of concerns by purposefully excluding LMS operational features (attendance, grading, exam banks, material distribution, student subject enrollment).

---

## 2. Schema & Database Changes

### 2.1. `wali_kelas_assignments` (In-Place Replacement)
- Migration: `2024_06_01_000002_create_wali_kelas_assignments_table.php` (replaces legacy `2023_09_18_031115_create_wali_kelas_table.php`).
- Fields:
  - `id` (bigint, PK)
  - `kelas_id` (FK `kelas.id`, `restrictOnDelete`)
  - `user_id` (FK `users.id`, `restrictOnDelete`)
  - `academic_year_id` (FK `academic_years.id`, `restrictOnDelete`)
  - `notes` (text, nullable)
  - `timestamps`
- Constraints:
  - `UNIQUE(kelas_id, academic_year_id)` — strictly one homeroom teacher per class per academic year.

### 2.2. `mapels` (New Table)
- Migration: `2024_06_01_000003_create_curriculum_domain_tables.php`.
- Fields:
  - `id` (bigint, PK)
  - `kelas_id` (FK `kelas.id`, `restrictOnDelete`)
  - `code` (string, unique)
  - `name` (string)
  - `description` (text, nullable)
  - `is_active` (boolean, default true)
  - `timestamps`
- Constraints:
  - `UNIQUE(kelas_id, name)` — unique subject name within each class level.

### 2.3. `teaching_assignments` (New Table)
- Migration: `2024_06_01_000003_create_curriculum_domain_tables.php`.
- Fields:
  - `id` (bigint, PK)
  - `kelas_id` (FK `kelas.id`, `restrictOnDelete`)
  - `mapel_id` (FK `mapels.id`, `restrictOnDelete`)
  - `user_id` (FK `users.id`, `restrictOnDelete`)
  - `academic_year_id` (FK `academic_years.id`, `restrictOnDelete`)
  - `status` (string, default `'Aktif'`)
  - `notes` (text, nullable)
  - `timestamps`
- Constraints:
  - `UNIQUE(kelas_id, mapel_id, academic_year_id)` — prevents duplicate teachers for the same subject in the same class and year, while allowing a single teacher to teach across multiple classes in that year.

---

## 3. Domain Architecture & Code Additions

### 3.1. Models & Relationships
- **`WaliKelasAssignment`** (`app/Models/WaliKelasAssignment.php`):
  - BelongsTo: `Kelas`, `User`, `AcademicYear`.
- **`Mapel`** (`app/Models/Mapel.php`):
  - BelongsTo: `Kelas`.
  - HasMany: `TeachingAssignment`.
  - Scope: `scopeActive`.
- **`TeachingAssignment`** (`app/Models/TeachingAssignment.php`):
  - BelongsTo: `Kelas`, `Mapel`, `User`, `AcademicYear`.
  - Scope: `scopeActive`.
- **`User`**, **`Kelas`**, **`AcademicYear`**: Updated with HasMany relationships to new domain entities.

### 3.2. Service Layer
- **`WaliKelasAssignmentService`** (`app/Services/Academic/WaliKelasAssignmentService.php`):
  - Transactional `assign()`, `update()`, and `delete()` methods.
- **`TeachingAssignmentService`** (`app/Services/Academic/TeachingAssignmentService.php`):
  - Transactional `assign()`, `updateTeacher()`, `updateStatus()`, `deactivate()`, and `reactivate()` methods.
  - Soft-deactivation workflow preserves academic and teaching history.

### 3.3. Controllers & Routes
- `MapelController` (`/mapel`) — Subject CRUD with DataTables, class filter, and deletion guard.
- `WaliKelasAssignmentController` (`/wali-kelas-assignment`) — Homeroom teacher assignments with DataTables and academic year filter.
- `TeachingAssignmentController` (`/teaching-assignment`) — Teaching assignments with dual filters and non-destructive deactivation.
- Access Control: Restricted to `role:Administrator|Pengurus`.

### 3.4. Navigation & Views
- `resources/views/pages/academic/mapel/` (`index.blade.php`, `include/action.blade.php`)
- `resources/views/pages/academic/wali_kelas/` (`index.blade.php`, `include/action.blade.php`)
- `resources/views/pages/academic/teaching_assignment/` (`index.blade.php`, `include/action.blade.php`)
- Integrated into `resources/views/components/navbar.blade.php`.

---

## 4. Validation Results

| Test / Check | Command | Result |
|---|---|---|
| **Cache Clear** | `php artisan optimize:clear` | **PASS** (all caches cleared) |
| **Fresh Migration & Seed** | `php artisan migrate:fresh --seed` | **PASS** (16 migrations, 5 seeders) |
| **Test Suite** | `php artisan test` | **PASS** (**188 passed, 771 assertions**) |
| **Frontend Build** | `npm run build` | **PASS** (Vite build successful) |
| **Pint Linter** | `composer run lint:check` | **PASS** (0 style violations) |
| **Security Audit** | `composer audit` | **PASS** (0 vulnerabilities) |

---

## 5. Deployment & Migration Notes

1. **Development Environment**:
   - `php artisan migrate:fresh --seed` can be executed cleanly.
2. **Production/Staging Environment**:
   - Run `php artisan migrate` to create `wali_kelas_assignments`, `mapels`, and `teaching_assignments`.
   - Run `php artisan db:seed --class=RolePermissionSeeder` to register `wali_kelas` and `pengajaran` permissions.
   - Run `php artisan view:cache` and `php artisan route:cache`.
