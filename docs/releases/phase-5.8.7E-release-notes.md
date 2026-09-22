# Release Notes — Phase 5.8.7E: Laravel 12 User Experience & Role Portal Foundation

**Release Tag**: `phase-5.8.7E-completed`  
**Date**: 2026-09-22  
**Branch**: `develop`  
**System Baseline**: Laravel 12.69.2, PHP 8.4+, MariaDB 10.4.32, Bootstrap 5.1.3, Vite 4.4.9, Spatie Permission  
**Test Status**: 325 passed (1264 assertions), 100% green  

---

## 1. Release Overview

Phase 5.8.7E delivers comprehensive **User Experience (UX) and Role Portal Foundation** improvements for **Sistem Informasi Pondok Pesantren Fatimah Az-Zahra**.

This release resolves systemic UX debt, standardizes visual components and responsive modal dialogs, enforces clean date formatting across all views, introduces the dedicated `Guru` role with Granular Spatie permissions, builds dedicated role-aware portals for Santri and Asatidz, and introduces a robust, presentation-ready demo dataset installer via Artisan.

### Key Architectural Highlights:
1. **Design System & Dark Mode Stabilization**:
   - Replaced scattered inline hex styles with centralized CSS design tokens (`--pesantren-*`) in `public/assets/css/app.css`.
   - Added theme gradients (`.bg-brand-gradient`, `.bg-intelligence-gradient`, etc.) and brand accent tokens (`.text-teal`, `.bg-teal`, `.text-amber`, `.bg-amber`).
   - Standardized table hover variables for reliable dark mode contrast.
2. **Table & Modal Standardization**:
   - Eliminated `table-striped` across all 23 Blade files (~32 occurrences), adopting uniform clean surfaces with `.table-hover`.
   - Removed phantom `kode` header/column definitions in Kamar and Kelas DataTables.
   - Synchronized `x-modal-form` and `x-edit-modal` with centered alignments, rounded corners (`radius-15`), subtle drop shadows, and standardized header/footer actions.
3. **Centralized Date Formatting**:
   - Implemented `Helper::formatDate($date, $format = 'd F Y')` and registered the `@formatDate($date)` Blade directive.
   - Replaced raw timestamps and manual `date()`/`strtotime()` calls across santri detail, prints, and role listings with localized Indonesian dates.
4. **CRUD Reliability & Error Resilience**:
   - Resolved silent failures in `SantriController`: added structured exception logging (`Log::error`) with user context and URI details while preserving user form input via `->withInput()`.
5. **Password Management Enhancement**:
   - Made profile password updates strictly optional (`nullable` validation) in `AccountRequest` and `ProfilController`.
   - Implemented administrator-managed password reset (`PATCH /users/{user}/reset-password` and `users.reset_password` alias) with confirmation modal.
6. **Dedicated `Guru` Role & Permission Architecture**:
   - Introduced `Guru` role in `config/permission.php` and `RolePermissionSeeder` without relying on legacy `Pengurus` checks.
   - Added granular permissions: `attendance.manage`, `assessment.input`, `teaching.schedule.view`, `class.schedule.view`.
   - Protected navbar links with permission gates (`@can`, `@canany`).
7. **Role-Aware Dashboard & Portals**:
   - **Santri Read-Only Personal Portal (`pages.portal.santri`)**: Read-only profile overview, active academic year, enrolled class, room placement, attendance statistics (H/I/S/A and percentage), and personal assessment score breakdown. Strictly boundary-enforced: no financial data, no score modification, no student ranking.
   - **Guru / Asatidz Portal (`pages.portal.guru`)**: Active academic year status, assigned classes/subjects, weekly teaching schedule matrix, session fulfillment counter, and quick action shortcuts to Presensi and Nilai input.
   - Role-aware dispatching in `DashboardController`: automatically routes Santri and Guru users to their dedicated portals while preserving administrative dashboards for Administrator and Pengurus.
8. **Reusable Demo Data Architecture**:
   - Artisan Command: `php artisan demo:install {--fresh} {--santri=20}`.
   - Robust `DemoDataSeeder` generating realistic Indonesian demo data: Administrator, Pengurus, Keuangan, 3 Asatidz (Guru), enrolled Santri, classes, rooms, academic years, teaching assignments, schedules, completed sessions, attendance records, assessment definitions, components, scores, and performance summaries.
   - Eloquent factories: `UserFactory`, `SantriFactory`, `KelasFactory`, `MapelFactory`, `AcademicYearFactory`.
9. **Strict Domain Boundaries Preserved**:
   - Zero implementation of LMS modules, course materials, question banks, CBT/exam engines, report card (*rapor*) generation, student ranking, or graduation systems.

---

## 2. Testing & Quality Assurance

- **Full Suite Test Results**:
  - `php artisan test`: **325 passed** (1264 assertions)
  - Zero failures, zero warnings, zero regressions.
- **Dedicated Feature Tests Added**:
  - `Tests\Feature\RolePortalTest`:
    - `santri_portal_renders_personal_read_only_data_correctly`
    - `guru_portal_renders_teacher_schedule_and_assignments`
    - `administrator_and_pengurus_receive_main_dashboard`
    - `user_profile_update_allows_empty_password`
    - `admin_can_reset_any_user_password`
    - `non_admin_cannot_reset_user_password`
    - `demo_install_command_runs_successfully`
- **Asset Compilation**: `npm run build` executed successfully (Vite 4.4.9).
- **Code Style**: `composer run lint:check` passed 100% via Laravel Pint.
