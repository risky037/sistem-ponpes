# Release Notes — Phase 5.8.7C-11: Laravel 12 Academic Reporting & Analytics Foundation

**Release Tag**: `phase-5.8.7C-11-completed`  
**Date**: 2026-09-20  
**Branch**: `develop`  
**System Baseline**: Laravel 12.x, PHP 8.4+, MariaDB 10.4.32, Bootstrap 5.1.3, Vite 4.4.9  

---

## 1. Release Objectives

Phase 5.8.7C-11 establishes the **Academic Reporting & Analytics Foundation** for the **Sistem Informasi Pondok Pesantren Fatimah Az-Zahra**.

This domain layer computes, caches, and presents aggregated academic metrics (attendance rates and weighted assessment score averages) per student per academic year, uniquely anchored to `AcademicEnrollment`.

Primary objectives accomplished:
1. **Performance Aggregation Cache (`academic_performance_summaries`)**: Normalized reporting entity caching total sessions, attendance counts, attendance rate (%), scored components, total required components, total weight, weighted score sum, average score, and computation status (`Lengkap`, `Sebagian`, `Kosong`).
2. **Explicit On-Demand Generation**: Summaries are computed only upon explicit administrative action (single-student recalculation or year-wide bulk generation), completely avoiding side-effects or N+1 recalculation overhead during everyday attendance or grade entry.
3. **Strict Enrollment Anchor**: Records are anchored directly to `academic_enrollment_id` with `restrictOnDelete` foreign key protection. Direct relationship to `santri` is intentionally omitted to maintain academic placement context.
4. **Dynamic Component Re-evaluation**: Total components are dynamically evaluated against active teaching assignments and assessment definitions for the student's class and academic year.
5. **Division-by-Zero Protection**: Full numerical guarding ensures safe calculations when total sessions or total component weights are zero.
6. **Role-Based Access Control**: Exclusively accessible by `Administrator` and `Pengurus` roles (`performance.index`, `performance.show`, `performance.generate`). Access for `Santri` is denied (HTTP 403).
7. **Strict LMS Boundary**: Excludes report cards (*rapor*), ranking (*peringkat*), graduation (*kelulusan*), student portal, LMS dashboard, exam/CBT systems, and learning materials.

---

## 2. Schema & Database Changes

Migration: `database/migrations/2024_06_01_000007_create_academic_performance_summaries_table.php`

### `academic_performance_summaries` (Reporting Cache Table)
- Fields:
  - `id` (bigint, PK)
  - `academic_enrollment_id` (FK `academic_enrollments.id`, `unique`, `restrictOnDelete`)
  - `total_sessions` (unsignedSmallInteger, default 0)
  - `present_count` (unsignedSmallInteger, default 0)
  - `excused_count` (unsignedSmallInteger, default 0)
  - `sick_count` (unsignedSmallInteger, default 0)
  - `absent_count` (unsignedSmallInteger, default 0)
  - `attendance_rate` (decimal 5,2, nullable)
  - `scored_components` (unsignedSmallInteger, default 0)
  - `total_components` (unsignedSmallInteger, default 0)
  - `weighted_score_sum` (decimal 8,2, nullable)
  - `total_weight` (unsignedSmallInteger, default 0)
  - `average_score` (decimal 5,2, nullable)
  - `computation_status` (string, default `'Kosong'`)
  - `source_version` (string, default `'v1.0'`)
  - `computed_at` (timestamp, nullable)
  - `timestamps`
- Constraints:
  - `UNIQUE(academic_enrollment_id)`
  - `FOREIGN KEY (academic_enrollment_id) REFERENCES academic_enrollments(id) ON DELETE RESTRICT`

---

## 3. Domain Service Layer

Service: `app/Services/Academic/AcademicPerformanceService.php`

- `generateSummary(AcademicEnrollment $enrollment): AcademicPerformanceSummary`
  - Validates enrollment anchor integrity.
  - Aggregates attendance records and assessment scores.
  - Derives status (`Lengkap`, `Sebagian`, `Kosong`).
  - Persists data idempotently using `updateOrCreate()` within `DB::transaction()`.
- `generateForYear(AcademicYear $year): Collection<AcademicPerformanceSummary>`
  - Queries all active enrollments (`status = 'Aktif'`) for the academic year.
  - Strictly skips non-active placements (`Nonaktif`, `Lulus`, `Pindah`).
  - Executes batch summary calculations within `DB::transaction()`.
- `computeAttendance(AcademicEnrollment $enrollment): array`
  - Counts `Hadir`, `Izin`, `Sakit`, `Alpha`.
  - Calculates `attendance_rate = (present_count / total_sessions) * 100` (safe when 0 sessions).
- `computeAssessment(AcademicEnrollment $enrollment): array`
  - Queries active teaching assignments and active definitions for the enrollment's class.
  - Matches student assessment scores with component weights.
  - Calculates `average_score = weighted_score_sum / total_weight` (safe when 0 weight).
- `deriveStatus(array $attendance, array $assessment): string`
  - `Kosong`: zero sessions and zero scores.
  - `Lengkap`: all components scored, sessions recorded, and attendance rate >= 75%.
  - `Sebagian`: partial attendance or scores.

---

## 4. Web Interface & Controller

Controller: `app/Http/Controllers/Academic/AcademicPerformanceController.php`

- `GET /academic/performance` (`academic.performance.index`):
  - Server-side DataTables endpoint supporting year, class, and aggregation status filtering.
- `GET /academic/performance/{enrollment}` (`academic.performance.show`):
  - Detailed student breakdown with attendance and component assessment score matrices.
- `POST /academic/performance/{enrollment}/generate` (`academic.performance.generate`):
  - Recalculates aggregation for a single enrollment.
- `POST /academic/performance/year/{year}/generate-all` (`academic.performance.bulk-generate`):
  - Batch recalculation for all active students in the selected academic year.

Views:
- `resources/views/pages/academic/performance/index.blade.php`: DataTables list with status badges and batch calculate action.
- `resources/views/pages/academic/performance/show.blade.php`: Enrollment details, summary metric cards, and attendance/score tabs.
- `resources/views/components/navbar.blade.php`: Added "Performa Akademik" navigation menu.

---

## 5. Security & Access Control

Registered in `config/permission.php`:
- `performance.index`
- `performance.show`
- `performance.generate`

Role Mapping:
- `Administrator`: Granted all 3 permissions.
- `Pengurus`: Granted all 3 permissions.
- `Santri`: No access (HTTP 403).

---

## 6. Verification and Validation Results

- **Automated Tests**:
  - Domain tests: `tests/Feature/Academic/AcademicPerformanceServiceTest.php` (13 passed, 49 assertions).
  - Full suite: **279 passed tests, 1029 assertions** (0 failures).
- **Migration & Database**:
  - `php artisan optimize:clear`: Passed.
  - `php artisan migrate:fresh --seed`: Passed cleanly.
- **Frontend & Linting**:
  - `npm run build`: Passed (production assets built via Vite).
  - `composer run lint:check`: Laravel Pint passed with 0 violations.
  - `composer audit`: 0 security vulnerabilities.
- **LMS Boundary Audit**:
  - Grep search confirmed 0 forbidden LMS terms in performance code.

---

## 7. Release Commit & Tag

- **Commit**: `feat(academic): implement performance analytics foundation`
- **Annotated Tag**: `phase-5.8.7C-11-completed`
