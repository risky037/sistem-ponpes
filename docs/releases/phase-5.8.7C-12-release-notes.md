# Release Notes — Phase 5.8.7C-12: Laravel 12 Academic Administration & Export Foundation

**Release Tag**: `phase-5.8.7C-12-completed`  
**Date**: 2026-09-20  
**Branch**: `develop`  
**System Baseline**: Laravel 12.x, PHP 8.4+, MariaDB 10.4.32, Bootstrap 5.1.3, Vite 4.4.9  

---

## 1. Release Objectives

Phase 5.8.7C-12 establishes the **Academic Administration & Export Foundation** for the **Sistem Informasi Pondok Pesantren Fatimah Az-Zahra**.

This domain layer provides an executive administrative hub and a unified multi-entity export engine across all previously established academic domains (Foundation, Curriculum & Teaching, Operations, Attendance, Evaluation, and Performance Analytics).

Primary objectives accomplished:
1. **Academic Administration Hub (`academic/administration`)**: Consolidated executive overview presenting institutional academic health metrics (active academic year, student enrollment coverage, active teaching assignments, recorded attendance sessions, evaluation scores, and performance summary status) with quick navigation.
2. **Academic Export Center (`academic/export`)**: Centralized export hub supporting parameterized data extraction with filters for academic year, class, subject, teacher, and enrollment/computation status.
3. **Dual Export Delivery (Excel & Printable/PDF)**:
   - **Excel Spreadsheet (`.xlsx`)**: High-performance formatted export classes leveraging `maatwebsite/excel` concerns (`FromCollection`, `ShouldAutoSize`, `WithHeadings`, `WithMapping`, `WithStyles`).
   - **Printable HTML Views (`@media print`)**: Lightweight, browser-native printable Blade views allowing instant document printing or "Save as PDF" without requiring heavy external binary dependencies (dompdf, snappy, wkhtmltopdf).
4. **Five Core Academic Export Domains**:
   - Academic Enrollment Export (`AcademicEnrollmentExport`)
   - Teaching Assignment & Weekly Schedule Export (`TeachingAssignmentExport`)
   - Attendance Rekapitulasi Summary Export (`AttendanceSummaryExport`)
   - Assessment & Score Ledger Export (`AssessmentSummaryExport`)
   - Academic Performance Analytics Export (`AcademicPerformanceExport`)
5. **Immutable Export Audit Logging (`academic_export_logs`)**: Institutional governance audit trail tracking every export transaction with authenticated user, export type, contextual academic year, class, format, filter payload JSON, record count, client IP address, and timestamp.
6. **Role-Based Access Control & Permissions**:
   - `export.index`, `export.enrollment`, `export.teaching_assignment`, `export.attendance`, `export.assessment`, `export.performance`
   - Explicitly granted to `Administrator` and `Pengurus`.
   - Access denied (HTTP 403 Forbidden) for `Santri`, `Keuangan`, and `Alumni`.
7. **Strict LMS Boundary Enforcement**: Zero implementation of report cards (*rapor*), ranking (*peringkat*), graduation workflows, student portal views, exam/CBT systems, question banks, or learning course modules.

---

## 2. Schema & Database Changes

Migration: `database/migrations/2024_06_01_000008_create_academic_export_logs_table.php`

### `academic_export_logs` (Audit History Table)
- Fields:
  - `id` (bigint, PK)
  - `user_id` (FK `users.id`, `restrictOnDelete`)
  - `export_type` (string: `enrollment`, `teaching_assignment`, `attendance`, `assessment`, `performance`)
  - `academic_year_id` (FK `academic_years.id`, nullable, `restrictOnDelete`)
  - `kelas_id` (FK `kelas.id`, nullable, `restrictOnDelete`)
  - `format` (string: `xlsx`, `print`)
  - `filter_payload` (json, nullable)
  - `records_count` (unsignedInteger, default 0)
  - `ip_address` (string 45, nullable)
  - `created_at` (timestamp, useCurrent)
- Characteristics:
  - Immutable: No `updated_at` column.
  - Foreign key safety: Referenced entity existence verified before persisting FK columns; arbitrary/non-existent filters preserved within `filter_payload` JSON.

---

## 3. Domain Architecture & Services

### 3.1. Model
- `app/Models/AcademicExportLog.php`:
  - Scopes: `forUser()`, `forAcademicYear()`, `ofType()`
  - Relations: `belongsTo(User::class)`, `belongsTo(AcademicYear::class)`, `belongsTo(Kelas::class)`
  - Casts: `filter_payload` (array), `created_at` (datetime), `records_count` (integer)

### 3.2. Service Layer
- `app/Services/Academic/AcademicExportService.php`:
  - `exportEnrollment(array $filters, string $format, User $user, ?string $ip): BinaryFileResponse|array`
  - `exportTeachingAssignment(array $filters, string $format, User $user, ?string $ip): BinaryFileResponse|array`
  - `exportAttendance(array $filters, string $format, User $user, ?string $ip): BinaryFileResponse|array`
  - `exportAssessment(array $filters, string $format, User $user, ?string $ip): BinaryFileResponse|array`
  - `exportPerformance(array $filters, string $format, User $user, ?string $ip): BinaryFileResponse|array`
  - `getRecentLogs(int $limit = 50): Collection`

### 3.3. Export Classes (`app/Exports/Academic/`)
- `AcademicEnrollmentExport.php`
- `TeachingAssignmentExport.php`
- `AttendanceSummaryExport.php`
- `AssessmentSummaryExport.php`
- `AcademicPerformanceExport.php`

### 3.4. Controllers
- `app/Http/Controllers/Academic/AcademicAdministrationController.php`: Executive statistics and domain hub overview.
- `app/Http/Controllers/Academic/AcademicExportController.php`: Export hub interface and file generation endpoints.

---

## 4. UI & Printable Templates

- `resources/views/pages/academic/administration/index.blade.php`: Overview metrics and recent audit history.
- `resources/views/pages/academic/export/index.blade.php`: Tabbed filter panels and export hub.
- `resources/views/pages/academic/export/print_enrollment.blade.php`: Printable enrollment table with `@media print`.
- `resources/views/pages/academic/export/print_teaching_assignment.blade.php`: Printable teaching schedule table.
- `resources/views/pages/academic/export/print_attendance_summary.blade.php`: Printable attendance rekap table.
- `resources/views/pages/academic/export/print_assessment_summary.blade.php`: Printable evaluation grade ledger.
- `resources/views/pages/academic/export/print_performance.blade.php`: Printable academic performance aggregation overview.

---

## 5. Verification & Test Results

- **Total Tests**: **298 passed** (1107 assertions)
- **New Feature Tests**: 19 tests, 78 assertions
  - `tests/Feature/Academic/AcademicExportServiceTest.php` (8 tests, 32 assertions)
  - `tests/Feature/Academic/AcademicExportControllerTest.php` (11 tests, 46 assertions)
- **Regression Check**: Zero regressions across legacy or existing academic tests.
- **Frontend Build**: `npm run build` completed cleanly (Vite v4.4.9).
- **Code Style**: `composer run lint:check` passed with Laravel Pint.
- **Security Audit**: `composer audit` returned 0 vulnerabilities.
- **LMS Boundary**: Clean (0 matches for prohibited terms).
