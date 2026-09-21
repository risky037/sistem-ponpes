# Implementation Plan — Phase 5.8.7D: Laravel 12 Academic Intelligence Layer

**Target Phase**: 5.8.7D  
**Application**: Sistem Informasi Pondok Pesantren Fatimah Az-Zahra  
**Architecture Standard**: Laravel 12 Domain-Driven Modular / Thin Controller / Service Layer  
**Baseline Test Status**: 298 passed, 1107 assertions  
**Document Type**: Engineering Implementation Plan  

---

## 1. Architectural Summary & Objectives

Phase 5.8.7D introduces the **Academic Intelligence Layer** to transform the operational academic records of the pesantren into executive insights, operational diagnostics, and decision-support analytics.

### Key Deliverables
1. **Materialized Snapshot Storage (`academic_kpi_snapshots`)**: Immutable historical point-in-time KPI tracking per academic year.
2. **Hybrid Caching Engine**: High-speed retrieval of live metrics using Laravel Cache with on-demand refresh capability.
3. **Dedicated Domain Services**:
   - `AcademicIntelligenceService`: Orchestrator for dashboards, snapshots, and cache lifecycle.
   - `AcademicKpiService`: Institutional KPIs, attendance trends, grade distribution brackets, and class operational health metrics.
   - `TeacherWorkloadService`: Teacher assignment distribution, scheduled teaching hours, session fulfillment rates, and grading completeness.
4. **Thin Controller**: `AcademicIntelligenceController` with explicit Spatie permission gating.
5. **Modern Institutional UI**: Responsive Blade views utilizing Bootstrap 5 and ApexCharts for data visualizations.
6. **Feature Test Suite**: Comprehensive tests validating service logic, caching, authorization, and data aggregation with zero regressions.

---

## 2. Database Changes

### 2.1. Migration Name
`database/migrations/2024_06_01_000009_create_academic_kpi_snapshots_table.php`

### 2.2. Schema Design
```php
<?php

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academic_kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AcademicYear::class)->constrained()->cascadeOnDelete();
            $table->string('snapshot_type')->index(); // 'institutional_kpi', 'attendance_analytics', 'grade_distribution', 'teacher_workload', 'operational_health'
            $table->date('snapshot_date')->index();
            $table->json('metrics');
            $table->string('source_version', 20)->default('v1.0');
            $table->foreignIdFor(User::class, 'captured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['academic_year_id', 'snapshot_type', 'snapshot_date'], 'academic_kpi_snapshots_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_kpi_snapshots');
    }
};
```

---

## 3. Models

### 3.1. New Model: `App\Models\AcademicKpiSnapshot`
- **File**: `app/Models/AcademicKpiSnapshot.php`
- **Table**: `academic_kpi_snapshots`
- **Guarded**: `['id']`
- **Casts**:
  - `snapshot_date` => `date`
  - `metrics` => `array`
- **Constants**:
  - `TYPE_INSTITUTIONAL_KPI = 'institutional_kpi'`
  - `TYPE_ATTENDANCE_ANALYTICS = 'attendance_analytics'`
  - `TYPE_GRADE_DISTRIBUTION = 'grade_distribution'`
  - `TYPE_TEACHER_WORKLOAD = 'teacher_workload'`
  - `TYPE_OPERATIONAL_HEALTH = 'operational_health'`
- **Scopes**:
  - `forAcademicYear(int|AcademicYear $academicYear)`
  - `ofType(string $type)`
- **Relationships**:
  - `academicYear(): BelongsTo`
  - `capturedBy(): BelongsTo`

### 3.2. Updates to Existing Models
- `App\Models\AcademicYear`:
  - Add relation: `public function kpiSnapshots(): HasMany { return $this->hasMany(AcademicKpiSnapshot::class); }`
- `App\Models\User`:
  - Add relation: `public function capturedKpiSnapshots(): HasMany { return $this->hasMany(AcademicKpiSnapshot::class, 'captured_by'); }`

---

