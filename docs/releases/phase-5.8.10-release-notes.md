---
title: Catatan Rilis Fase 5.8.10 — LMS Database Foundation Implementation
category: releases
role:
  - Administrator
  - Pengurus
estimated_time: 15 menit
difficulty: intermediate
version: 5.8.10
---

# Catatan Rilis Fase 5.8.10 — LMS Database Foundation Implementation (Migration, Models, Relationships & Testing)

**Tanggal Rilis**: 26 September 2026  
**Status Fase**: Selesai (*Verified Clean*)  
**Target Modul**: Fondasi Basis Data *Learning Management System* (LMS) DIGITREN

---

## 1. Ringkasan Eksekutif

Fase 5.8.10 berhasil mengimplementasikan fondasi basis data fisik untuk modul **Learning Management System (LMS)** berdasarkan cetak biru arsitektur yang telah disetujui pada Fase 5.8.9. Seluruh tabel baru telah dimigrasikan, model Eloquent telah dikonfigurasi dengan relasi terbalik yang lengkap, model factory dan demo seeder telah disiapkan, serta seluruh fungsionalitas basis data telah diuji melalui automated test suite yang lulus 100%.

Sesuai batasan ketat fase ini:
- **Hanya Lapisan Basis Data**: Tidak ada antarmuka pengguna (UI), pengontrol HTTP (controller), atau lapisan layanan (service) yang diimplementasikan pada fase ini.
- **Isolasi Domain Akademik Eksisting**: Tabel akademik eksisting (`teaching_assignments`, `academic_enrollments`, `assessments`, `mapels`, `kelas`, `academic_years`) tidak dimodifikasi strukturnya dan tetap berfungsi normal.
- **Integritas Relasional Penuh**: Menegakkan `ON DELETE RESTRICT` pada master data penting dan `CASCADE` pada entitas anak lampiran dan pivot target kelas.

---

## 2. Ringkasan Migrasi Basis Data (*Migration Summary*)

Tiga berkas migrasi baru telah dibuat dan berhasil dijalankan pada basis data:

| No | Berkas Migrasi | Tabel Dibuat | Keterangan & Kunci Relasional |
| :---: | :--- | :--- | :--- |
| 1 | `2026_09_26_100001_create_learning_materials_table.php` | `learning_materials` | Tabel kepala materi ajar. Memuat relasi FK ke `users` (`teacher_id` & `created_by`), `mapels` (`mapel_id`), `kelas` (`kelas_id` nullable), dan `academic_years` (`academic_year_id`). Dilengkapi fitur `softDeletes` dan indeks komposit pencarian. |
| 2 | `2026_09_26_100002_create_learning_material_files_table.php` | `learning_material_files` | Tabel lampiran berkas dokumen fisik (PDF, DOCX, presentasi). Terikat ke `learning_materials.id` dengan `ON DELETE CASCADE`. Memuat metadata nama asli, tipe MIME, ekstensi, ukuran bita, dan *download count*. |
| 3 | `2026_09_26_100003_create_learning_material_targets_table.php` | `learning_material_targets` | Tabel pivot penargetan visibilitas multi-kelas. Memuat `UNIQUE KEY ('learning_material_id', 'kelas_id')` untuk mencegah duplikasi penargetan materi ke rombel kelas yang sama. |

> [!NOTE]
> Tabel pelacak pembacaan `learning_material_views` **tidak dibuat** pada fase ini sesuai keputusan arsitektur Fase 5.8.9 untuk ditunda ke fase lanjutan (Fase 5.8.14+).

---

## 3. Model Eloquent yang Dihasilkan (*Models & Relationships*)

### 3.1 Model `App\Models\LearningMaterial`
- **Konstanta Tipe Konten**: `CONTENT_TYPE_TEXT`, `CONTENT_TYPE_PDF`, `CONTENT_TYPE_VIDEO`, `CONTENT_TYPE_LINK`, `CONTENT_TYPE_MIXED`.
- **Konstanta Status**: `STATUS_DRAFT`, `STATUS_PUBLISHED`, `STATUS_ARCHIVED`.
- **Relasi**:
  - `teacher()`: `BelongsTo` ke `User` (guru pengampu).
  - `creator()`: `BelongsTo` ke `User` (pembuat rekaman).
  - `mapel()`: `BelongsTo` ke `Mapel`.
  - `kelas()`: `BelongsTo` ke `Kelas` (rujukan rombel utama).
  - `academicYear()`: `BelongsTo` ke `AcademicYear`.
  - `files()`: `HasMany` ke `LearningMaterialFile`.
  - `targets()`: `BelongsToMany` ke `Kelas` melalui pivot `learning_material_targets`.
