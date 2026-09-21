# Pre-Implementation Audit — Laravel 12 Academic Intelligence Layer

**Phase**: 5.8.7D  
**Date**: 2026-09-21  
**Baseline**: Laravel 12.69.2, PHP 8.4.16, MariaDB 10.4.32  
**Previous Phase Tag**: `phase-5.8.7C-12-completed`  
**Test Baseline**: 298 passed, 1107 assertions (100% passing)  
**System**: Sistem Informasi Pondok Pesantren Fatimah Az-Zahra  

---

## 1. Executive Summary

Phase 5.8.7D introduces the **Academic Intelligence Layer** to transform raw academic operational data accumulated across Phases 5.8.7C-7 through 5.8.7C-12 into actionable institutional intelligence and decision support metrics.

While preceding phases successfully established foundational domain entities (Academic Year, Batches, Enrollments, Curriculum, Teaching Assignments, Operational Scheduling, Attendance Tracking, Assessment Scoring, Performance Summaries, and Administrative Exports), administrators and leadership currently lack a unified, high-level intelligence interface to monitor institutional KPIs, analyze longitudinal attendance patterns, track teacher workload balance, identify operational bottlenecks, and make data-driven academic policy decisions.

### Core Strategic Focus Areas
1. **Academic KPI Foundation**: High-level institutional vital signs (retention, session delivery rate, attendance index, evaluation coverage, institutional score average).
2. **Institutional Analytics**: Score distribution histograms, grade tier breakdowns, cross-class performance comparisons, and historical semester-over-semester trends.
3. **Teacher Workload Intelligence**: Faculty allocation distribution, planned vs. completed session fulfillment, assignment load balance, and grading timeliness tracking.
4. **Operational Monitoring**: Class health diagnostics, early warnings for low attendance (<75%), unfulfilled class schedules, and missing assessment components.
5. **Decision Support Metrics**: Executive summaries highlighting operational bottlenecks and intervention recommendations for administrators and leadership (*Dewan Asatidz / Pengurus*).

---

## 2. Current Architecture Assessment

### 2.1. System Baseline & Lineage
- **PHP Version**: `8.4.16`
- **Laravel Framework**: `12.69.2`
- **Database**: MariaDB `10.4.32`
- **Frontend Stack**: Blade SSR, Bootstrap 5.x, ApexCharts (`apexcharts.min.js`), DataTables, jQuery 3.7.1, Vite
- **Authorization**: Spatie Laravel Permission (`spatie/laravel-permission`)
- **Active Tests**: 298 feature tests, 1107 assertions (100% green)

### 2.2. Preceding Domain Migrations & Anchor Lineage
The academic architecture in the database has evolved through 8 dedicated, sequential migrations:

```
[01] academic_foundation_tables (2024_06_01_000001)
     └── academic_years, student_batches, academic_enrollments
[02] wali_kelas_assignments_table (2024_06_01_000002)
     └── wali_kelas_assignments
[03] curriculum_domain_tables (2024_06_01_000003)
     └── mapels, teaching_assignments
[04] academic_operational_tables (2024_06_01_000004)
     └── class_schedules, academic_calendar_events, teaching_sessions
[05] attendance_records_table (2024_06_01_000005)
     └── attendance_records
[06] evaluation_domain_tables (2024_06_01_000006)
     └── assessment_definitions, assessment_components, student_assessment_scores
[07] academic_performance_summaries_table (2024_06_01_000007)
     └── academic_performance_summaries (1:1 with academic_enrollments)
[08] academic_export_logs_table (2024_06_01_000008)
     └── academic_export_logs (audit trail for exports)
```

### 2.3. Domain Entity Relationship Graph
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

### 2.4. Audit of Existing Service Layer Capabilities
Existing academic services provide specific, single-responsibility operational capabilities:
1. `AcademicEnrollmentService`: Enrolls students, handles transfers, deactivates enrollments.
2. `TeachingAssignmentService`: Assigns teachers to subjects and classes per academic year.
3. `TeachingSessionService`: Plans, completes, or cancels individual teaching sessions.
4. `AttendanceService`: Records and updates per-student attendance for teaching sessions.
5. `AssessmentService`: Manages assessment definitions, components, and records student scores.
6. `AcademicPerformanceService`: Computes attendance metrics, weighted assessment averages, and completion statuses per student enrollment.
7. `AcademicExportService`: Handles Excel exports and printable views with audit logging.

**Gap Identified**: No service currently aggregates cross-domain operational data into macro-level institutional indicators, teacher workload distributions, class operational health indexes, or historical snapshot comparisons.

