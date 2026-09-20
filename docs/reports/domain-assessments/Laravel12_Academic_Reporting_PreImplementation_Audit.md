# Laravel 12 Academic Reporting & Analytics — Pre-Implementation Audit

**Phase**: 5.8.7C-11
**Date**: 2026-09-20
**Baseline Tag**: phase-5.8.7C-10-completed (b1935242)
**Tests baseline**: 266 passed, 979 assertions

---

## 1. Domain Context

### Objective
Build an **academic aggregation foundation** that computes, stores, and displays
summary performance metrics per student per academic year — derived from existing
`AttendanceRecord` and `StudentAssessmentScore` data — without implementing:
report cards, ranking, graduation, student portal, LMS dashboard, or exam system.

---

## 2. Current Domain Graph

```
academic_years
    ├── academic_enrollments  (anchor: santri × academic_year × kelas)
    │       ├── attendance_records         (per teaching_session)
    │       └── student_assessment_scores  (per assessment_component)
    ├── assessment_definitions
    │       └── assessment_components      (per teaching_assignment)
    └── teaching_assignments
            ├── assessment_components
            └── teaching_sessions
                    └── attendance_records
```

---

## 3. Affected Models

### 3.1 AcademicYear
- **File**: `app/Models/AcademicYear.php`
- **Key fields**: `id`, `name`, `semester`, `start_date`, `end_date`, `is_active`
- **Relations**: `academic_enrollments`, `teaching_assignments`, `assessment_definitions`,
  `class_schedules`, `calendar_events`
- **Scope**: `active()`

### 3.2 AcademicEnrollment ← **Primary Anchor**
- **File**: `app/Models/AcademicEnrollment.php`
- **Key fields**: `id`, `academic_year_id`, `santri_id`, `kelas_id`, `status`, `enrolled_at`
- **Statuses**: `Aktif`, `Nonaktif`, `Lulus`, `Pindah`
- **Relations**: `attendanceRecords()`, `studentAssessmentScores()`, `academicYear()`, `santri()`, `kelas()`
- **Unique constraint**: `(academic_year_id, santri_id)` — one enrollment per student per year

### 3.3 AttendanceRecord
- **File**: `app/Models/AttendanceRecord.php`
- **Key fields**: `id`, `teaching_session_id`, `academic_enrollment_id`, `status`, `marked_at`, `marked_by`
- **Statuses**: `Hadir`, `Izin`, `Sakit`, `Alpha`
- **Scopes**: `present()`, `absent()`, `forSession()`
- **Unique constraint**: `(teaching_session_id, academic_enrollment_id)`

### 3.4 StudentAssessmentScore
- **File**: `app/Models/StudentAssessmentScore.php`
- **Key fields**: `id`, `assessment_component_id`, `academic_enrollment_id`, `score` (decimal 5,2),
  `graded_by`, `graded_at`
- **Unique constraint**: `(assessment_component_id, academic_enrollment_id)`
- **Score range**: 0.00–100.00

### 3.5 TeachingAssignment
- **File**: `app/Models/TeachingAssignment.php`
- **Key fields**: `id`, `kelas_id`, `mapel_id`, `user_id`, `academic_year_id`, `status`
- **Statuses**: `Aktif`, `Nonaktif`
- **Relations**: `assessmentComponents()`, `teachingSessions()`

---

## 4. Aggregation Data Requirements

### 4.1 Attendance Summary (per enrollment)

Computable from `attendance_records` JOINed to `academic_enrollment_id`:

| Metric | Source |
|--------|--------|
| `total_sessions` | COUNT all `teaching_session_id` records for enrollment's kelas |
| `present_count` | COUNT where status = `Hadir` |
| `excused_count` | COUNT where status = `Izin` |
| `sick_count` | COUNT where status = `Sakit` |
| `absent_count` | COUNT where status = `Alpha` |
| `attendance_rate` | `present_count / total_sessions * 100` |

### 4.2 Assessment Summary (per enrollment)

Computable from `student_assessment_scores` → `assessment_components` → `assessment_definitions`:

| Metric | Source |
|--------|--------|
| `scored_components` | COUNT scored components |
| `total_components` | COUNT active components for student's assignment |
| `weighted_score_sum` | SUM(score × component.weight) |
| `total_weight` | SUM(component.weight) for scored components |
| `average_score` | weighted_score_sum / total_weight |

### 4.3 Computed Status
| Status | Condition |
|--------|-----------|
| `Lengkap` | All components scored AND attendance_rate ≥ 75% |
| `Sebagian` | Some components scored OR sessions recorded but incomplete |
| `Kosong` | No scores AND no attendance records |

---

## 5. Migration Dependency Order

```
1. academic_years                    (2024_06_01_000001)
2. academic_enrollments              (2024_06_01_000001)
3. teaching_assignments              (curriculum migration)
4. class_schedules + teaching_sessions (2024_06_01_000004)
5. attendance_records                (2024_06_01_000005)
6. assessment_definitions + components + scores (2024_06_01_000006)
7. academic_performance_summaries    ← NEW (2024_06_01_000007)
```

---

## 6. Service Pattern Observed

All existing services follow this pattern:
- **Namespace**: `App\Services\Academic`
- **Validation**: `DomainException` thrown for business rule violations
- **Writes**: wrapped in `DB::transaction()`
- **No direct Santri relation** (always through `AcademicEnrollment`)

The `AcademicPerformanceService` must follow the same conventions.

---

## 7. Existing Permission Structure (config/permission.php)

Relevant roles: `Administrator`, `Pengurus`, `Santri`

Pattern observed:
```php
'assessment' => [
    'assessment.definition.index', ...
],
'attendance' => [
    'attendance.index', ...
],
```

New permissions will follow:
```
performance.index
performance.show
performance.generate
```

---

## 8. UI Pattern Observed

- All academic views use Blade components
- No dedicated `resources/views/academic/` directory (views are in role-namespaced directories or top-level)
- Controllers located in `app/Http/Controllers/Academic/`
- Routes registered in `routes/web.php`

---

## 9. Boundary Confirmation

The following features are **STRICTLY EXCLUDED** from Phase 5.8.7C-11:
- ❌ Report cards (rapor)
- ❌ Ranking / peringkat
- ❌ Graduation (kelulusan)
- ❌ Student portal (santri self-view)
- ❌ LMS dashboard
- ❌ Exam / CBT system
- ❌ Learning materials

The `academic_performance_summaries` table will be a **computed cache** only.
No new operational transactions are introduced.

---

## 10. Risk Assessment

| Risk | Severity | Mitigation |
|------|----------|-----------|
| `weighted_score_sum / total_weight` division by zero | Medium | Guard: if `total_weight = 0`, set `average_score = null` |
| Stale summary after new score/attendance | Medium | `generateSummary()` called explicitly; no auto-trigger |
| Enrollment without any scores/sessions | Low | `computation_status = Kosong` is valid state |
| Migration order (FK to academic_enrollments) | Low | Already established; new migration will be `000007` |