## 4. Service Layer Architecture

Create three dedicated services under `app/Services/Academic/`:

### 4.1. `AcademicKpiService`
**File**: `app/Services/Academic/AcademicKpiService.php`

**Responsibilities**:
1. `getInstitutionalKpis(AcademicYear $year): array`
   - Active student count, active class count.
   - Teaching assignment coverage.
   - Session delivery rate (`completed / (completed + planned) * 100`).
   - Global attendance rate (from `AcademicPerformanceSummary` & `AttendanceRecord`).
   - Global evaluation completion rate (`Lengkap` ratio).
   - Institutional average score.
2. `getAttendanceAnalytics(AcademicYear $year): array`
   - Overall status breakdown (`Hadir`, `Izin`, `Sakit`, `Alpha`).
   - Monthly time-series attendance trend.
   - Class-by-class attendance comparison.
   - Low attendance alert list (classes or enrollments with `< 75%` attendance).
3. `getGradeDistribution(AcademicYear $year): array`
   - Score bracket histogram:
     - `A (90.00 - 100.00)`: Mumtaz (Sangat Baik)
     - `B (80.00 - 89.99)`: Jayyid Jiddan (Baik)
     - `C (70.00 - 79.99)`: Jayyid (Cukup)
     - `D (60.00 - 69.99)`: Maqbul (Kurang)
     - `E (< 60.00)`: Rasib (Sangat Kurang)
   - Performance completion breakdown (`Lengkap`, `Sebagian`, `Kosong`).
4. `getSubjectIndicators(AcademicYear $year): array`
   - Subject average scores across classes.
   - Assessment component count per subject.
   - Low-performing subjects identification.
5. `getClassOperationalHealth(AcademicYear $year): Collection`
   - Per-class operational summary: enrolled students, scheduled sessions, attendance rate, average score, evaluation completion percentage.

### 4.2. `TeacherWorkloadService`
**File**: `app/Services/Academic/TeacherWorkloadService.php`

**Responsibilities**:
1. `getTeacherWorkloadOverview(AcademicYear $year): Collection`
   - Query all teachers (`User`) with teaching assignments in the academic year.
   - Metrics per teacher:
     - Number of distinct assigned subjects (`mapel_count`).
     - Number of distinct assigned classes (`kelas_count`).
     - Total weekly scheduled hours/slots from `ClassSchedule`.
     - Session statistics: planned count, completed count, cancelled count.
     - Session fulfillment percentage (`completed / (completed + planned) * 100`).
     - Grading completeness: total active assessment components vs scored components.
2. `getWorkloadDistributionStats(AcademicYear $year): array`
   - Summary vital signs: total active faculty, average classes per teacher, average sessions per teacher, top loaded vs least loaded faculty.

### 4.3. `AcademicIntelligenceService`
**File**: `app/Services/Academic/AcademicIntelligenceService.php`

**Responsibilities**:
1. `getDashboardOverview(AcademicYear $year, bool $forceFresh = false): array`
   - Wraps calls to `AcademicKpiService` and `TeacherWorkloadService`.
   - Utilizes `Cache::remember("academic_intelligence_kpi_{$year->id}", 1800, ...)` unless `$forceFresh` is true.
2. `captureSnapshot(AcademicYear $year, ?User $capturedBy = null): Collection<AcademicKpiSnapshot>`
   - Computes fresh metrics across all domains.
   - Atomically persists snapshots in `academic_kpi_snapshots` inside `DB::transaction()`.
3. `getHistoricalComparison(AcademicYear $currentYear, ?AcademicYear $previousYear = null): array`
   - Compares key metrics (attendance, average score, completion rate) between two academic years.
4. `purgeCache(AcademicYear $year): void`
   - Clears cached intelligence entries for the specified academic year.

---

## 5. Controller Layer

### 5.1. Controller: `App\Http\Controllers\Academic\AcademicIntelligenceController`
**File**: `app/Http/Controllers/Academic/AcademicIntelligenceController.php`

