# Release Notes — Phase 5.8.7D: Laravel 12 Academic Intelligence Layer

**Release Tag**: `phase-5.8.7D-completed`  
**Date**: 2026-09-21  
**Branch**: `develop`  
**System Baseline**: Laravel 12.x, PHP 8.4+, MariaDB 10.4.32, Bootstrap 5.1.3, ApexCharts, Vite 4.4.9  
**Test Status**: 318 passed (1226 assertions), 100% green  

---

## 1. Release Overview

Phase 5.8.7D introduces the **Academic Intelligence Layer** for the **Sistem Informasi Pondok Pesantren Fatimah Az-Zahra**.

Building upon the previous operational and aggregation foundations (Phases 5.8.7C-7 through 5.8.7C-12), this release equips institutional administrators and leadership (*Dewan Asatidz / Pengurus*) with macro-level academic analytics, teacher workload intelligence, class operational health diagnostics, flexible score distributions, and point-in-time snapshot archiving.

### Core Capabilities Delivered:
1. **Academic KPI Foundation**: Real-time institutional vital signs including active enrollment counts, class coverage, session delivery fulfillment rate, institutional attendance index, evaluation coverage rate, and institutional average score.
2. **Institutional Attendance Analytics**: Monthly time-series attendance trends, attendance status composition (Hadir, Izin, Sakit, Alpha), cross-class attendance comparisons, and automated identification of students with attendance below 75%.
3. **Flexible Grade Distribution Analytics**: Dynamic score distribution brackets (avoiding rigid labels or student ranking) and evaluation completion status tracking (Lengkap, Sebagian, Kosong).
4. **Teacher Workload Intelligence**: Faculty workload distribution metrics tracking assigned subjects, assigned classes, scheduled weekly slots, session fulfillment ratios (planned vs. completed vs. cancelled), and grading timeliness.
5. **Class Operational Health Diagnostics**: Consolidated operational health matrix summarizing student counts, session delivery, attendance rates, average scores, and evaluation completion per class.
6. **Historical Snapshot Archiving (`academic_kpi_snapshots`)**: Materialized point-in-time KPI records per academic year enabling frozen historical archiving and longitudinal comparison without heavy re-computations.
7. **Hybrid Caching Architecture**: In-memory caching (`Cache::remember`) with a 30-minute TTL for live metrics with on-demand manual refresh capability.
8. **Strict LMS Boundary Enforcement**: Zero implementation of report cards (*rapor*), ranking (*peringkat*), graduation decisions, student self-service views, LMS course modules, or CBT/exam question banks.

---

## 2. Database Changes

Migration: `database/migrations/2024_06_01_000009_create_academic_kpi_snapshots_table.php`

### `academic_kpi_snapshots` (Materialized Snapshot Storage)
- **Columns**:
  - `id` (bigint unsigned, primary key)
  - `academic_year_id` (foreignId constrained to `academic_years`, `restrictOnDelete`)
  - `snapshot_type` (string: `institutional_kpi`, `attendance_analytics`, `grade_distribution`, `teacher_workload`, `operational_health`)
  - `snapshot_date` (date)
  - `metrics` (json: structured payload containing metrics, distributions, and analytics)
  - `source_version` (string, default `v1.0`)
  - `captured_by` (foreignId constrained to `users`, nullable, `nullOnDelete`)
  - `created_at`, `updated_at` (timestamps)
- **Indexes**:
  - Composite index: `['academic_year_id', 'snapshot_type', 'snapshot_date']`

---

## 3. Domain Model Layer

### New Model
- **`App\Models\AcademicKpiSnapshot`**:
  - Constants for snapshot types (`TYPE_INSTITUTIONAL_KPI`, `TYPE_ATTENDANCE_ANALYTICS`, `TYPE_GRADE_DISTRIBUTION`, `TYPE_TEACHER_WORKLOAD`, `TYPE_OPERATIONAL_HEALTH`).
  - Casts: `snapshot_date` => `date`, `metrics` => `array`.
  - Scopes: `forAcademicYear()`, `ofType()`.
  - Relationships: `belongsTo(AcademicYear::class)`, `belongsTo(User::class, 'captured_by')`.

