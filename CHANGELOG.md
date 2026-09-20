# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Planned
- **[Phase 5.8.7D] Academic Intelligence Layer:**
  - Operational analytics and institutional telemetry
  - Teacher workload distribution and capacity analytics
  - Academic Key Performance Indicators (KPIs)
  - Predictive insights on student attendance and subject mastery

---

## [5.8.7C-12] - 2026-09-20

### Added
- **Academic Administration Hub (`academic/administration`)**:
  - Executive overview dashboard presenting systemic statistics across enrollments, curriculum, attendance sessions, evaluation scores, and performance analytics.
  - Recent export audit log feed for institutional oversight.
- **Academic Export Center (`academic/export`)**:
  - Centralized interface with multi-parameter filtering (academic year, class, subject, teacher, status) supporting 5 core academic domains.
  - Dual delivery options: formatted Excel spreadsheet (`.xlsx`) and browser-native printable view (`@media print`) with zero heavy binary dependencies.
- **Export Classes & Formats (`app/Exports/Academic/`)**:
  - `AcademicEnrollmentExport` (student placement, class, batch, status).
  - `TeachingAssignmentExport` (teachers, subjects, class schedules).
  - `AttendanceSummaryExport` (session totals, H/I/S/A counts, attendance %).
  - `AssessmentSummaryExport` (evaluation schemes, components, student scores).
  - `AcademicPerformanceExport` (aggregated attendance rate, average score, computation status).
- **Audit Logging Foundation (`academic_export_logs`)**:
  - Migration `2024_06_01_000008_create_academic_export_logs_table.php` recording user ID, export type, contextual filters, record count, format, client IP, and timestamp with `restrictOnDelete` foreign key protection.
- **Permissions & Security**:
  - Permissions: `export.index`, `export.enrollment`, `export.teaching_assignment`, `export.attendance`, `export.assessment`, `export.performance`.
  - Exclusively authorized for `Administrator` and `Pengurus`; forbidden for `Santri`, `Keuangan`, and `Alumni` (HTTP 403).
- **Automated Tests**:
  - `AcademicExportServiceTest` and `AcademicExportControllerTest` adding 19 new feature tests (bringing total suite to 298 passed, 1107 assertions).

---

## [5.8.7C-11] - 2026-09-20

### Added
- **Academic Performance Summaries Domain (`academic_performance_summaries`)**:
  - Pre-aggregated reporting entity caching total sessions, attendance counts, attendance rate (%), scored components, total required components, total weight, weighted score sum, average score, and computation status (`Lengkap`, `Sebagian`, `Kosong`).
  - Primary anchor to `AcademicEnrollment` with `restrictOnDelete` foreign key protection.
  - Explicit calculation versioning via `source_version` (default `'v1.0'`).
- **AcademicPerformanceService Layer**:
  - `generateSummary(AcademicEnrollment)` for single-student on-demand recalculation with division-by-zero protection.
  - `generateForYear(AcademicYear)` for batch active-student recalculation within database transactions.
  - Dynamic evaluation of active components against class teaching assignments.
- **AcademicPerformanceController & Web UI**:
  - Server-side DataTables listing (`academic.performance.index`) with year, class, and status filters.
  - Detailed student breakdown view (`academic.performance.show`) with attendance and assessment score matrices.
  - Dedicated administrative routes for single and bulk recalculation.
- **Role-Based Access Control**:
  - Registered `performance.index`, `performance.show`, `performance.generate` permissions assigned to `Administrator` and `Pengurus` roles; strictly forbidden for `Santri`.
  - Added "Performa Akademik" navigation menu item in navbar.
- **Automated Tests**:
  - `AcademicPerformanceServiceTest` with 13 tests and 49 assertions covering empty state, calculations, completeness derivation, division-by-zero safety, idempotency, batch generation, and authorization (total suite: 279 passed, 1029 assertions).

---

## [5.8.7C-10] - 2026-09-20

### Added
- **Academic Evaluation Domain Foundation**:
  - Three-tier normalized assessment architecture (`assessment_definitions`, `assessment_components`, `student_assessment_scores`).
  - Two-tier weight model (academic year defaults with teaching assignment overrides).
  - Historical data preservation policy (hard delete if empty; soft deactivation via `is_active = false` if components/scores exist).
- **AssessmentService Layer**:
  - Encapsulated business rules for score ranges (0–100), weight ranges (0–100), class-year enrollment integrity, and bulk grading transactions.
- **Unified AssessmentController & UI**:
  - Management interfaces for definitions, components, and bulk score entry.
