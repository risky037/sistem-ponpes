# Pre-Implementation Audit — Laravel 12 Academic Administration & Export Foundation

**Phase**: 5.8.7C-12  
**Date**: 2026-09-20  
**Baseline**: Laravel 12.69.2, PHP 8.4.16, MariaDB 10.4.32  
**Previous Phase Tag**: `phase-5.8.7C-11-completed` (`e7e13035`)  
**Test Baseline**: 279 passed, 1029 assertions  
**GitHub Issue**: #49 (`[Phase 5.8.7C-12] Academic Administration & Export Foundation`)  
**GitHub Project**: [Sistem Pesantren Az-Zahra (Project #6)](https://github.com/users/risky037/projects/6)

---

## 1. Executive Summary

Phase 5.8.7C-12 establishes the **Academic Administration & Export Foundation** for the Sistem Pesantren Fatimah Az-Zahra. Building upon the five foundational academic domains established in Phases 5.8.7C-7 through 5.8.7C-11 (Foundation, Curriculum & Teaching, Operational Schedule & Attendance, Evaluation, and Performance Analytics), this phase introduces a unified administrative export and reporting capability across all academic data.

### Strict LMS Boundary Enforcement
In strict compliance with architectural guidelines, Phase 5.8.7C-12 maintains a rigid non-LMS boundary:
- **STRICTLY ALLOWED**:
  - Administrative data management overview
  - Multi-entity academic export engine (Excel via `maatwebsite/excel` and Printable View via Blade `@media print`)
  - Cross-domain administrative filtering engine
  - Administrative audit logging for export accountability (`academic_export_logs`)
- **STRICTLY PROHIBITED**:
  - Student portal access to grades or export data
  - Online learning / LMS course modules
  - Exam system / CBT / Online quiz
  - Question banks
  - Learning materials / syllabus distribution
  - Student ranking / leaderboard
  - Graduation workflows / alumni migration
  - Report card (*rapor*) generation / official transcript printing

---

## 2. Existing Repository Architecture Audit

### 2.1. Git & System Baseline
- **Branch**: `develop`
- **Release Tag**: `phase-5.8.7C-11-completed`
- **Commit**: `e7e13035` (`feat(academic): implement performance analytics foundation`)
- **PHP Version**: `8.4.16`
- **Laravel Framework**: `12.69.2`
- **Database**: MariaDB `10.4.32`
- **Active Tests**: 279 feature tests, 1029 assertions (100% passing)

### 2.2. Confirmed Migration Sequence & Lineage
The academic database schema has been built sequentially through 7 dedicated migrations:

| # | Migration File | Target Tables | Primary Anchor / Foreign Keys |
|---|---|---|---|
| 01 | `2024_06_01_000001_create_academic_foundation_tables` | `academic_years`, `student_batches`, `academic_enrollments` | `santris`, `kelas` (restrictOnDelete) |
| 02 | `2024_06_01_000002_create_wali_kelas_assignments_table` | `wali_kelas_assignments` | `kelas`, `users`, `academic_years` (restrictOnDelete) |
| 03 | `2024_06_01_000003_create_curriculum_domain_tables` | `mapels`, `teaching_assignments` | `kelas`, `mapels`, `users`, `academic_years` |
| 04 | `2024_06_01_000004_create_academic_operational_tables` | `class_schedules`, `academic_calendar_events`, `teaching_sessions` | `teaching_assignments`, `academic_years` |
| 05 | `2024_06_01_000005_create_attendance_records_table` | `attendance_records` | `teaching_sessions`, `academic_enrollments` |
| 06 | `2024_06_01_000006_create_evaluation_domain_tables` | `assessment_definitions`, `assessment_components`, `student_assessment_scores` | `academic_years`, `teaching_assignments`, `academic_enrollments` |
| 07 | `2024_06_01_000007_create_academic_performance_summaries_table` | `academic_performance_summaries` | `academic_enrollments` (1:1 anchor, restrictOnDelete) |

### 2.3. Existing Academic Domain Inventory

#### Domain 1: Academic Foundation (Phase 5.8.7C-7 / 5.8.7C-8)
- **Models**: `AcademicYear`, `StudentBatch`, `AcademicEnrollment`
- **Services**: `AcademicEnrollmentService`
- **Controllers**: `AcademicYearController`, `StudentBatchController`, `AcademicEnrollmentController`

#### Domain 2: Curriculum & Teaching (Phase 5.8.7C-7)
- **Models**: `Mapel`, `WaliKelasAssignment`, `TeachingAssignment`
- **Services**: `TeachingAssignmentService`, `WaliKelasAssignmentService`
- **Controllers**: `MapelController`, `WaliKelasAssignmentController`, `TeachingAssignmentController`

#### Domain 3: Operational Schedules & Calendar (Phase 5.8.7C-8)
- **Models**: `ClassSchedule`, `AcademicCalendarEvent`, `TeachingSession`
- **Services**: `AcademicScheduleService`, `TeachingSessionService`
- **Controllers**: `ClassScheduleController`, `AcademicCalendarEventController`

#### Domain 4: Attendance (Phase 5.8.7C-9)
- **Models**: `AttendanceRecord`
- **Services**: `AttendanceService`
- **Controllers**: `AttendanceController`

#### Domain 5: Academic Evaluation (Phase 5.8.7C-10)
- **Models**: `AssessmentDefinition`, `AssessmentComponent`, `StudentAssessmentScore`
- **Services**: `AssessmentService`
- **Controllers**: `AssessmentController`

#### Domain 6: Performance Analytics & Aggregations (Phase 5.8.7C-11)
- **Models**: `AcademicPerformanceSummary`
- **Services**: `AcademicPerformanceService`
- **Controllers**: `AcademicPerformanceController`

### 2.4. Current Domain Map

```
                           +------------------------+
                           |     AcademicYear       |
                           +-----------+------------+
                                       |
           +---------------------------+---------------------------+
           |                           |                           |
+----------v-----------+   +-----------v------------+   +----------v-----------+
| AcademicEnrollment   |   |   TeachingAssignment   |   | AssessmentDefinition |
| (Santri x Kelas x AY)|   | (Guru x Mapel x Kelas) |   | (Year Scheme Config) |
+----------+-----------+   +-----------+------------+   +----------+-----------+
           |                           |                           |
           |                           +-------------+             |
           |                           |             |             |
           |               +-----------v----+   +----v-------------v----+
           |               | TeachingSession|   |  AssessmentComponent  |
           |               +-----------+----+   +------------+----------+
           |                           |                     |
           +-------------------+       |                     |
           |                   |       |                     |
+----------v-----------+   +---v-------v----+   +------------v----------+
|  PerformanceSummary  |   |AttendanceRecord|   | StudentAssessmentScore|
|  (Enrollment Anchor) |   | (Session x Enr)|   |   (Comp x Enrollment) |
+----------------------+   +----------------+   +-----------------------+
```

---

## 3. Analysis of Existing Administrative Gaps

An exhaustive audit of the 7 core academic administration capabilities reveals the following:

| Administrative Capability | Current Repository State | Identified Architectural Gap | Proposed Phase 5.8.7C-12 Solution |
|---|---|---|---|
| **1. Academic Master Data Management** | 11 separate isolated CRUD pages across different routes. | No central administrative hub or overview providing holistic system statistics (total enrollments, assignment coverage, attendance rates, evaluation completion). | Create dedicated **Academic Administration Hub (`academic/administration`)** consolidating cross-domain health status and direct export actions. |
| **2. Academic Data Filtering** | Basic ad-hoc query filtering hardcoded in individual controller DataTables AJAX blocks (`$request->filled(...)`). | No centralized filter pipeline or filter DTO capable of driving both UI DataTables and export queries consistently across domains. | Implement structured filtering methods in `AcademicExportService` supporting `academic_year_id`, `kelas_id`, `mapel_id`, `user_id`, and status parameters. |
| **3. Academic Export** | Only legacy core exports exist (`SantriExport`, `KamarExport`, `TabunganExport`). Zero academic domain exports exist. | Administrators have no method to download or export academic data to spreadsheet or printable documents. | Build unified **Academic Export Foundation** with dedicated export classes under `app/Exports/Academic/` and printable Blade views under `resources/views/pages/academic/export/`. |
| **4. Administrative Reporting** | Per-student performance summary show page exists (`AcademicPerformanceController::show`). | No class-level or batch administrative overview reports for aggregate academic verification. | Introduce administrative summary export views (Class Attendance Rekap, Assessment Grade Ledger, Performance Overview). |
| **5. Data Archival** | AcademicYear has `is_active` boolean; AcademicEnrollment has `status` (Aktif/Nonaktif/Lulus/Pindah). | No explicit administrative mechanism to filter historical archived data vs current active year in exports. | Include strict historical filtering with default fallback to active academic year while permitting historical year archive queries. |
| **6. Audit History** | Generic `activity_logs` table exists for standard model observers. | No tracking of administrative export activities (who downloaded sensitive student academic records, when, what filters, record count, and IP). | Introduce **`academic_export_logs`** table to record immutable audit metadata for all academic export operations. |
| **7. Bulk Administrative Operation** | `AcademicPerformanceService::generateForYear()` exists. | No bulk export operations or single-click multi-class export capabilities. | Implement multi-class / year-wide bulk export capabilities across all academic entities. |

---

## 4. Academic Export Candidates & Data Entity Audit

Based on existing models and relationships, 5 core export candidates are identified:

### Candidate 1: Academic Enrollment Export
- **Domain**: Academic Foundation
- **Data Source**: `AcademicEnrollment`
- **Eager Loading**: `santri.user`, `santri.wali_santri`, `kelas`, `academicYear`, `studentBatch`
- **Required Filters**: `academic_year_id` (default active), `kelas_id` (optional), `status` (optional)
- **Supported Formats**:
  - Excel (`.xlsx` via `AcademicEnrollmentExport`)
  - Printable HTML (`@media print` via `pages.academic.export.print_enrollment`)
- **Required Permission**: `export.enrollment`

### Candidate 2: Teaching Assignment & Schedule Export
- **Domain**: Curriculum & Operation
- **Data Source**: `TeachingAssignment`
- **Eager Loading**: `user` (guru), `mapel`, `kelas`, `academicYear`, `classSchedules`
- **Required Filters**: `academic_year_id` (default active), `kelas_id` (optional), `user_id` (optional)
- **Supported Formats**:
  - Excel (`.xlsx` via `TeachingAssignmentExport`)
  - Printable HTML (`@media print` via `pages.academic.export.print_teaching_assignment`)
- **Required Permission**: `export.teaching_assignment`

### Candidate 3: Attendance Summary Rekap Export
- **Domain**: Attendance
- **Data Source**: Aggregated `AttendanceRecord` via `TeachingSession` & `AcademicEnrollment`
- **Eager Loading**: `academicEnrollment.santri`, `academicEnrollment.kelas`, `teachingSession.teachingAssignment.mapel`
- **Required Filters**: `academic_year_id` (required), `kelas_id` (optional), `mapel_id` (optional), `start_date`, `end_date`
- **Supported Formats**:
  - Excel (`.xlsx` via `AttendanceSummaryExport`)
  - Printable HTML (`@media print` via `pages.academic.export.print_attendance_summary`)
- **Required Permission**: `export.attendance`

### Candidate 4: Assessment Summary & Score Ledger Export
- **Domain**: Academic Evaluation
- **Data Source**: `StudentAssessmentScore` joined with `AssessmentComponent`, `AssessmentDefinition`, `AcademicEnrollment`
- **Eager Loading**: `academicEnrollment.santri`, `assessmentComponent.assessmentDefinition`, `assessmentComponent.teachingAssignment.mapel`
- **Required Filters**: `academic_year_id` (required), `kelas_id` (optional), `teaching_assignment_id` / `mapel_id` (optional)
- **Supported Formats**:
  - Excel (`.xlsx` via `AssessmentSummaryExport`)
  - Printable HTML (`@media print` via `pages.academic.export.print_assessment_summary`)
- **Required Permission**: `export.assessment`

### Candidate 5: Academic Performance Analytics Export
- **Domain**: Academic Reporting & Analytics
- **Data Source**: `AcademicPerformanceSummary`
- **Eager Loading**: `academicEnrollment.santri`, `academicEnrollment.kelas`, `academicEnrollment.academicYear`
- **Required Filters**: `academic_year_id` (required), `kelas_id` (optional), `computation_status` (optional)
- **Supported Formats**:
  - Excel (`.xlsx` via `AcademicPerformanceExport`)
  - Printable HTML (`@media print` via `pages.academic.export.print_performance`)
- **Required Permission**: `export.performance`

---

## 5. Domain Architecture Evaluation: Option A vs Option B

### Option A: Dedicated Queue-Based Export Domain (`ExportJob`, `ExportTemplate`, `ExportService`)
- **Structure**:
  - Database tables for export queue jobs, progress tracking, downloadable temporary file links, template customizations.
  - Background worker process (`php artisan queue:work`) executing asynchronous export rendering.
  - S3 / Local storage storage lifecycle cron jobs for expired export cleanup.
- **Evaluation**:
  - **Pros**: Handles ultra-large datasets (100k+ rows) without hitting HTTP request execution limits.
  - **Cons**: Significant architectural over-engineering. In a typical pesantren institution, student cohorts range from 200 to 2,500 active records. Synchronous queries execute in 15–70 milliseconds. Introducing queues introduces background failure modes, worker downtime risks, polling UX complexity, and testing friction without any measurable real-world performance gain.

### Option B: Service-Driven Direct Synchronous Export Layer (RECOMMENDED)
- **Structure**:
  - Central `AcademicExportService` orchestrating data retrieval, query filtering, and export execution.
  - Domain-specific export classes under `app/Exports/Academic/` implementing `Maatwebsite\Excel\Concerns` (`FromCollection`, `ShouldAutoSize`, `WithHeadings`, `WithMapping`, `WithStyles`).
  - Standardized printable Blade views styled with `@media print` for browser print/PDF export.
  - Dedicated audit logging table (`academic_export_logs`) tracking administrative accountability.
- **Evaluation**:
  - **Pros**: Immediate file download response, zero external worker dependencies, seamless user experience, low memory overhead via eager-loaded chunked queries, consistent with existing `SantriController` export pattern, 100% testable in synchronous PHPUnit feature tests.
  - **Cons**: Not designed for massive multi-million record datasets (irrelevant for school/pesantren domain).

**Recommendation**: **Option B**. It delivers robust, maintainable, and idiomatic Laravel architecture that perfectly satisfies institutional requirements.

---

## 6. Database Impact Assessment

To fulfill the requirements of **Audit History** and **Administrative Accountability** without altering existing historical academic tables, Phase 5.8.7C-12 introduces one new migration:

### Migration: `2024_06_01_000008_create_academic_export_logs_table.php`
- **Table Name**: `academic_export_logs`
- **Purpose**: Record immutable metadata of every academic export transaction for institutional data security and audit compliance.
- **Columns & Schema**:
  ```php
  Schema::create('academic_export_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
      $table->string('export_type'); // enrollment, teaching_assignment, attendance, assessment, performance
      $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->restrictOnDelete();
      $table->foreignId('kelas_id')->nullable()->constrained('kelas')->restrictOnDelete();
      $table->string('format'); // xlsx, print
      $table->json('filter_payload')->nullable();
      $table->unsignedInteger('records_count')->default(0);
      $table->string('ip_address', 45)->nullable();
      $table->timestamp('created_at')->useCurrent();
  });
  ```
- **Conventions Preserved**:
  - Strict `restrictOnDelete()` on foreign keys (`user_id`, `academic_year_id`, `kelas_id`).
  - Read-only audit log: no `updated_at` column needed (immutable historical record).
  - Encapsulated write operations within `DB::transaction()`.

---

## 7. Permission Architecture Design

Following the established authorization pattern (`attendance.*`, `assessment.*`, `performance.*`), Phase 5.8.7C-12 introduces the `export` permission group in `config/permission.php`:

### Permission Group: `export`
1. `export.index` — Access the Academic Administration & Export Hub.
2. `export.enrollment` — Export academic enrollment records.
3. `export.teaching_assignment` — Export teaching assignments and class schedules.
4. `export.attendance` — Export attendance records and rekap summaries.
5. `export.assessment` — Export assessment schemes and student scores.
6. `export.performance` — Export academic performance analytics summaries.

### Role Assignment Matrix

| Role | `export.index` | `export.enrollment` | `export.teaching_assignment` | `export.attendance` | `export.assessment` | `export.performance` |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Administrator** |  Allow |  Allow |  Allow |  Allow |  Allow |  Allow |
| **Pengurus** |  Allow |  Allow |  Allow |  Allow |  Allow |  Allow |
| **Santri** | ⛔ Deny | ⛔ Deny | ⛔ Deny | ⛔ Deny | ⛔ Deny | ⛔ Deny |
| **Keuangan** | ⛔ Deny | ⛔ Deny | ⛔ Deny | ⛔ Deny | ⛔ Deny | ⛔ Deny |
| **Alumni** | ⛔ Deny | ⛔ Deny | ⛔ Deny | ⛔ Deny | ⛔ Deny | ⛔ Deny |

- **Security Enforcement**:
  - Routes protected by `middleware(['role:Administrator|Pengurus'])` and specific `can:export.*` gates.
  - Any request from `Santri`, `Keuangan`, or `Alumni` strictly aborts with HTTP `403 Forbidden`.

---

## 8. Testing Strategy

All verification adheres strictly to the repository baseline: **Feature tests only**.

### Current Baseline
- **Tests**: 279 passed
- **Assertions**: 1029
- **Regression Policy**: Zero tolerance for regressions in existing test suites.

### New Test Suites for Phase 5.8.7C-12
1. **`AcademicExportServiceTest.php`**:
   - Query filtering logic per export candidate (verifies accurate filtering by academic year, class, and status).
   - Accurate data mapping and calculation of exported rows.
   - Idempotent audit log generation in `academic_export_logs`.
   - Handling of empty dataset exports (graceful empty export with valid headers, 0 rows).
2. **`AcademicExportControllerTest.php`**:
   - Authorization gates: Admin & Pengurus (200 OK / Binary download), Santri (403), Guest (302).
   - Validation failures: Missing required parameters, invalid export formats, non-existent entity IDs.
   - Excel download endpoint response assertions (`BinaryFileResponse`, MIME `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`).
   - Print preview view response assertions (200 OK, valid Blade view rendered with `@media print`).
   - Audit log creation verification upon export download.

Target post-implementation test baseline: **~295+ tests, ~1080+ assertions**.

---

## 9. Risk Assessment & Mitigation

| Risk Identified | Impact | Mitigation Strategy |
|---|---|---|
| **Memory Spike on Large Exports** | Medium | Use query eager-loading (`with(...)`), selective column queries, and streaming collections where appropriate. |
| **LMS Boundary Leakage** | High | Explicitly reject report card generation (*rapor*), ranking calculations, or student portal views in all export classes and views. |
| **Historical Data Modification** | High | Export operations are strictly read-only on academic domain data; writes are strictly limited to logging in `academic_export_logs`. |
| **Data Privacy / Sensitive Scores Exposure** | High | Enforce strict role-based access control (`Administrator|Pengurus` only), preventing Santri or unauthorized roles from viewing or downloading academic exports. |

---

## 10. Audit Conclusion

The repository architecture is fully prepared for **Phase 5.8.7C-12: Academic Administration & Export Foundation**. The proposed architecture is synchronous, service-driven, cleanly integrated with `maatwebsite/excel` and native Blade print views, and backed by an immutable audit log table.
