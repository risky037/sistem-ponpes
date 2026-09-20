# Release Notes — Phase 5.8.7C-10: Laravel 12 Academic Evaluation Domain Foundation

**Release Tag**: `phase-5.8.7C-10-completed`  
**Date**: 2026-09-20  
**Branch**: `develop`  
**System Baseline**: Laravel 12.x, PHP 8.4+, MariaDB 10.4.32, Bootstrap 5.1.3, Vite 4.4.9  

---

## 1. Release Objectives

Phase 5.8.7C-10 establishes the **Academic Evaluation Domain Foundation** for the **Sistem Informasi Pondok Pesantren Fatimah Az-Zahra**.

This domain introduces a multi-tier assessment structure linking academic year defaults (`assessment_definitions`), per-class teacher overrides (`assessment_components`), and individual student grading entries (`student_assessment_scores`), all protected by strict transactional integrity, historical accountability, and class-year consistency rules.

Primary objectives accomplished:
1. **Evaluation Domain Schema (`assessment_definitions`, `assessment_components`, `student_assessment_scores`)**: Normalized 3-tier database schema with non-cascading foreign keys (`restrictOnDelete`) and unique composite keys.
2. **Two-Tier Weight Model (Q1 Approved)**: Year-level default weights on definitions, with optional per-assignment weight overrides on components.
3. **Historical Data Preservation (Q3 Approved)**: Deleting definitions without children performs a hard delete; definitions with existing components or scores are preserved via deactivation (`is_active = false`).
4. **Unified Controller Pattern (Q4 Approved)**: Single `AssessmentController` managing definition, component, and score workflows.
5. **Role-Based Access (Q2 Approved)**: Explicitly restricted evaluation management to `Administrator` and `Pengurus`, with no Santri score access in this foundational phase.
6. **Strict LMS Boundary**: Explicitly excluded report cards, ranking, graduation calculations, CBT exams, question banks, and learning materials.

---

## 2. Schema & Database Changes

Migration: `database/migrations/2024_06_01_000006_create_evaluation_domain_tables.php`

### 2.1. `assessment_definitions` (Master Category Table)
- Fields:
  - `id` (bigint, PK)
  - `academic_year_id` (FK `academic_years.id`, `restrictOnDelete`)
  - `name` (varchar)
  - `type` (varchar: `'UTS'`, `'UAS'`, `'Tugas'`, `'Praktik'`, `'Ulangan Harian'`, `'Lainnya'`)
  - `weight` (unsignedTinyInteger: 0–100)
  - `is_active` (boolean, default `true`)
  - `timestamps`
- Constraints:
  - `UNIQUE(academic_year_id, name)` named `assessment_definitions_year_name_unique`.

### 2.2. `assessment_components` (Assignment Mapping Table)
- Fields:
  - `id` (bigint, PK)
  - `teaching_assignment_id` (FK `teaching_assignments.id`, `restrictOnDelete`)
  - `assessment_definition_id` (FK `assessment_definitions.id`, `restrictOnDelete`)
  - `weight` (unsignedTinyInteger: 0–100)
  - `timestamps`
- Constraints:
  - `UNIQUE(teaching_assignment_id, assessment_definition_id)` named `assessment_components_assignment_def_unique`.

### 2.3. `student_assessment_scores` (Student Score Records)
- Fields:
  - `id` (bigint, PK)
  - `assessment_component_id` (FK `assessment_components.id`, `restrictOnDelete`)
  - `academic_enrollment_id` (FK `academic_enrollments.id`, `restrictOnDelete`)
  - `score` (decimal 5,2: 0.00–100.00)
  - `notes` (text, nullable)
  - `graded_by` (FK `users.id`, nullable, `restrictOnDelete`)
  - `graded_at` (timestamp, nullable)
  - `timestamps`
- Constraints:
  - `UNIQUE(assessment_component_id, academic_enrollment_id)` named `student_scores_comp_enrollment_unique`.

---

## 3. Domain Architecture & Code Additions

### 3.1. Eloquent Models & Relationships
- **`AssessmentDefinition`** (`app/Models/AssessmentDefinition.php`):
  - Constants: `TYPE_UTS`, `TYPE_UAS`, `TYPE_TUGAS`, `TYPE_PRAKTIK`, `TYPE_HARIAN`, `TYPE_LAINNYA`, `ALLOWED_TYPES`.
  - Scopes: `scopeActive()`, `scopeForAcademicYear()`.
  - Casts: `weight => integer`, `is_active => boolean`.
  - Relationships: `belongsTo(AcademicYear)`, `hasMany(AssessmentComponent)`.
- **`AssessmentComponent`** (`app/Models/AssessmentComponent.php`):
  - Casts: `weight => integer`.
  - Relationships: `belongsTo(TeachingAssignment)`, `belongsTo(AssessmentDefinition)`, `hasMany(StudentAssessmentScore)`.
- **`StudentAssessmentScore`** (`app/Models/StudentAssessmentScore.php`):
  - Casts: `score => decimal:2`, `graded_at => datetime`.
  - Relationships: `belongsTo(AssessmentComponent)`, `belongsTo(AcademicEnrollment)`, `belongsTo(User, 'graded_by')` as `grader()`.
