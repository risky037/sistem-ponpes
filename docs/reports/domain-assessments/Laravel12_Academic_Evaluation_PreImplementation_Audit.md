# Pre-Implementation Audit — Laravel 12 Academic Evaluation Domain Foundation
**Phase**: 5.8.7C-10  
**Date**: 2026-09-20  
**Baseline**: Laravel 12.69.2, PHP 8.4.16, MariaDB 10.4.32  
**Previous Phase Tag**: `phase-5.8.7C-9-completed`  
**Test Baseline**: 230 passed, 889 assertions  

---

## 1. Audit Scope

This document audits the current academic domain implementation to identify integration points, migration dependency order, risks, and historical data protection strategy for the **Academic Evaluation Domain** comprising:

- `assessment_definitions` — per-academic-year evaluation scheme definitions
- `assessment_components` — per-teaching-assignment component breakdowns
- `student_assessment_scores` — per-enrollment score records

**Strict LMS Boundary**: No report cards, graduation, ranking, exams, CBT, question banks, or learning materials.

---

## 2. Existing Domain Architecture

### 2.1. Confirmed Migration Sequence (Current)

| # | Migration File | Tables Created | Depends On |
|---|---|---|---|
| 01 | `0001_01_01_000000_create_users_table` | `users` | — |
| 02 | `2023_08_29_075200_create_kelas_table` | `kelas` | — |
| 03 | `2023_08_29_075223_create_santris_table` | `santris` | `users`, `kelas` |
| 04 | `2023_09_12_212807_create_permission_tables` | `roles`, `permissions`, pivot tables | `users` |
| 05 | `2024_06_01_000001_create_academic_foundation_tables` | `academic_years`, `student_batches`, `academic_enrollments` | `kelas`, `santris` |
| 06 | `2024_06_01_000002_create_wali_kelas_assignments_table` | `wali_kelas_assignments` | `kelas`, `users`, `academic_years` |
| 07 | `2024_06_01_000003_create_curriculum_domain_tables` | `mapels`, `teaching_assignments` | `kelas`, `mapels`, `users`, `academic_years` |
| 08 | `2024_06_01_000004_create_academic_operational_tables` | `class_schedules`, `academic_calendar_events`, `teaching_sessions` | `kelas`, `teaching_assignments`, `academic_years` |
| 09 | `2024_06_01_000005_create_attendance_records_table` | `attendance_records` | `teaching_sessions`, `academic_enrollments`, `users` |

**New migration (`000006`) must come after `000005`.**

### 2.2. Current Model Inventory

| Model | Table | Key Relations | Status |
|---|---|---|---|
| `AcademicYear` | `academic_years` | `HasMany`: enrollments, teaching_assignments, class_schedules, calendar_events, wali_kelas_assignments | ✅ Complete |
| `AcademicEnrollment` | `academic_enrollments` | `BelongsTo`: academic_year, santri, kelas; `HasMany`: attendance_records | ✅ Audit point |
| `TeachingAssignment` | `teaching_assignments` | `BelongsTo`: kelas, mapel, user, academic_year; `HasMany`: class_schedules, teaching_sessions | ✅ Anchor |
| `TeachingSession` | `teaching_sessions` | `BelongsTo`: teaching_assignment, class_schedule; `HasMany`: attendance_records | ✅ Operational |
| `AttendanceRecord` | `attendance_records` | `BelongsTo`: teaching_session, academic_enrollment, user (marker) | ✅ Phase 5.8.7C-9 |
| `Mapel` | `mapels` | `BelongsTo`: kelas; `HasMany`: teaching_assignments | ✅ Complete |
| `Kelas` | `kelas` | `HasMany`: academic_enrollments, wali_kelas_assignments, mapels, teaching_assignments, class_schedules | ✅ Complete |
| `User` | `users` | `HasOne`: santri; `HasMany`: wali_kelas_assignments, teaching_assignments | ✅ Complete |

### 2.3. Schema Detail — Evaluated Entities

**`academic_enrollments`**: Anchor for student score records.
```
id, academic_year_id, santri_id, kelas_id, status (Aktif/Nonaktif/Lulus/Pindah), enrolled_at, notes, timestamps
UNIQUE(academic_year_id, santri_id)
```

**`teaching_assignments`**: Anchor for assessment components.
```
id, kelas_id, mapel_id, user_id, academic_year_id, status (Aktif/Nonaktif), notes, timestamps
UNIQUE(kelas_id, mapel_id, academic_year_id)
```