---

## 3. Data Source Mapping for Academic Intelligence

To maintain architectural integrity and eliminate redundant queries or dual-source-of-truth problems, all intelligence metrics must trace directly to established domain anchors:

| Intelligence Domain | Required Metric | Primary Data Source | Computation & Aggregation Strategy |
|---|---|---|---|
| **Institutional Vital Signs (KPI)** | Total Active Students | `AcademicEnrollment` | `COUNT(*)` where `academic_year_id = ?` and `status = 'Aktif'` |
| | Class Count & Coverage | `Kelas` + `AcademicEnrollment` | Distinct count of classes with active enrollments |
| | Session Fulfillment Rate | `TeachingSession` | `(Completed Sessions / Planned Sessions) * 100` |
| | Institutional Attendance Index | `AttendanceRecord` | `(Hadir Records / Total Recorded Records) * 100` |
| | Evaluation Coverage Rate | `AcademicPerformanceSummary` | `(Count(status='Lengkap') / Total Active Enrollments) * 100` |
| | Institutional Average Score | `AcademicPerformanceSummary` | `AVG(average_score)` for active enrollments in academic year |
| **Attendance Patterns** | Status Breakdown | `AttendanceRecord` | Grouped `COUNT` by status (`Hadir`, `Izin`, `Sakit`, `Alpha`) |
| | Monthly/Weekly Trend | `AttendanceRecord` + `TeachingSession` | Time-series aggregation grouped by `DATE_FORMAT(session_date, '%Y-%m')` |
| | Class Attendance Ranking | `AttendanceRecord` + `AcademicEnrollment` | Grouped average attendance rate by `kelas_id` |
| | Low Attendance Alert List | `AcademicPerformanceSummary` | Active enrollments where `attendance_rate < 75.0%` |
| **Academic Performance Analytics** | Score Distribution (Grade Bands) | `AcademicPerformanceSummary` | Histogram grouping (`>=90`, `80-89`, `70-79`, `60-69`, `<60`) |
| | Subject Performance Indices | `StudentAssessmentScore` + `AssessmentComponent` + `TeachingAssignment` | Grouped `AVG(score)` by `mapel_id` and class level |
| | Assessment Component Coverage | `AssessmentComponent` + `TeachingAssignment` | Active assignments having >= 1 active assessment component |
| **Teacher Workload Intelligence** | Assignment Distribution | `TeachingAssignment` | Count of assigned subjects and classes per teacher (`user_id`) |
| | Scheduled Teaching Hours / Load | `ClassSchedule` | Total weekly schedule slots per teacher |
| | Session Execution Fulfillment | `TeachingSession` + `TeachingAssignment` | Ratio of completed vs planned sessions per teacher |
| | Grading Completeness | `StudentAssessmentScore` vs Expected Scores | Scored student components vs expected student counts per component |
| **Operational Health & Diagnostics** | Incomplete Class Sessions | `TeachingSession` | Planned sessions past current date not yet marked 'Completed' |
| | Unassigned Curriculum Slots | `Mapel` vs `TeachingAssignment` | Active mapels without an active `TeachingAssignment` in the year |
| | Uncomputed Performance Records | `AcademicEnrollment` vs `AcademicPerformanceSummary` | Active enrollments missing a performance summary or marked 'Kosong' |

---

## 4. Proposed Intelligence Architecture

### 4.1. Storage & Materialization Strategy: Hybrid Architecture
To balance real-time operational precision with high-performance dashboard rendering for large datasets, a **Hybrid Aggregation & Snapshot Architecture** is recommended:

1. **Materialized KPI Snapshot Table (`academic_kpi_snapshots`)**:
   - Stores frozen, point-in-time institutional intelligence records per academic year.
   - Enables fast historical comparisons (e.g., comparing Semester Ganjil 2024/2025 with Semester Ganjil 2023/2024) without executing resource-heavy cross-table calculations across historical years.
   - Allows administrators to explicitly trigger "Capture Snapshot" or "Refresh Metrics" for archival, auditing, or executive reporting.
   - Schema:
     - `id` (bigint unsigned)
     - `academic_year_id` (foreignId constrained to `academic_years`, cascade)
     - `snapshot_type` (string: `institutional_kpi`, `attendance_analytics`, `grade_distribution`, `teacher_workload`, `operational_health`)
     - `snapshot_date` (date)
     - `metrics` (json: flexible payload containing metrics, distributions, charts data, and breakdowns)
     - `source_version` (string, e.g. `v1.0`)
     - `captured_by` (foreignId constrained to `users`, nullable, nullOnDelete)
     - `timestamps`
     - Indexing: `['academic_year_id', 'snapshot_type', 'snapshot_date']`