- **Existing Models Updated**:
  - `AcademicYear`: Added `assessmentDefinitions()` and `assessment_definitions()`.
  - `TeachingAssignment`: Added `assessmentComponents()` and `assessment_components()`.
  - `AcademicEnrollment`: Added `studentAssessmentScores()` and `student_assessment_scores()`.
  - `User`: Added `gradedScores()` and `graded_scores()`.

### 3.2. Service Layer
- **`AssessmentService`** (`app/Services/Academic/AssessmentService.php`):
  - `createDefinition()`: Enforces unique name per academic year, valid type whitelist, and 0–100 weight range.
  - `updateDefinition()`: Updates definition attributes with duplicate prevention.
  - `deactivateDefinition()`: Hard-deletes if no components exist; deactivates (`is_active = false`) if components or scores exist.
  - `createComponent()`: Validates matching `academic_year_id` between assignment and definition, definition active state, duplicate prevention, and fallback weight resolution.
  - `deleteComponent()`: Rejects deletion if student scores exist to protect academic records.
  - `recordScore()`: Atomic score recording with range validation (0.00–100.00), class/year alignment, active enrollment validation, and duplicate prevention.
  - `bulkRecordScores()`: Atomic multi-score batch recording wrapped in `DB::transaction()`. Rolls back all changes if any student record violates domain rules.
  - `updateScore()`: Updates individual score and notes with grader accountability audit trail.

### 3.3. Unified Controller & Routes
- `AssessmentController` (`/assessment`):
  - `GET /assessment/definition`: Definitions list with filters and modals.
  - `POST /assessment/definition`: Store definition.
  - `PUT|PATCH /assessment/definition/{assessmentDefinition}`: Update definition.
  - `DELETE /assessment/definition/{assessmentDefinition}`: Deactivate/delete definition.
  - `GET /assessment/component`: Components list with teaching assignment and scoring progress.
  - `POST /assessment/component`: Store component.
  - `DELETE /assessment/component/{assessmentComponent}`: Delete component.
  - `GET /assessment/score`: Component index for scoring.
  - `GET /assessment/score/{assessmentComponent}/manage`: Score sheet for class cohort.
  - `POST /assessment/score/{assessmentComponent}`: Bulk record scores.
  - `PUT|PATCH /assessment/score/record/{studentAssessmentScore}`: Update single score.
- All routes protected under `role:Administrator|Pengurus`.

### 3.4. User Interface & Permissions
- Blade SSR Views in `resources/views/pages/academic/assessment/`:
  - `definition/index.blade.php`: DataTables list, year/type filters, create modal, edit modal.
  - `component/index.blade.php`: DataTables list, year/class filters, create modal with assignment-definition cascading.
  - `score/manage.blade.php`: Component summary card and bulk scoring table with inline inputs and audit history.
- Navigation in `resources/views/components/navbar.blade.php`: *Penilaian* dropdown with links to *Definisi Nilai*, *Komponen Nilai*, and *Input Nilai*.
- Permissions in `config/permission.php`: 10 distinct `assessment.*` permissions assigned to `Administrator` and `Pengurus`.

---

## 4. Strict LMS Domain Boundary

| Feature Area | Current Phase (5.8.7C-10) | Future LMS Phase |
|---|---|---|
| Assessment Definitions & Types | **Implemented** (`assessment_definitions`) | Master assessment categories |
| Assessment Components & Weights | **Implemented** (`assessment_components`) | Subject evaluation weight structure |
| Student Score Recording | **Implemented** (`student_assessment_scores`) | Raw grades data foundation |
| Gradebook Aggregations & Report Cards | **Excluded** | Future LMS Report Cards Domain |
| Exams & CBT Question Banks | **Excluded** | Future LMS Examination Domain |
| Class Ranking & Graduation Logic | **Excluded** | Future LMS Graduation Domain |
| Learning Materials & Course Modules | **Excluded** | Future LMS Content Domain |

---

## 5. Validation Results

| Test / Check | Command | Result |
|---|---|---|
| **Cache Clear** | `php artisan optimize:clear` | **PASS** (all caches cleared) |
| **Fresh Migration & Seed** | `php artisan migrate:fresh --seed` | **PASS** (19 migrations, 5 seeders) |
| **Permission Verification** | `php artisan tinker` | **PASS** (all 10 permissions assigned to Admin and Pengurus) |
| **Route Registration** | `php artisan route:list --path=assessment` | **PASS** (all 11 routes active) |
| **Test Suite** | `php artisan test` | **PASS** (**266 passed, 979 assertions**) |
| **Frontend Build** | `npm run build` | **PASS** (Vite build successful in 296ms) |
| **Pint Linter** | `composer run lint:check` | **PASS** (0 style violations) |
| **Security Audit** | `composer audit` | **PASS** (0 vulnerabilities) |

---

## 6. Deployment Notes

1. **Database Migration**: Run `php artisan migrate` to create `assessment_definitions`, `assessment_components`, and `student_assessment_scores`.
2. **Permissions**: Run `php artisan db:seed --class=RolePermissionSeeder` to register and sync `assessment` permissions.
3. **Application Optimization**: Run `php artisan optimize:clear && php artisan optimize`.
