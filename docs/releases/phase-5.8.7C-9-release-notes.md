# Release Notes — Phase 5.8.7C-9: Laravel 12 Attendance Domain Foundation

**Release Tag**: `phase-5.8.7C-9-completed`  
**Date**: 2026-09-20  
**Branch**: `develop`  
**System Baseline**: Laravel 12.x, PHP 8.4+, MariaDB 10.4.32, Bootstrap 5.1.3, Vite 4.4.9  

---

## 1. Release Objectives

Phase 5.8.7C-9 introduces the **Attendance Domain Foundation** for the **Sistem Informasi Pondok Pesantren Fatimah Az-Zahra**.

This domain directly connects instructional delivery (`TeachingSession`) with active students via their academic placements (`AcademicEnrollment`), providing an accountable, historical, and transactional attendance-tracking foundation.

Primary objectives accomplished:
1. **Attendance Records Entity (`attendance_records`)**: Established student attendance records per teaching session with strict unique constraint and foreign key protections.
2. **Attendance Lifecycle & Domain Service (`AttendanceService`)**: Encapsulated attendance validation rules (cancelled session guards, class/academic year consistency, status enum validation, and completeness verification).
3. **Auditing & Historical Accountability**: Captured `marked_at` timestamp and `marked_by` user reference (`restrictOnDelete`) for audit compliance.
4. **Administrative Attendance UI**: Developed responsive Blade SSR interfaces for session attendance overviews and attendance sheets utilizing the Pesantren Green design system.
5. **Strict LMS Boundary**: Preserved separation of concerns by explicitly excluding grading/evaluation, exams, CBT question banks, and learning materials.

---

## 2. Schema & Database Changes

Migration: `database/migrations/2024_06_01_000005_create_attendance_records_table.php`

### `attendance_records` (New Table)
- Fields:
  - `id` (bigint, PK)
  - `teaching_session_id` (FK `teaching_sessions.id`, `restrictOnDelete`)
  - `academic_enrollment_id` (FK `academic_enrollments.id`, `restrictOnDelete`)
  - `status` (varchar: `'Hadir'`, `'Izin'`, `'Sakit'`, `'Alpha'`)
  - `notes` (text, nullable)
  - `marked_at` (timestamp, nullable)
  - `marked_by` (FK `users.id`, nullable, `restrictOnDelete`)
  - `timestamps`
- Constraints:
  - `UNIQUE(teaching_session_id, academic_enrollment_id)` named `attendance_session_enrollment_unique`.

---

## 3. Domain Architecture & Code Additions

### 3.1. Models & Relationships
- **`AttendanceRecord`** (`app/Models/AttendanceRecord.php`):
  - BelongsTo: `TeachingSession`, `AcademicEnrollment`, `User` (as `marker`).
  - Scopes: `scopePresent()`, `scopeAbsent()`, `scopeForSession()`.
  - Casts: `marked_at` => `'datetime'`.
- **`TeachingSession`**: Added `attendanceRecords()` and `attendance_records()`.
- **`AcademicEnrollment`**: Added `attendanceRecords()` and `attendance_records()`.

### 3.2. Service Layer
- **`AttendanceService`** (`app/Services/Academic/AttendanceService.php`):
  - `markAttendance()`: Atomic single record creation with session eligibility, class/year match, and duplicate prevention.
  - `bulkMarkAttendance()`: Atomic bulk attendance marking for entire class cohorts.
  - `updateAttendance()`: Updates attendance record status, notes, and marker.
  - `completeAttendance()`: Validates attendance completeness across active enrolled students without mutating session status.

### 3.3. Controllers & Routes
- `AttendanceController` (`/attendance`):
  - `GET /attendance`: Sesi pembelajaran overview with attendance summary badges (`H`, `I`, `S`, `A`).
  - `GET /attendance/{teachingSession}/manage`: Attendance sheet with quick "Tandai Semua Hadir" helper.
  - `POST /attendance/{teachingSession}`: Bulk store attendance submissions.
  - `PUT|PATCH /attendance/record/{attendanceRecord}`: Individual record update endpoint.
- Routes protected with `role:Administrator|Pengurus`.

### 3.4. Navigation & Permissions
- Integrated menu in `resources/views/components/navbar.blade.php`: *Presensi Kelas* (`bx bx-check-square`).
- Permissions added in `config/permission.php`: `attendance.index`, `attendance.create`, `attendance.update` assigned to `Administrator` and `Pengurus`.

---

## 4. Strict LMS Domain Boundary

| Feature Area | Current Phase (5.8.7C-9) | Future LMS Phase |
|---|---|---|
| Student Attendance Tracking | **Implemented** (`attendance_records`) | Feeds into attendance percentage reporting |
| Teaching Session Connection | **Implemented** (`teaching_sessions`) | Feeds into teacher instructional journals |
| Student Gradebook / Scores | **Excluded** | Future LMS Grading Domain |
| Exams & Question Banks | **Excluded** | Future LMS Assessment Domain |
| Report Cards / Raport | **Excluded** | Future LMS Reporting Domain |
| Course Modules & Files | **Excluded** | Future LMS Content Domain |

---

## 5. Validation Results

| Test / Check | Command | Result |
|---|---|---|
| **Cache Clear** | `php artisan optimize:clear` | **PASS** (all caches cleared) |
| **Fresh Migration & Seed** | `php artisan migrate:fresh --seed` | **PASS** (18 migrations, 5 seeders) |
| **Test Suite** | `php artisan test` | **PASS** (**230 passed, 889 assertions**) |
| **Frontend Build** | `npm run build` | **PASS** (Vite build successful) |
| **Pint Linter** | `composer run lint:check` | **PASS** (0 style violations) |
| **Security Audit** | `composer audit` | **PASS** (0 vulnerabilities) |

---

## 6. Deployment Notes

1. **Database Migration**: Run `php artisan migrate` to create `attendance_records`.
2. **Permissions**: Run `php artisan db:seed --class=RolePermissionSeeder` to register and sync `attendance` permissions.
3. **Application Optimization**: Run `php artisan optimize:clear && php artisan optimize`.