**`academic_years`**: Anchor for assessment definitions.
```
id, name, semester, start_date, end_date, is_active, timestamps
UNIQUE(name, semester)
```

### 2.4. Existing Service Patterns

All services in `app/Services/Academic/` follow the same structure:
- Constructor-free (no DI for models); accept Eloquent model instances as typed parameters
- Use `DomainException` for domain rule violations
- Use `DB::transaction()` for atomic operations
- Validated by typed PHPDoc on public methods

**Observed pattern**: `AttendanceService` is the most recent and fullest reference.

### 2.5. Existing Permission Structure

Permissions follow two naming formats in `config/permission.php`:
- **Dotted** (newer, since Phase 5.8.7C-9): `attendance.index`, `attendance.create`, `attendance.update`
- **Space-separated** (legacy): `attendance index`, `attendance create`, `attendance update`

Both formats are registered together per permission group. **Phase 5.8.7C-10 must adopt the dotted format** for new evaluation permissions.

Evaluation permissions should be in:
- `config('permission.admin')` → `assessment` group
- `config('permission.pengurus')` → `assessment` group  
- Santri role → **read-only `assessment.index`** (santri should see their own scores; to be confirmed in Q2)

### 2.6. Existing UI Component Patterns (Pesantren Green Design System)

Used across all academic Blade views:
- `<x-breadcrumb>` — page navigation context
- `<x-card-toolbar>` — card header with title and action slot
- `<x-status-badge>` — reusable status indicator
- `<x-delete-modal>` — soft-delete/deactivate confirmation modal
- `<x-modal-form>` — general-purpose AJAX-compatible form modal
- DataTables server-side via Yajra with `Ajax` calls including filter dropdowns
- Toastr for success/error flash notifications

---

## 3. Proposed Entities

### 3.1. `assessment_definitions`

**Purpose**: Define named assessment categories for a given academic year (e.g., "Ujian Tengah Semester", "Tugas Harian", "Praktik").

| Field | Type | Notes |
|---|---|---|
| `id` | bigint PK | — |
| `academic_year_id` | FK → `academic_years.id` | `restrictOnDelete` |
| `name` | varchar | e.g., "UTS", "UAS", "Tugas", "Praktik" |
| `type` | varchar | Enum-like: `UTS`, `UAS`, `Tugas`, `Praktik`, `Lainnya` |
| `weight` | unsignedTinyInteger | Percentage weight (1–100) |
| `is_active` | boolean | default true |
| `timestamps` | — | — |

**Constraints**: No unique composite required at this layer (multiple definitions of same type may exist per year with different names). `name` unique within `academic_year_id` could be enforced: `UNIQUE(academic_year_id, name)`.

### 3.2. `assessment_components`

**Purpose**: Link an `AssessmentDefinition` to a specific `TeachingAssignment`, with a component-level weight override.

| Field | Type | Notes |
|---|---|---|
| `id` | bigint PK | — |
| `teaching_assignment_id` | FK → `teaching_assignments.id` | `restrictOnDelete` |
| `assessment_definition_id` | FK → `assessment_definitions.id` | `restrictOnDelete` |
| `weight` | unsignedTinyInteger | Component-level weight within this assignment (1–100) |
| `timestamps` | — | — |

**Constraints**: `UNIQUE(teaching_assignment_id, assessment_definition_id)` → one component per definition per assignment.

### 3.3. `student_assessment_scores`

**Purpose**: Store individual student scores per assessment component, linked to their academic enrollment (NOT to the santri record directly).

| Field | Type | Notes |
|---|---|---|
| `id` | bigint PK | — |
| `assessment_component_id` | FK → `assessment_components.id` | `restrictOnDelete` |
| `academic_enrollment_id` | FK → `academic_enrollments.id` | `restrictOnDelete` |
| `score` | decimal(5,2) | Range 0.00–100.00 |
| `notes` | text nullable | Grader notes |
| `graded_by` | FK → `users.id` nullable | `restrictOnDelete` — accountability |
| `graded_at` | timestamp nullable | When score was recorded |
| `timestamps` | — | — |

**Constraints**: `UNIQUE(assessment_component_id, academic_enrollment_id)` → one score per student per component.

---

## 4. Dependency Graph (New)

