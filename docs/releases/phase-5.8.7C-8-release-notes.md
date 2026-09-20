# Release Notes — Phase 5.8.7C-8: Laravel 12 Academic Operational Foundation

**Release Tag**: `phase-5.8.7C-8-completed`  
**Date**: 2026-09-20  
**Branch**: `develop`  
**System Baseline**: Laravel 12.x, PHP 8.4+, MariaDB 10.4.32, Bootstrap 5.1.3, Vite 4.4.9  

---

## 1. Release Objectives

Phase 5.8.7C-8 establishes the operational academic foundation (*Academic Operational Layer*) for **Sistem Informasi Pondok Pesantren Fatimah Az-Zahra**, bridging the static curriculum model (Mapel, TeachingAssignment, WaliKelasAssignment) and future digital learning workflows.

Primary objectives accomplished:
1. **Academic Scheduling Domain (`class_schedules`)**: Established class schedule allocation per weekly slot, linking class, subject, teacher assignment, and academic year.
2. **Academic Calendar Domain (`academic_calendar_events`)**: Structured institutional milestones, semester start dates, exam periods, and holidays.
3. **Teaching Session Foundation (`teaching_sessions`)**: Introduced transactional session records tracking instructional delivery with a defined lifecycle (`Planned`, `Completed`, `Cancelled`).
4. **Service Layer Isolation**: Built dedicated domain services (`AcademicScheduleService`, `TeachingSessionService`) encapsulating slot conflict validation, time order constraints, status state transitions, and historical integrity protections.
5. **Administrative UI Modernization**: Implemented responsive Blade SSR interfaces for Class Schedules and Academic Calendar Events reusing `<x-card-toolbar>`, `<x-status-badge>`, `<x-delete-modal>`, and Pesantren Green design tokens.
6. **Strict Domain Boundary**: Explicitly excluded LMS operational features (attendance/presensi, grading/penilaian, exam question banks, and learning material uploads) to preserve separation of concerns.

---

## 2. Schema & Database Changes

Migration: `database/migrations/2024_06_01_000004_create_academic_operational_tables.php`

### 2.1. `class_schedules` (New Table)
- Fields:
  - `id` (bigint, PK)
  - `kelas_id` (FK `kelas.id`, `restrictOnDelete`)
  - `teaching_assignment_id` (FK `teaching_assignments.id`, `restrictOnDelete`)
  - `academic_year_id` (FK `academic_years.id`, `restrictOnDelete`)
  - `day_of_week` (varchar, e.g., 'Senin', 'Selasa', etc.)
  - `start_time` (time)
  - `end_time` (time)
  - `room` (varchar, nullable)
  - `notes` (text, nullable)
  - `timestamps`
- Constraints:
  - `UNIQUE(kelas_id, teaching_assignment_id, academic_year_id, day_of_week, start_time)` named `class_schedules_slot_unique`.

### 2.2. `academic_calendar_events` (New Table)
- Fields:
  - `id` (bigint, PK)
  - `academic_year_id` (FK `academic_years.id`, `restrictOnDelete`)
  - `title` (varchar)
  - `event_type` (varchar: `'Awal Semester'`, `'Libur'`, `'Ujian'`, `'Kegiatan'`, `'Lainnya'`)
  - `start_date` (date)
  - `end_date` (date)
  - `description` (text, nullable)
  - `timestamps`

### 2.3. `teaching_sessions` (New Table)
- Fields:
  - `id` (bigint, PK)
  - `teaching_assignment_id` (FK `teaching_assignments.id`, `restrictOnDelete`)
  - `class_schedule_id` (FK `class_schedules.id`, nullable, `restrictOnDelete`)
  - `session_date` (date)
  - `status` (varchar, default `'Planned'`)
  - `notes` (text, nullable)
  - `timestamps`
- Lifecycle Statuses:
  - `Planned`, `Completed`, `Cancelled`.

---

## 3. Domain Architecture & Code Additions