### Model Enhancements
- **`App\Models\AcademicYear`**: Added `kpiSnapshots()` and `kpi_snapshots()` (`hasMany`).
- **`App\Models\User`**: Added `capturedKpiSnapshots()` and `captured_kpi_snapshots()` (`hasMany`).

---

## 4. Service Layer Pattern

Implemented under `app/Services/Academic/`:

1. **`AcademicKpiService`**:
   - `getInstitutionalKpis(AcademicYear $year): array`
   - `getAttendanceAnalytics(AcademicYear $year): array`
   - `getGradeDistribution(AcademicYear $year, ?array $customBands = null): array`
   - `getSubjectIndicators(AcademicYear $year): Collection`
   - `getClassOperationalHealth(AcademicYear $year): Collection`

2. **`TeacherWorkloadService`**:
   - `getTeacherWorkloadOverview(AcademicYear $year): Collection`
   - `getWorkloadDistributionStats(AcademicYear $year): array`

3. **`AcademicIntelligenceService`**:
   - `getDashboardOverview(AcademicYear $year, bool $forceFresh = false): array`
   - `captureSnapshot(AcademicYear $year, ?User $capturedBy = null): Collection`
   - `getHistoricalSnapshots(AcademicYear $year): Collection`
   - `getHistoricalComparison(AcademicYear $currentYear, ?AcademicYear $previousYear = null): array`
   - `purgeCache(AcademicYear $year): void`

---

## 5. Controller & Presentation Layer

### Controller & Validation
- **`App\Http\Requests\Academic\AcademicIntelligenceFilterRequest`**: FormRequest validation for filter parameters (`academic_year_id`, `compare_year_id`, `kelas_id`).
- **`App\Http\Controllers\Academic\AcademicIntelligenceController`**:
  - `index(AcademicIntelligenceFilterRequest $request)`: Main dashboard view with KPI cards, charts, and class health matrix.
  - `workload(AcademicIntelligenceFilterRequest $request)`: Dedicated teacher workload view with faculty distribution table.
  - `subjectAnalytics(AcademicIntelligenceFilterRequest $request)`: Subject-level performance analysis.
  - `refresh(Request $request, AcademicYear $year)`: Purges cache and recalculates metrics on demand.
  - `snapshot(Request $request, AcademicYear $year)`: Captures immutable historical snapshot.

### Blade Views (`resources/views/pages/academic/intelligence/`)
- `index.blade.php`: Executive dashboard with KPI cards, ApexCharts visualizations (Monthly Attendance Trend, Session Status Donut, Grade Distribution Histogram, Class Attendance Comparison), and Class Operational Health Matrix.
- `workload.blade.php`: Faculty workload cards and distribution table with session fulfillment progress bars.
- `subjects.blade.php`: Subject achievement indicators and component coverage matrix.
- `resources/views/components/navbar.blade.php`: Added "Intelijen Akademik" menu section with sub-links.

---

## 6. Security & Permission Matrix

Configured in `config/permission.php`:
- **Permission Group**: `intelligence`
  - `intelligence.index`
  - `intelligence.dashboard`
  - `intelligence.analytics`
  - `intelligence.workload`
  - `intelligence.snapshot`

### Role Authorization
- `Administrator`: Full access (Allowed)
- `Pengurus`: Full access (Allowed)
- `Keuangan`: Access denied (HTTP 403 Forbidden)
- `Santri`: Access denied (HTTP 403 Forbidden)
- `Guest`: Redirected to login (HTTP 302)

---

## 7. Quality Assurance & Testing Verification

Executed verification suite:
- `php artisan optimize:clear`: Passed (All caches cleared)
- `php artisan migrate:fresh --seed`: Passed (All 22 migrations applied, seeders executed)
- `php artisan test`: Passed (**318 tests passed, 1226 assertions**)
  - New test suites:
    - `AcademicKpiServiceTest` (5 tests)
    - `TeacherWorkloadServiceTest` (2 tests)
    - `AcademicIntelligenceServiceTest` (4 tests)
    - `AcademicIntelligenceControllerTest` (9 tests)
- `npm run build`: Passed (Vite build successful, assets packaged)
- `composer run lint:check`: Passed (`./vendor/bin/pint --test` clean)
- `composer audit`: Passed (0 security vulnerability advisories found)