2. **In-Memory / Application Cache Layer**:
   - For the currently active academic year, live computations are cached using Laravel's `Cache::remember` with deterministic cache keys (e.g. `academic_intelligence_kpi_{year_id}_{filters_hash}`) and a configurable TTL (e.g. 15-30 minutes).
   - Cache is automatically or manually flushed whenever operational data changes significantly or when an administrator clicks "Segarkan Data" (Refresh Metrics).

3. **Dynamic Service Calculation Engine**:
   - When live data is requested, queries are executed through specialized, indexed aggregation queries using Eloquent with subqueries and eager loading, preventing N+1 overhead.

### 4.2. Service Layer Structure
Create three dedicated, cohesive services in `app/Services/Academic/`:

```
app/Services/Academic/
├── AcademicIntelligenceService.php   # Orchestrator: high-level dashboard metrics, snapshots, trends
├── AcademicKpiService.php            # Domain KPI math: attendance rates, score bands, class health
└── TeacherWorkloadService.php        # Faculty analytics: assignment count, session fulfillment, grading
```

- **Reusability & DRY**: Reuses existing models (`AcademicPerformanceSummary`, `AttendanceRecord`, `StudentAssessmentScore`, `TeachingSession`, `TeachingAssignment`) without duplicating business rules.
- **Transaction Safety**: All snapshot creation and state mutations are wrapped in `DB::transaction()`.
- **Domain Exceptions**: Strict validation throws `DomainException` upon rule violations.

### 4.3. Controller & Presentation Layer Structure
- **Controller**: `app/Http/Controllers/Academic/AcademicIntelligenceController.php`
  - Thin controller following existing RESTful/resource conventions.
  - Endpoints:
    - `index(Request $request)`: Executive overview dashboard with KPI cards, attendance trends, grade distribution charts, and class health matrix.
    - `workload(Request $request)`: Dedicated teacher workload analytics with teacher load distribution table and session fulfillment metrics.
    - `subjectAnalytics(Request $request)`: Subject-level performance analysis, difficulty rankings, and component coverage.
    - `refresh(Request $request, AcademicYear $year)`: Clears cache and regenerates current operational metrics.
    - `snapshot(Request $request, AcademicYear $year)`: Captures and persists an immutable historical snapshot.
- **Blade Views**: `resources/views/pages/academic/intelligence/`
  - `index.blade.php`: Unified Intelligence Hub with filter controls (Academic Year, Class).
  - `workload.blade.php`: Faculty Workload & Session Execution Matrix.
  - `subjects.blade.php`: Subject Performance & Evaluation Coverage.
  - Utilizes existing layout (`layouts.app`), ApexCharts for interactive visualizations, and Bootstrap 5 responsive grids.

---

## 5. Risk Assessment & Mitigation

| Risk Area | Specific Failure Scenario | Impact | Mitigation Strategy |
|---|---|---|---|
| **Query Performance Degradation** | Multiple raw queries calculating percentages across hundreds of thousands of attendance and score rows on dashboard hit. | High latency (>3s), potential DB connection exhaustion. | 1. Utilize `AcademicPerformanceSummary` pre-aggregated table for student-level data.<br>2. Use indexed database queries (`academic_year_id`, `status`).<br>3. Implement Laravel Cache (`Cache::remember`) with explicit cache invalidation.<br>4. Provide snapshot capability for historical years. |
| **Data Synchronization Drift** | Separate workload tables becoming out of sync with actual sessions when sessions are completed or cancelled. | Inconsistent reports, administrative confusion. | Calculate teacher workload dynamically from `TeachingAssignment` and `TeachingSession` via optimized SQL queries rather than maintaining a redundant normalized sync table. |
| **Historical Year Pollution** | Analytics mixing data from previous academic years with current active year. | Distorted institutional metrics. | Enforce explicit `academic_year_id` scoping in every query, defaulting to the active academic year if unspecified. |
| **Boundary Creep (LMS / Rapor)** | UI inadvertently displaying student ranking, individual report cards, or student-facing score portals. | Violation of strict architectural boundary. | Enforce institutional aggregate display only; disallow student leaderboard rankings, do not introduce report card generation, and restrict access strictly to Administrator and Pengurus roles. |
| **Memory Exhaustion on Large Datasets** | Hydrating full Eloquent model collections into PHP memory for mathematical operations. | PHP fatal out-of-memory error. | Utilize database-level aggregations (`COUNT`, `AVG`, `SUM`, `groupBy`) and chunking rather than collecting all Eloquent records into PHP memory. |