### 3.1. Models & Relationships
- **`ClassSchedule`** (`app/Models/ClassSchedule.php`):
  - BelongsTo: `Kelas`, `TeachingAssignment`, `AcademicYear`.
  - HasMany: `TeachingSession`.
- **`AcademicCalendarEvent`** (`app/Models/AcademicCalendarEvent.php`):
  - BelongsTo: `AcademicYear`.
- **`TeachingSession`** (`app/Models/TeachingSession.php`):
  - BelongsTo: `TeachingAssignment`, `ClassSchedule`.
- **`Kelas`**, **`AcademicYear`**, **`TeachingAssignment`**:
  - Updated with HasMany relationships to new operational entities.

### 3.2. Service Layer
- **`AcademicScheduleService`** (`app/Services/Academic/AcademicScheduleService.php`):
  - `createSchedule()`, `updateSchedule()`, `deleteSchedule()`.
  - Enforces time sequence (`start_time < end_time`), slot collision guards, and historical preservation (prevents deleting schedules with recorded sessions).
- **`TeachingSessionService`** (`app/Services/Academic/TeachingSessionService.php`):
  - `generateSession()`, `completeSession()`, `cancelSession()`.
  - Manages atomic state machine transitions and schedule assignment validation.

### 3.3. Controllers & Routes
- `ClassScheduleController` (`/class-schedule`) — Schedule management with DataTables, filter by academic year, class, and day.
- `AcademicCalendarEventController` (`/academic-calendar-event`) — Academic calendar event management with DataTables, filter by year and event category.
- Routes registered in `routes/web.php` with `role:Administrator|Pengurus` middleware protection.

### 3.4. Navigation & Views
- Views created under:
  - `resources/views/pages/academic/class_schedule/` (`index.blade.php`, `include/action.blade.php`)
  - `resources/views/pages/academic/calendar_event/` (`index.blade.php`, `include/action.blade.php`)
- Sidebar navigation updated in `resources/views/components/navbar.blade.php`:
  - `Jadwal Pelajaran` (`bx bx-time-five`)
  - `Kalender Akademik` (`bx bx-calendar-event`)

---

## 4. Strict LMS Domain Boundary

| Feature Area | Current Phase (5.8.7C-8) | Future LMS Phase |
|---|---|---|
| Class Scheduling | **Implemented** (`class_schedules`) | Feeding into session generation |
| Academic Calendar | **Implemented** (`academic_calendar_events`) | Feeding into active term validation |
| Teaching Sessions | **Implemented** (`teaching_sessions`) | Foundation for teacher journal & roll call |
| Student Attendance | **Excluded** | Future LMS Attendance Domain |
| Grading & Report Cards | **Excluded** | Future LMS Assessment Domain |
| Exams & CBT | **Excluded** | Future LMS Examination Domain |
| Online Learning Materials | **Excluded** | Future LMS Learning Materials Domain |

---

## 5. Validation Results

| Test / Check | Command | Result |
|---|---|---|
| **Cache Clear** | `php artisan optimize:clear` | **PASS** (all caches cleared) |
| **Fresh Migration & Seed** | `php artisan migrate:fresh --seed` | **PASS** (17 migrations, 5 seeders) |
| **Test Suite** | `php artisan test` | **PASS** (**211 passed, 836 assertions**) |
| **Frontend Build** | `npm run build` | **PASS** (Vite build successful) |
| **Pint Linter** | `composer run lint:check` | **PASS** (0 style violations) |
| **Security Audit** | `composer audit` | **PASS** (0 vulnerabilities) |

---

## 6. Deployment Notes

1. **Database Migration**: Run `php artisan migrate` to create `class_schedules`, `academic_calendar_events`, and `teaching_sessions`.
2. **Permissions**: Run `php artisan db:seed --class=RolePermissionSeeder` to register and grant `jadwal` and `kalender_akademik` permissions to `Administrator` and `Pengurus` roles.
3. **Application Optimization**: Run `php artisan optimize:clear && php artisan optimize`.
