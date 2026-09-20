# Phase 5.8.7C-7 — Laravel 12 Academic Curriculum & Teaching Domain Foundation Report

**Phase**: 5.8.7C-7  
**Module**: Academic Domain — Curriculum & Teaching Foundation  
**Framework Baseline**: Laravel 12.69.2, PHP 8.4.16, MariaDB 10.4.32, Bootstrap 5.1.3  
**Status**: Completed & Verified  

---

## 1. Executive Summary

Phase 5.8.7C-7 solidifies the **Academic Curriculum & Teaching Foundation** of the Pondok Pesantren Fatimah Az-Zahra system. Building on the core academic foundation established in Phase 5.8.7C-6 (`AcademicYear`, `StudentBatch`, `AcademicEnrollment`), this phase establishes the curriculum ownership, class-scoped subjects, and teaching assignment domain models required before any future Learning Management System (LMS) features can be introduced.

In strict adherence to the project constraints, **no LMS features** (attendance, grading, exam banks, material distribution, student subject enrollment) were created. Instead, the architectural boundary is kept clean, robust, and hardened with foreign key integrity.

---

## 2. Architectural Audit & Domain Design Decisions

### 2.1. Teacher & Staff Representation
- **Audit**: The legacy codebase had scattered references to "guru", but no clean representation. However, the `User` model with Spatie's `HasRoles` already models system identities (`Administrator`, `Pengurus`, `Keuangan`, `Santri`).
- **Decision**: Avoid duplicate `Teacher` or `Guru` tables. Teachers are `User` records with staff roles (`Administrator` / `Pengurus`). Teaching assignments and homeroom assignments reference `users.id` directly.

### 2.2. WaliKelas Domain Replacement (`wali_kelas_assignments`)
- **Audit**: The legacy `wali_kelas` table had a faulty schema:
  1. It stored `santri_id` instead of a staff `user_id`.
  2. It had no temporal dimension (`academic_year_id`), making multi-year homeroom tracking impossible.
  3. It cascaded deletes from `kelas` and `santris`, destroying history.
  4. It had zero routes, controllers, or views.
- **Decision**: The legacy migration was modified in-place and replaced with `wali_kelas_assignments`:
  - `kelas_id` (FK `kelas.id`, `restrictOnDelete`)
  - `user_id` (FK `users.id`, `restrictOnDelete`)
  - `academic_year_id` (FK `academic_years.id`, `restrictOnDelete`)
  - `notes` (nullable text)
  - Composite Unique: `UNIQUE(kelas_id, academic_year_id)` — strictly one homeroom teacher per class per academic year.

### 2.3. Class-Scoped Subjects (`mapels`)
- **Audit**: Permissions for `mapel` were pre-declared in `config/permission.php`, but no model, table, controller, or views existed.
- **Decision**: Implemented `Mapel` scoped to `Kelas`. Pesantren curriculum varies substantially by class level (e.g., Alfiyah, Nahwu, Fiqih, Shorof):
  - `kelas_id` (FK `kelas.id`, `restrictOnDelete`)
  - `code` (string, unique system-wide)
  - `name` (string, unique per class)
  - `description` (nullable text)
  - `is_active` (boolean, default true)

### 2.4. Teaching Assignments (`teaching_assignments`)
- **Audit**: No structure existed for assigning teachers to subjects.
- **Decision**: Implemented temporal pivot entity `TeachingAssignment`:
  - `kelas_id` (FK `kelas.id`, `restrictOnDelete`)
  - `mapel_id` (FK `mapels.id`, `restrictOnDelete`)
  - `user_id` (FK `users.id`, `restrictOnDelete`)
  - `academic_year_id` (FK `academic_years.id`, `restrictOnDelete`)
  - `status` (`Aktif`, `Nonaktif`)
  - `notes` (nullable text)
  - Composite Unique: `UNIQUE(kelas_id, mapel_id, academic_year_id)` — prevents duplicate teachers for the same subject in the same class in a single year, while allowing a single teacher to teach across multiple classes in that year.
  - Soft-deactivation pattern: Deleting an assignment via UI deactivates it (`status = Nonaktif`), preserving historical curriculum records.

---

## 3. Schema & Migration Summary

| Migration File | Action | Tables / Changes |
|---|---|---|
| `2024_06_01_000002_create_wali_kelas_assignments_table.php` | Modified In-Place (Renamed from `2023_09_18_031115`) | Creates `wali_kelas_assignments` with strict FKs and unique constraint on `(kelas_id, academic_year_id)`. |
| `2024_06_01_000003_create_curriculum_domain_tables.php` | New Migration | Creates `mapels` (`code` unique, `UNIQUE(kelas_id, name)`) and `teaching_assignments` (`UNIQUE(kelas_id, mapel_id, academic_year_id)`). |

---

## 4. Service Layer Architecture

Following clean domain-driven architecture in Laravel 12:

1. **`WaliKelasAssignmentService`** (`app/Services/Academic/WaliKelasAssignmentService.php`)
   - `assign(Kelas $kelas, User $user, AcademicYear $academicYear, ?string $notes): WaliKelasAssignment`
     - Validates single active wali kelas per class per academic year within a database transaction.
   - `update(WaliKelasAssignment $assignment, User $newUser, ?string $notes): WaliKelasAssignment`
   - `delete(WaliKelasAssignment $assignment): void`

2. **`TeachingAssignmentService`** (`app/Services/Academic/TeachingAssignmentService.php`)
   - `assign(Kelas $kelas, Mapel $mapel, User $teacher, AcademicYear $academicYear, ?string $notes, string $status): TeachingAssignment`
     - Validates subject belongs to target class.
     - Enforces single active teacher per subject per class per academic year.
   - `updateTeacher(...)`
   - `updateStatus(...)`
   - `deactivate(TeachingAssignment $assignment, ?string $notes): TeachingAssignment`
   - `reactivate(TeachingAssignment $assignment, ?string $notes): TeachingAssignment`

---

## 5. Deletion Guards & Historical Integrity

- **`KelasController@destroy`**:
  - Blocks deletion if class has associated `academic_enrollments`, `mapels`, `wali_kelas_assignments`, or `teaching_assignments`.
- **`AcademicYearController@destroy`**:
  - Blocks deletion if academic year has associated `academic_enrollments`, `wali_kelas_assignments`, or `teaching_assignments`.
- **`MapelController@destroy`**:
  - Blocks deletion if subject has associated `teaching_assignments`.

---

## 6. UI/UX & Blade Modernization

All newly introduced views strictly utilize the **Pesantren Green** design tokens and reusable Blade components:
- `<x-card-toolbar>` for header layout and action triggers.
- `<x-modal-form>` and `<x-edit-modal>` for responsive modal workflows.
- `<x-delete-modal>` with non-destructive deactivation styling and descriptive entity labels.
- Responsive DataTables with AJAX server-side rendering and client-side dropdown filters.

### Navigation Integration (`resources/views/components/navbar.blade.php`)
Added under Master Data / Academic section:
- **Wali Kelas**: `wali-kelas-assignment.index`
- **Mata Pelajaran**: `mapel.index`
- **Penugasan Mengajar**: `teaching-assignment.index`

---

## 7. Verification Results

- **Automated Tests**: 188 tests passed, 807 assertions passed (100% green).
- **Static Analysis**: Laravel Pint passed with 0 lint errors.
- **Security Audit**: Composer audit passed with 0 known vulnerabilities.
- **Frontend Build**: Vite build passed successfully (`npm run build`).