```
academic_years
    └── assessment_definitions
            └── assessment_components
                    ├── FK: teaching_assignments (existing)
                    └── student_assessment_scores
                            ├── FK: academic_enrollments (existing)
                            └── FK: users (graded_by, nullable)
```

### 4.1. Migration Sequence

| Migration File | Action |
|---|---|
| `2024_06_01_000006_create_evaluation_domain_tables` | Creates `assessment_definitions`, `assessment_components`, `student_assessment_scores` in a single migration file |

All three evaluation tables can be created in a single migration file since they depend only on existing tables (+ each other sequentially). Drop order in `down()` must be reversed: `student_assessment_scores`, `assessment_components`, `assessment_definitions`.

---

## 5. Proposed Code Artifacts

### 5.1. Models

| Model | Table | Namespace |
|---|---|---|
| `AssessmentDefinition` | `assessment_definitions` | `App\Models` |
| `AssessmentComponent` | `assessment_components` | `App\Models` |
| `StudentAssessmentScore` | `student_assessment_scores` | `App\Models` |

**Model relationships to add**:
- `AcademicYear` → `HasMany(AssessmentDefinition::class)`
- `TeachingAssignment` → `HasMany(AssessmentComponent::class)`
- `AcademicEnrollment` → `HasMany(StudentAssessmentScore::class)`
- `User` → `HasMany(StudentAssessmentScore::class, 'graded_by')`

### 5.2. Service

`app/Services/Academic/AssessmentService.php`

Planned methods:
- `createDefinition(AcademicYear, array $data): AssessmentDefinition`
- `updateDefinition(AssessmentDefinition, array $data): AssessmentDefinition`
- `deactivateDefinition(AssessmentDefinition): AssessmentDefinition`
- `createComponent(TeachingAssignment, AssessmentDefinition, int $weight): AssessmentComponent`
- `recordScore(AssessmentComponent, AcademicEnrollment, float $score, ?User $grader, ?string $notes): StudentAssessmentScore`
- `updateScore(StudentAssessmentScore, float $score, ?User $grader, ?string $notes): StudentAssessmentScore`

Domain rules enforced:
- Definition must belong to same academic year as the teaching assignment's academic year (when linking component)
- Score must be in range 0.00–100.00
- Enrollment class must match the teaching assignment's class
- Enrollment academic year must match the teaching assignment's academic year (via definition)
- Cannot record score for a non-Aktif enrollment (consistent with attendance domain)

### 5.3. Controller

`app/Http/Controllers/Academic/AssessmentController.php`

Routes (under `role:Administrator|Pengurus` middleware, consistent with other academic routes):

| Method | URI | Action | Name |
|---|---|---|---|
| GET | `/assessment/definition` | `indexDefinition` | `assessment.definition.index` |
| POST | `/assessment/definition` | `storeDefinition` | `assessment.definition.store` |
| PATCH | `/assessment/definition/{definition}` | `updateDefinition` | `assessment.definition.update` |
| DELETE | `/assessment/definition/{definition}` | `destroyDefinition` | `assessment.definition.destroy` |
| GET | `/assessment/component` | `indexComponent` | `assessment.component.index` |
| POST | `/assessment/component` | `storeComponent` | `assessment.component.store` |
| DELETE | `/assessment/component/{component}` | `destroyComponent` | `assessment.component.destroy` |
| GET | `/assessment/score/{component}` | `indexScore` | `assessment.score.index` |
| POST | `/assessment/score/{component}` | `storeScore` | `assessment.score.store` |
| PATCH | `/assessment/score/record/{score}` | `updateScore` | `assessment.score.update` |

### 5.4. Views

`resources/views/pages/academic/assessment/`
- `definition/index.blade.php` — DataTable of assessment definitions, with filter by academic year
- `component/index.blade.php` — DataTable of components, with filter by year and mapel
- `score/manage.blade.php` — Score entry sheet per component (similar to attendance manage sheet)

### 5.5. Permissions

New `assessment` group to add to `config/permission.php`:

```php
// admin group
'assessment' => [
    'assessment.definition.index',
    'assessment.definition.store',
    'assessment.definition.update',
    'assessment.definition.destroy',
    'assessment.component.index',
    'assessment.component.store',
    'assessment.component.destroy',
    'assessment.score.index',
    'assessment.score.store',
    'assessment.score.update',
],

// pengurus group (same)
// santri group: ['assessment.score.index'] — if Q2 is approved
```