- **Scopes**:
  - `scopePublished(Builder $query)`: Menyaring materi dengan status `published`.
  - `scopeDraft(Builder $query)`: Menyaring materi dengan status `draft`.
  - `scopeArchived(Builder $query)`: Menyaring materi dengan status `archived`.
  - `scopeForClass(Builder $query, int|Kelas $kelas)`: Menyaring materi yang ditargetkan untuk kelas tertentu.
- **Helper Methods**:
  - `isPublished(): bool`
  - `isDraft(): bool`
  - `isArchived(): bool`
  - `hasFiles(): bool`

### 3.2 Model `App\Models\LearningMaterialFile`
- **Relasi**:
  - `learningMaterial()` / `material()`: `BelongsTo` ke `LearningMaterial`.
- **Helper & Accessor**:
  - `getHumanFileSize(): string`: Mengonversi ukuran bita menjadi satuan terformat (`B`, `KB`, `MB`, `GB`).
  - Accessor `$file->human_file_size`.

### 3.3 Penambahan Relasi pada Model Eksisting (Non-Breaking)
- `User::learningMaterials()`: `HasMany` ke `LearningMaterial`.
- `Mapel::learningMaterials()`: `HasMany` ke `LearningMaterial`.
- `Kelas::primaryLearningMaterials()`: `HasMany` ke `LearningMaterial`.
- `Kelas::targetedLearningMaterials()`: `BelongsToMany` ke `LearningMaterial`.

---

## 4. Factory & Demo Seeder

1. **`LearningMaterialFactory`**:
   - Mendukung pembuatan data dummy realistis untuk testing dengan berbagai *state*: `draft()`, `published()`, `archived()`, `pdf()`, `video()`.
   - Menggunakan resolusi `academic_year_id` yang cerdas untuk mencegah pelanggaran kunci unik.
2. **`LearningMaterialSeeder`**:
   - Menghasilkan contoh bahan ajar kepesantrenan:
     - **Fiqih**: "Bab Thaharah: Wudhu, Mandi Wajib, dan Ketentuan Tayammum" (PDF lampiran, ditargetkan ke rombel paralel).
     - **Nahwu**: "Kajian Nadhom Alfiyah: Pengertian Kalam dan Tanda-Tanda Isim" (Teks kaya & kutipan nadhom).
     - **Hadits**: "Hadits Arba'in Ke-1: Niat dan Hakikat Keikhlasan Beramal" (Tautan video kajian).
     - **Fiqih (Draft)**: "Bab Sholat Berjamaah dan Ketentuan Makmum Masbuq" (Status draf ustadz).

---

## 5. Hasil Verifikasi Pengujian (*Automated Test Results*)

1. **Feature Test Khusus LMS (`LearningMaterialDatabaseTest`)**:
   - `php artisan test --filter=LearningMaterialDatabaseTest`
   - **Hasil**: `8 passed (47 assertions)`
   - Mencakup verifikasi kepemilikan guru, relasi mapel, penargetan multi-kelas, penegakan kunci unik pivot, manajemen berkas lampiran beserta ukuran bita, kerja *soft deletes* dan *force delete*, seluruh *query scopes*, serta integritas data seeder.

2. **Rangkaian Regresi Penuh (*Full Regression Test Suite*)**:
   - `php artisan test`
   - **Hasil**: **381 passed (1679 assertions)** dalam 87.26s.
   - Tidak ada satu pun pengujian akademik, keuangan, keamanan, atau dokumentasi yang rusak atau terpengaruh.

3. **Pemeriksaan Format Kode (*Code Formatting*)**:
   - `composer run lint:check` (Laravel Pint): **Passed** (0 violations).

---

## 6. Rekomendasi Sebelum Fase 5.8.11 (Service & Policy Layer)

Untuk persiapan Fase 5.8.11:
1. Implementasikan `LearningMaterialService` yang membungkus logika bisnis CRUD, orkestrasi transaksi DB, dan penyimpanan berkas fisik ke `storage/app/materials/`.
2. Implementasikan `LearningMaterialPolicy` untuk menegakkan aturan hak akses guru pembuat materi dan pembatasan santri berdasarkan kelas terdaftar aktif.
3. Buat `StoreLearningMaterialRequest` dan `UpdateLearningMaterialRequest` dengan validasi ketat terhadap ukuran berkas (maksimal 25MB) dan ekstensi file yang diizinkan.
4. Jangan membuat tampilan UI sebelum Fase 5.8.12.