- **Permissions & Tests**:
  - Registered `assessment.definition.*`, `assessment.component.*`, `assessment.score.*` for `Administrator` and `Pengurus`.
  - Automated test suite expanded to 266 passed tests, 979 assertions.

---

## [5.8.7C-9] - 2026-09-20

### Added
- **Attendance Domain Foundation**:
  - `attendance_records` table linked to `TeachingSession` and `AcademicEnrollment` with unique session-student constraint and `restrictOnDelete` foreign keys.
  - Historical accountability tracking with `marked_at` and `marked_by` user references.
- **AttendanceService Layer**:
  - Invariant validation for cancelled session guards, class/year consistency, status enum validation, and completeness verification.
- **Attendance Management UI**:
  - DataTables session overview and responsive attendance grading sheet.
- **Permissions & Tests**:
  - Registered `attendance.index`, `attendance.create`, `attendance.update` for `Administrator` and `Pengurus`.
  - Automated test suite expanded to 230 passed tests, 889 assertions.

---

## [5.8.7C-8] - 2026-09-20

### Added
- **Academic Operational Foundation**:
  - `class_schedules` domain allocating weekly timetable slots with slot collision prevention.
  - `academic_calendar_events` domain tracking institutional academic milestones.
  - `teaching_sessions` domain establishing instructional delivery logs with defined lifecycle (`Planned`, `Completed`, `Cancelled`).
- **Domain Services**:
  - `AcademicScheduleService` and `TeachingSessionService` encapsulating operational invariants.
- **Operational UI**:
  - Responsive Blade management interfaces for Class Schedules and Academic Calendar Events.
- **Automated Tests**:
  - Automated test suite expanded to 211 passed tests, 824 assertions.

---

## [5.8.7C-7] - 2026-09-20

### Added
- **Academic Curriculum & Teaching Domain Foundation**:
  - Replaced legacy unused `wali_kelas` with normalized `wali_kelas_assignments` table.
  - Introduced class-scoped `Mapel` with code uniqueness and active status filtering.
  - Established `teaching_assignments` linking teachers, subjects, classes, and academic years with temporal uniqueness.
- **Domain Services**:
  - `WaliKelasAssignmentService` and `TeachingAssignmentService` encapsulating assignment invariants and soft-deactivation lifecycles.
- **Curriculum & Assignment UI**:
  - DataTables management interfaces for Mata Pelajaran, Wali Kelas, and Teaching Assignments.
- **Automated Tests**:
  - Automated test suite expanded to 192 passed tests, 762 assertions.

---

## [v12.0.0-security-baseline] - 2026-09-19

### Added
- Official **Laravel 12.69.2** runtime baseline on **PHP 8.4.16**.
- Automated security hardening regression test suite (107 passed, 531 assertions).
- Comprehensive developer workflow scripts in `composer.json` (`dev`, `test`, `lint`, `lint:check`, `setup`).
- Repository code styling configuration (`pint.json`) adhering to Laravel preset with migration isolation (100% compliance).
- Technical documentation hub in `docs/reports/` categorizing all historical audit and migration reports.
- GitHub Actions CI matrix optimization with `sqlite, pdo_sqlite` extension integration.

### Changed
- Upgraded core framework directly from Laravel 11.56.1 to Laravel 12.69.2.
- Upgraded package ecosystem (`spatie/laravel-permission`, `yajra/laravel-datatables`, `milon/barcode`, `revolution/laravel-google-sheets`, `intervention/image-laravel`, `phpunit/phpunit`, `nunomaduro/collision`).
- Normalized git branch strategy: consolidated migration and stabilization baseline into `develop` as primary canonical branch.
- Replaced deprecated `queue:work --daemon` scheduled tasks with non-blocking `queue:work --stop-when-empty`.

### Security
- Implemented login rate limiting via `RateLimiter` in `AuthController` with lockout thresholds.
- Enforced administrative account protections (prevention of self-deletion, self-demotion, and last-admin removal).
- Protected default institutional roles (`Administrator`, `Keuangan`, `Santri`) from accidental deletion via permission config.
- Hidden `remember_token` attribute on `User` model array/JSON serialization.
- Replaced permissive directory creation masks (`0777`) with secure `0755` permissions across controllers.
- Hardened image upload validation against malicious script injection and validated master student card templates.
- Enforced strict origin matching in CORS configuration and configurable Sanctum token expirations.

### Removed
- Removed obsolete JetBrains `qodana.yaml` configuration.

---

## [1.0.0] - 2026-09-17

### Added
- Baseline legacy application structure for Pondok Pesantren Fatimah Az-Zahra.