---

## 6. Performance Considerations

1. **Database Indexes**:
   - Ensure the new `academic_kpi_snapshots` table is composite-indexed on `['academic_year_id', 'snapshot_type', 'snapshot_date']`.
   - Leverage existing indexes on `teaching_sessions(teaching_assignment_id, session_date, status)` and `attendance_records(teaching_session_id, status)`.
2. **Eager Loading Strategy**:
   - Strictly eager-load nested relationships when rendering tabular breakdowns:
     `with(['kelas', 'mapel', 'user', 'classSchedules', 'teachingSessions'])`.
3. **Database-Level Aggregation**:
   - Use raw DB expressions for time-series grouping (e.g. `DATE_FORMAT(session_date, '%Y-%m')`) and conditional sums (e.g. `SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END)`).
4. **Caching Policy**:
   - Default TTL of 30 minutes for dashboard metrics.
   - Immediate cache invalidation endpoint accessible to administrators to ensure on-demand freshness.

---

## 7. Security & Permission Considerations

1. **Spatie Permission Integration**:
   - Define dedicated permission group `intelligence` in `config/permission.php`:
     - `intelligence.index`: Access to academic intelligence dashboard.
     - `intelligence.dashboard`: Access to KPI overview and operational analytics.
     - `intelligence.analytics`: Access to performance distributions and subject analytics.
     - `intelligence.workload`: Access to teacher workload analysis.
     - `intelligence.snapshot`: Permission to capture or refresh historical snapshots.
2. **Role Access Policy**:
   - `Administrator`: Full access to all intelligence permissions.
   - `Pengurus`: Full access to all intelligence permissions.
   - `Keuangan`: Strictly denied.
   - `Santri`: Strictly denied.
3. **Input Validation**:
   - Validate all filter parameters (`academic_year_id`, `kelas_id`, `start_date`, `end_date`) using standard Laravel FormRequests or explicit controller validation.
4. **Data Protection**:
   - Intelligence dashboards aggregate data at institutional, class, subject, and teacher workload levels. No private santri biodata, parent contact information, or banking data is exposed.

---

## 8. Strict Boundary Confirmation

To preserve the clean architectural separation of the application, the following boundaries are formally reiterated and strictly enforced for Phase 5.8.7D:

| Feature / Capability | Allowed in Phase 5.8.7D? | Justification |
|---|---|---|
| **Institutional Academic KPIs** | ✅ **YES** | Core objective of intelligence layer. |
| **Macro Attendance Trends & Analytics** | ✅ **YES** | Essential for institutional operational monitoring. |
| **Teacher Workload & Delivery Tracking** | ✅ **YES** | Essential for administrative workload balance. |
| **Score Distribution & Subject Analytics** | ✅ **YES** | Aggregate grade bands and subject-level indicators. |
| **Materialized KPI Snapshots** | ✅ **YES** | Needed for historical comparison and performance caching. |
| **Report Card (*Rapor*) Generation** | ❌ **STRICTLY PROHIBITED** | Explicitly excluded; belongs to a future reporting/rapor phase. |
| **Student Ranking / Leaderboard** | ❌ **STRICTLY PROHIBITED** | Not aligned with institutional pedagogical goals and out of scope. |
| **Graduation Decision System** | ❌ **STRICTLY PROHIBITED** | Belongs to student lifecycle / alumni domain. |
| **Student Self-Service Portal** | ❌ **STRICTLY PROHIBITED** | Santri role has zero access to intelligence layer. |
| **LMS Modules / Course Management** | ❌ **STRICTLY PROHIBITED** | System is an academic information system, not an LMS. |
| **Exams, CBT, Question Banks** | ❌ **STRICTLY PROHIBITED** | Excluded from evaluation domain boundaries. |
| **Learning Materials / Syllabus Hosting** | ❌ **STRICTLY PROHIBITED** | Out of scope. |

---

## 9. Conclusion & Readiness Assessment

The repository is fully stabilized with **298 passing tests** and clean domain foundations. The architectural audit confirms that the system possesses all underlying relational structures required to power an **Academic Intelligence Layer** without breaking existing code or introducing unwarranted complexity.

With the proposed hybrid caching and snapshot architecture, the system will achieve high analytical capabilities, rapid dashboard response times, and full compliance with domain boundaries.
