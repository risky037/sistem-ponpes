# Pre-Implementation Audit: Academic Operational Foundation

**Document**: `Laravel12_Academic_Operational_PreImplementation_Audit.md`  
**Phase**: 5.8.7C-8 — Laravel 12 Academic Operational Foundation  
**Timestamp**: 2026-09-20T19:55:00+07:00  
**Baseline Git Commit**: `9fb2123f` (`phase-5.8.7C-7-completed`)  
**PHP Version**: 8.4.16  
**Laravel Version**: 12.69.2  
**Test Baseline**: 188 tests passed, 771 assertions passed  

---

## 1. Executive Summary

Phase 5.8.7C-8 establishes the **Academic Operational Foundation** of the Pondok Pesantren Fatimah Az-Zahra system. Following the completion of the core academic entities in Phase 5.8.7C-6 and curriculum/teaching assignments in Phase 5.8.7C-7, this phase introduces the scheduling, academic calendar, and session tracking primitives necessary to operate classes day-to-day.

In accordance with strict LMS boundary constraints, this phase deliberately excludes attendance taking, student grades, exams, question banks, and learning materials.

---

## 2. Review of Existing Architecture & Relationships

### 2.1. Existing Models & Linkages
- **`AcademicYear`** (`app/Models/AcademicYear.php`):
  - Current relations: `academic_enrollments()`, `wali_kelas_assignments()`, `teaching_assignments()`.
  - Target additions: `classSchedules()`, `calendarEvents()`.
- **`Kelas`** (`app/Models/Kelas.php`):
  - Current relations: `academic_enrollments()`, `wali_kelas_assignments()`, `mapels()`, `teaching_assignments()`.
  - Target addition: `classSchedules()`.
- **`TeachingAssignment`** (`app/Models/TeachingAssignment.php`):
  - Current relations: `kelas()`, `mapel()`, `user()`, `academic_year()`.
  - Target additions: `classSchedules()`, `teachingSessions()`.
- **`Mapel`** (`app/Models/Mapel.php`):
  - Current relations: `kelas()`, `teaching_assignments()`.
  - Unchanged; schedules link via `TeachingAssignment`.
- **`User`** (`app/Models/User.php`):
  - Current relations: `santri()`, `wali_kelas_assignments()`, `teaching_assignments()`.
  - Unchanged; instructors link via `TeachingAssignment`.

### 2.2. Migration Dependency Order
1. `2024_06_01_000001_create_academic_foundation_tables.php` (`academic_years`, `student_batches`, `academic_enrollments`)
2. `2024_06_01_000002_create_wali_kelas_assignments_table.php` (`wali_kelas_assignments`)
3. `2024_06_01_000003_create_curriculum_domain_tables.php` (`mapels`, `teaching_assignments`)
4. **`2024_06_01_000004_create_academic_operational_tables.php`** (NEW):
   - Table 1: `class_schedules` (depends on `kelas`, `teaching_assignments`, `academic_years`)
   - Table 2: `academic_calendar_events` (depends on `academic_years`)
   - Table 3: `teaching_sessions` (depends on `teaching_assignments`, `class_schedules`)

---

## 3. Schema Design & Constraints

### 3.1. `class_schedules`
- **Primary Purpose**: Defines scheduled teaching timeslots per class, subject assignment, and day.
- **Columns**:
  - `id` (bigint, PK)
  - `kelas_id` (foreignId, `restrictOnDelete`)
  - `teaching_assignment_id` (foreignId, `restrictOnDelete`)
  - `academic_year_id` (foreignId, `restrictOnDelete`)
  - `day_of_week` (string: `'Senin'`, `'Selasa'`, `'Rabu'`, `'Kamis'`, `'Jumat'`, `'Sabtu'`, `'Ahad'`)
  - `start_time` (time)
  - `end_time` (time)
  - `room` (string, nullable)
  - `notes` (text, nullable)
  - `timestamps`
- **Composite Unique**:
  - `UNIQUE(kelas_id, teaching_assignment_id, academic_year_id, day_of_week, start_time)`

### 3.2. `academic_calendar_events`
- **Primary Purpose**: Tracks institutional academic calendar milestones (e.g., Awal Semester, Libur, Ujian).
- **Columns**:
  - `id` (bigint, PK)
  - `academic_year_id` (foreignId, `restrictOnDelete`)
  - `title` (string)
  - `event_type` (string: `'Awal Semester'`, `'Libur'`, `'Ujian'`, `'Kegiatan'`, `'Lainnya'`)
  - `start_date` (date)
  - `end_date` (date)
  - `description` (text, nullable)
  - `timestamps`

### 3.3. `teaching_sessions`
- **Primary Purpose**: Operational unit representing an actual scheduled or ad-hoc teaching instance.
- **Columns**:
  - `id` (bigint, PK)
  - `teaching_assignment_id` (foreignId, `restrictOnDelete`)
  - `class_schedule_id` (foreignId, nullable, `restrictOnDelete`)
  - `session_date` (date)
  - `status` (string, default `'Planned'`; enum: `'Planned'`, `'Completed'`, `'Cancelled'`)
  - `notes` (text, nullable)
  - `timestamps`

---

## 4. Service Layer Specifications

1. **`AcademicScheduleService`**:
   - `createSchedule(Kelas, TeachingAssignment, AcademicYear, string $day, string $start, string $end, ?string $room, ?string $notes): ClassSchedule`
   - `updateSchedule(ClassSchedule, array $data): ClassSchedule`
   - `deleteSchedule(ClassSchedule): void` (guarded against active `teaching_sessions`)
   - Invariants:
     - `start_time < end_time`
     - `teaching_assignment.kelas_id === kelas.id`
     - `teaching_assignment.academic_year_id === academic_year.id`
     - Database transaction wrap.

2. **`TeachingSessionService`**:
   - `generateSession(TeachingAssignment, string $date, ?ClassSchedule $schedule, ?string $notes): TeachingSession`
   - `cancelSession(TeachingSession, ?string $notes): TeachingSession`
   - `completeSession(TeachingSession, ?string $notes): TeachingSession`
   - Invariants:
     - Enforces state transitions: `Planned -> Completed`, `Planned -> Cancelled`.
     - Preserves session history.

---

## 5. UI & Permissions Plan

- **UI Views**:
  - Class Schedule: `resources/views/pages/academic/class_schedule/` (`index.blade.php`, `include/action.blade.php`)
  - Calendar Event: `resources/views/pages/academic/calendar_event/` (`index.blade.php`, `include/action.blade.php`)
  - Components: `<x-card-toolbar>`, `<x-status-badge>`, `<x-delete-modal>`
- **Navigation**:
  - Add "Jadwal Pelajaran" and "Kalender Akademik" under Master Data in `resources/views/components/navbar.blade.php`.
- **Permissions**:
  - `jadwal`: `index`, `view`, `store`, `update`, `destroy`
  - `kalender_akademik`: `index`, `view`, `store`, `update`, `destroy`
  - Roles: `Administrator` and `Pengurus`.
