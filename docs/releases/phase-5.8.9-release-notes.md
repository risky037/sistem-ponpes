---
title: Catatan Rilis Fase 5.8.9 — LMS Domain Architecture & Database Schema Design
category: releases
role:
  - Administrator
  - Pengurus
estimated_time: 15 menit
difficulty: intermediate
version: 5.8.9
---

# Catatan Rilis Fase 5.8.9 — LMS Domain Architecture, Database Schema & Academic Integration Design

**Tanggal Rilis**: 26 September 2026  
**Status Fase**: Perancangan Arsitektur & Desain Sistem (*Architecture & Design Only*)  
**Target Modul**: *Learning Management System* (LMS) DIGITREN

---

## 1. Ringkasan Eksekutif

Fase 5.8.9 berhasil merampungkan cetak biru arsitektur lengkap (*complete architectural blueprint*) untuk modul **Learning Management System (LMS)** pada platform DIGITREN Pondok Pesantren Fatimah Az-Zahra. Perancangan ini menjembatani kebutuhan distribusi bahan ajar digital bagi asatidz dan santri tanpa mengorbankan kesederhanaan, stabilitas, dan budaya kepesantrenan.

Sesuai batasan ketat fase ini:
- **Nol Migrasi Basis Data**: Tidak ada migrasi atau tabel basis data baru yang dibuat atau dijalankan pada fase ini.
- **Nol Perubahan Struktur Eksisting**: Tidak ada perubahan pada tabel-tabel akademik yang telah berjalan (`teaching_assignments`, `academic_enrollments`, dll).
- **Integritas Penilaian Utuh**: Mekanisme penilaian dan komputasi nilai tertimbang pada `AssessmentService` dan `AcademicPerformanceService` dipertahankan 100% tanpa modifikasi.
- **Dokumentasi Sebagai Sumber Kebenaran Tunggal (*Single Source of Truth*)**: Seluruh spesifikasi arsitektur disimpan dalam format Markdown terstruktur yang terintegrasi langsung dengan Documentation Center DIGITREN.

---

## 2. Dokumen Arsitektur & Desain yang Dihasilkan

| Dokumen | Kategori | Ringkasan Isi |
| :--- | :--- | :--- |
| **[`lms-existing-domain-audit.md`](file:///home/adminfid/Documents/Projects/sistem-Ponpes-Fatimah-Az-Zahra/docs/architecture/lms-existing-domain-audit.md)** | Arsitektur | Audit menyeluruh terhadap 11 model akademik eksisting, batas kepemilikan data (*ownership boundaries*), titik sambung integrasi, dan mitigasi potensi konflik. |
| **[`lms-database-schema.md`](file:///home/adminfid/Documents/Projects/sistem-Ponpes-Fatimah-Az-Zahra/docs/architecture/lms-database-schema.md)** | Arsitektur | Spesifikasi skema DDL MySQL untuk tabel `learning_materials`, `learning_material_files`, `learning_material_targets`, dan `learning_material_views` (opsional), dilengkapi strategi indeks performa dan *soft deletes*. |
| **[`lms-authorization-design.md`](file:///home/adminfid/Documents/Projects/sistem-Ponpes-Fatimah-Az-Zahra/docs/architecture/lms-authorization-design.md)** | Arsitektur | Matriks hak akses peran (Admin, Guru, Santri, Pengurus) dan implementasi lengkap kelas `LearningMaterialPolicy` dengan perlindungan unduhan berkas. |
| **[`lms-service-architecture.md`](file:///home/adminfid/Documents/Projects/sistem-Ponpes-Fatimah-Az-Zahra/docs/architecture/lms-service-architecture.md)** | Arsitektur | Spesifikasi lapisan layanan `LearningMaterialService` untuk menangani CRUD materi, transaksi multi-tabel, pengelolaan lampiran berkas fisik, serta evaluasi pola *repository*. |
| **[`lms-user-interface-design.md`](file:///home/adminfid/Documents/Projects/sistem-Ponpes-Fatimah-Az-Zahra/docs/design/lms-user-interface-design.md)** | Desain | Rancangan kawat visual (*wireframes*), kartu materi guru, ruang baca fokus santri (*Focus Reading View*), token CSS, serta hirarki tampilan Blade. |
| **[`lms-roadmap.md`](file:///home/adminfid/Documents/Projects/sistem-Ponpes-Fatimah-Az-Zahra/docs/architecture/lms-roadmap.md)** | Arsitektur | Peta jalan rilis bertahap dari Fase 5.8.10 (Migrasi Basis Data) hingga Fase 5.8.13 (Peluncuran UI Guru & Santri). |

---

## 3. Keputusan Arsitektural Utama (*Key Architectural Decisions*)

1. **Pemisahan Tegas Antara LMS dan Evaluasi Akademik (*Separation of LMS and Assessment*)**:
   - LMS bertindak murni sebagai **Pustaka Bahan Ajar Digital (*Resource Library*)**.
   - Penilaian tetap 100% dikendalikan oleh modul `AssessmentComponent` dan `StudentAssessmentScore`.
   - Menghindari kerumitan sistem kuis otomatis yang rawan kendala jaringan di pesantren dan mengikis adab sorogan tatap muka.

2. **Pola Pemetaan Multi-Kelas (*Targeting Pivot Pattern*)**:
   - Materi tidak diikat secara kaku 1-ke-1 pada satu kelas tertentu, melainkan menggunakan tabel pivot `learning_material_targets`.
   - Guru yang mengajar mata pelajaran yang sama di beberapa rombel kelas (misal: Fiqih di Kelas VII-A, VII-B, VII-C) cukup mengunggah materi satu kali.

3. **Penyimpanan Berkas Terisolasi & Rute Bertanda Tangan (*Secure Storage Architecture*)**:
   - Berkas fisik disimpan di disk lokal privat (`storage/app/materials/`) tanpa tautan publik langsung.
   - Pengunduhan berkas wajib melalui rute ber-policy untuk memverifikasi hak akses santri sebelum file dialirkan (*streamed download*).

4. **Kesiapan Kinerja Tinggi (*High-Performance Indexing*)**:
   - Seluruh kombinasi kueri umum (pencarian berdasarkan ustadz pembuat, status publikasi, kelas target santri, dan tahun ajaran) telah dilengkapi indeks komposit pada tingkat DDL.

---

## 4. Hasil Verifikasi Kualitas (*Quality Verification*)

- **Migrasi**: 0 berkas migrasi baru (memenuhi batasan fase arsitektur).
- **Rangkaian Pengujian (*Regression Test Suite*)**: 373 pengujian fitur dan unit lolos 100% tanpa ada yang gagal (*0 failures*).
- **Standar Format Kode (*Linting Check*)**: Laravel Pint lulus dengan status `passed`.
- **Integritas Git**: Bersih dan siap untuk komit.

---

## 5. Rekomendasi Sebelum Memulai Fase 5.8.10

Sebelum memulai eksekusi Fase 5.8.10 (Migrasi Basis Data):
1. Pastikan lingkungan pengembangan dan produksi memiliki ruang disk yang cukup pada direktori `storage/app/` untuk menampung berkas modul materi.
2. Konfigurasikan batas ukuran unggah berkas pada `php.ini` (`upload_max_filesize = 30M`, `post_max_size = 35M`) agar sesuai dengan kapasitas unggah modul LMS.
3. Pertahankan isolasi modul: migrasi LMS tidak boleh menyentuh tabel eksisting selain menambahkan *foreign key* yang mengarah ke tabel `users`, `mapel`, `kelas`, dan `academic_years`.