**Methods**:
1. `index(Request $request): View`
   - Selects target `AcademicYear` (requested or active default).
   - Retrieves executive KPI cards, charts payload (attendance trend, score distribution, session status), class health matrix.
   - Returns view: `pages.academic.intelligence.index`.
2. `workload(Request $request): View`
   - Retrieves teacher workload metrics and distribution stats for the selected year.
   - Returns view: `pages.academic.intelligence.workload`.
3. `subjectAnalytics(Request $request): View`
   - Retrieves subject-level performance metrics, component coverage, and difficulty indicators.
   - Returns view: `pages.academic.intelligence.subjects`.
4. `refresh(Request $request, AcademicYear $year): RedirectResponse`
   - Purges cache and regenerates live data for `$year`.
   - Flashes success message.
5. `snapshot(Request $request, AcademicYear $year): RedirectResponse`
   - Captures and stores point-in-time `AcademicKpiSnapshot`.
   - Flashes success message.

---

## 6. Routes & Authorization

### 6.1. Route Definitions
Add to `routes/web.php` inside `Route::middleware(['auth'])->group(...)` and `Route::group(['middleware' => ['role:Administrator|Pengurus']], ...)`:

```php
// academic intelligence (intelijen & analitik akademik)
Route::controller(AcademicIntelligenceController::class)->prefix('academic/intelligence')->as('academic.intelligence.')->group(function () {
    Route::get('/', 'index')->name('index')->middleware('can:intelligence.index');
    Route::get('/workload', 'workload')->name('workload')->middleware('can:intelligence.workload');
    Route::get('/subjects', 'subjectAnalytics')->name('subjects')->middleware('can:intelligence.analytics');
    Route::post('/year/{year}/refresh', 'refresh')->name('refresh')->middleware('can:intelligence.dashboard');
    Route::post('/year/{year}/snapshot', 'snapshot')->name('snapshot')->middleware('can:intelligence.snapshot');
});
```

### 6.2. Permissions Configuration
In `config/permission.php`:
Under `'admin'` and `'pengurus'`:
```php
'intelligence' => [
    'intelligence.index',
    'intelligence.dashboard',
    'intelligence.analytics',
    'intelligence.workload',
    'intelligence.snapshot',
],
```
Under `'keuangan'` and `'santri'`:
Empty / omitted (strictly denied).

Update `RolePermissionSeeder` and run `php artisan db:seed --class=RolePermissionSeeder`.

---

## 7. UI / UX Design & Components

### 7.1. Navigation Menu Updates
In `resources/views/components/navbar.blade.php`:
Add an **Intelijen Akademik** entry with icon `<i class="bx bx-analyse"></i>` or `<i class="bx bx-line-chart"></i>` under the Academic section:
```blade
<li class="{{ request()->routeIs('academic.intelligence.*') ? 'mm-active' : '' }}">
    <a class="has-arrow" href="javascript:;">
        <div class="parent-icon icon-color-4"><i class="bx bx-analyse"></i></div>
        <div class="menu-title">Intelijen Akademik</div>
    </a>
    <ul>
        <li class="{{ request()->routeIs('academic.intelligence.index') ? 'mm-active' : '' }}">
            <a href="{{ route('academic.intelligence.index') }}"><i class="bx bx-right-arrow-alt"></i>Dashboard KPI</a>
        </li>
        <li class="{{ request()->routeIs('academic.intelligence.workload') ? 'mm-active' : '' }}">
            <a href="{{ route('academic.intelligence.workload') }}"><i class="bx bx-right-arrow-alt"></i>Beban Mengajar Guru</a>
        </li>
        <li class="{{ request()->routeIs('academic.intelligence.subjects') ? 'mm-active' : '' }}">
            <a href="{{ route('academic.intelligence.subjects') }}"><i class="bx bx-right-arrow-alt"></i>Analisis Mapel</a>
        </li>
    </ul>
</li>
```