### 5.6. Tests

| Test Class | File | Scope |
|---|---|---|
| `AssessmentDefinitionTest` | `tests/Feature/Academic/AssessmentDefinitionTest.php` | Model, relationships, constraints, controller index/store/update/destroy |
| `AssessmentComponentTest` | `tests/Feature/Academic/AssessmentComponentTest.php` | Model, unique constraint, FK restrictOnDelete, controller |
| `AssessmentServiceTest` | `tests/Feature/Academic/AssessmentServiceTest.php` | Domain rules, score range, class/year match, non-Aktif enrollment rejection |

---

## 6. Risk Analysis

| Risk | Severity | Mitigation |
|---|---|---|
| `weight` semantics ambiguity (definition weight vs component weight) | Medium | Two separate `weight` fields serve different purposes. `AssessmentDefinition.weight` = year-level category weight. `AssessmentComponent.weight` = assignment-level component weight. Document clearly in model `$casts` and docblocks. |
| Score uniqueness allows only one score per student per component | Low | By design — matches attendance pattern. `updateScore()` replaces prior score. |
| Class/year mismatch between definition and teaching assignment | Medium | Domain validation in `AssessmentService::createComponent()` must verify `definition.academic_year_id === teaching_assignment.academic_year_id`. |
| `cascade` on `academic_enrollments.santri_id` delete could orphan scores | Low | Score is FK'd to `academic_enrollment_id` with `restrictOnDelete` — santri cannot be deleted while scores exist. Safe. |
| `weight` out of range (0 or >100) | Low | Add DB-level check constraint and Laravel validation `integer|min:1|max:100`. |
| LMS feature creep | Low | No report card, ranking, or exam table will be created in this phase. |

---

## 7. Historical Data Protection Strategy

1. **All FK constraints use `restrictOnDelete()`** — existing academic_years, teaching_assignments, and academic_enrollments cannot be deleted once evaluation data references them.
2. **`graded_by` is nullable** — if a grading user account is deactivated or removed, historical scores are retained with `null` grader (same pattern as `marked_by` in attendance_records).
3. **`graded_at` timestamp** — provides an audit trail for when scores were recorded.
4. **No soft deletes** — assessment definitions deactivated via `is_active = false` flag, not destroyed. Components and scores are immutable once created (update only via `updateScore()`).
5. **`AcademicEnrollment` linked, NOT `Santri` directly** — score data is scoped to the academic context (year + class placement), not the student's global identity. This is consistent with the attendance domain.

---

## 8. Files to Be Created / Modified

### New Files

| File | Type |
|---|---|
| `database/migrations/2024_06_01_000006_create_evaluation_domain_tables.php` | Migration |
| `app/Models/AssessmentDefinition.php` | Model |
| `app/Models/AssessmentComponent.php` | Model |
| `app/Models/StudentAssessmentScore.php` | Model |
| `app/Services/Academic/AssessmentService.php` | Service |
| `app/Http/Controllers/Academic/AssessmentController.php` | Controller |
| `resources/views/pages/academic/assessment/definition/index.blade.php` | View |
| `resources/views/pages/academic/assessment/component/index.blade.php` | View |
| `resources/views/pages/academic/assessment/score/manage.blade.php` | View |
| `tests/Feature/Academic/AssessmentDefinitionTest.php` | Test |
| `tests/Feature/Academic/AssessmentComponentTest.php` | Test |
| `tests/Feature/Academic/AssessmentServiceTest.php` | Test |
| `docs/releases/phase-5.8.7C-10-release-notes.md` | Documentation |

### Modified Files

| File | Change |
|---|---|
| `app/Models/AcademicYear.php` | Add `HasMany(AssessmentDefinition::class)` |
| `app/Models/TeachingAssignment.php` | Add `HasMany(AssessmentComponent::class)` |
| `app/Models/AcademicEnrollment.php` | Add `HasMany(StudentAssessmentScore::class)` |
| `app/Models/User.php` | Add `HasMany(StudentAssessmentScore::class, 'graded_by')` |
| `config/permission.php` | Add `assessment` group to `admin`, `pengurus`, and optionally `santri` sections |
| `resources/views/components/navbar.blade.php` | Add "Penilaian" sidebar link |
| `routes/web.php` | Register `AssessmentController` routes |

---

## 9. Open Questions for Approval

See `implementation_plan.md` for the formal questions requiring approval.