### 7.2. Blade Views
Located in `resources/views/pages/academic/intelligence/`:
1. `index.blade.php`:
   - Academic year selector dropdown with quick switch and "Segarkan Data" / "Simpan Snapshot" buttons.
   - Institutional KPI Cards: Active Students, Session Delivery Rate, Attendance Index, Evaluation Completeness, Institutional Average Score.
   - Interactive Visualizations using ApexCharts:
     - Area Chart: Monthly Attendance Trend.
     - Bar / Column Chart: Grade Bracket Distribution (Mumtaz, Jayyid Jiddan, Jayyid, Maqbul, Rasib).
     - Radial / Donut Chart: Session Status Breakdown (Completed, Planned, Cancelled).
     - Column Chart: Class Attendance Comparison.
   - Class Operational Health Matrix Table:
     - Class Name, Wali Kelas, Total Students, Attendance Rate, Average Score, Evaluation Completion Status Badge.
2. `workload.blade.php`:
   - Workload Summary Banner & Faculty Load KPIs (Total Asatidz, Average Load, Full-delivery Rate).
   - Faculty Workload Table:
     - Teacher Name, Assigned Subjects (count & badges), Assigned Classes (count & badges), Scheduled Hours/Week, Session Delivery Progress Bar, Grading Completion Badge.
3. `subjects.blade.php`:
   - Subject Performance Table:
     - Subject Name, Class Level, Assigned Teacher, Average Score, Assessment Components, Operational Health Status.

---

## 8. Feature Testing Strategy

Create four comprehensive test suites under `tests/Feature/Academic/`:

1. `AcademicKpiServiceTest.php`:
   - Computes accurate institutional KPIs for active academic year.
   - Accurately generates score bracket distribution.
   - Computes monthly attendance trends and class rankings.
   - Handles edge cases (year with zero data, year with partial data).
2. `TeacherWorkloadServiceTest.php`:
   - Computes teacher assignments, classes, and scheduled slots correctly.
   - Accurately computes session fulfillment percentages (planned vs completed vs cancelled).
   - Computes grading completion status.
   - Handles teachers with no assignments or no sessions.
3. `AcademicIntelligenceServiceTest.php`:
   - Caching behavior (stores in cache, retrieves from cache, invalidates on purge).
   - Materialized snapshot creation and retrieval from `academic_kpi_snapshots`.
   - Historical year comparison calculations.
4. `AcademicIntelligenceControllerTest.php`:
   - Access control:
     - Administrator: 200 OK.
     - Pengurus: 200 OK.
     - Keuangan: 403 Forbidden.
     - Santri: 403 Forbidden.
     - Guest: 302 Redirect to login.
   - Actions:
     - `index` loads correctly with filter.
     - `workload` loads correctly.
     - `subjects` loads correctly.
     - `refresh` flushes cache and redirects with success toast.
     - `snapshot` creates record in database and redirects with success toast.

**Regression Requirement**:
- All 298 existing tests must continue to pass with 100% success rate.
- New total test count will exceed 320 tests.

---

## 9. Migration & Rollout Strategy

1. **Step 1: Database Migration**:
   - Run `php artisan migrate` to create `academic_kpi_snapshots`.
2. **Step 2: Permission Updates**:
   - Update `config/permission.php` and run `RolePermissionSeeder`.
3. **Step 3: Models & Services Implementation**:
   - Implement `AcademicKpiSnapshot` model.
   - Implement `AcademicKpiService`, `TeacherWorkloadService`, and `AcademicIntelligenceService`.
4. **Step 4: Controller & Routes Implementation**:
   - Implement `AcademicIntelligenceController` and register routes in `routes/web.php`.
5. **Step 5: View Layer Implementation**:
   - Create Blade views in `resources/views/pages/academic/intelligence/`.
   - Update navbar menu in `resources/views/components/navbar.blade.php`.
6. **Step 6: Automated Testing & Verification**:
   - Run unit/feature tests via `php artisan test`.
   - Verify code formatting and linting.
   - Perform end-to-end smoke test on browser.
