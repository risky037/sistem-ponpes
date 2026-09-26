# Desain Arsitektur Manajemen Pembelajaran Pesantren (Learning Management Design)

Dokumen ini memaparkan rancangan arsitektur modul **Learning Management System (LMS)** yang diadaptasi khusus untuk ekosistem Pondok Pesantren Fatimah Az-Zahra, terinspirasi dari kesederhanaan dan kepraktisan *Google Classroom*.

---

## 1. Filosofi & Visi Desain

Mayoritas sistem LMS modern (seperti Moodle, Canvas, atau Blackboard) dirancang untuk perguruan tinggi dengan fitur yang sangat kompleks: kuis adaptif, *plagiarism checker*, forum diskusi bertingkat, dan penilaian kelompok otomatis.

Namun, di lingkungan pondok pesantren dengan santri yang bermukim di asrama (*boarding school*):
1. **Waktu Akses Gawai Dibatasi**: Santri umumnya hanya mengakses komputer/gawai di laboratorium pada jam-jam tertentu atau melalui perangkat bersama.
2. **Kebutuhan Utama Adalah Sumber Belajar (Resource Library)**: Kebutuhan utama santri dan asatidz adalah ketersediaan modul digital, berkas PDF kitab kuning, ringkasan nadhom, dan rekaman video kajian asatidz sepuh.
3. **Pemberian Tugas Bersifat Klasikal**: Diskusi dan setoran tugas hafalan di pesantren sebagian besar tetap berlangsung secara tatap muka (musyawarah, sorogan, bandongan).

Oleh karena itu, modul LMS DIGITREN dirancang dengan pendekatan:
> **"Google Classroom Sederhana: Ringan, Cepat, dan Berfokus Penuh pada Distribusi Bahan Ajar."**

---

## 2. Ruang Lingkup Fungsional (*Functional Scope*)

```
                           ┌───────────────────────────┐
                           │      MODUL LMS GURU       │
                           │   (Pusat Kelola Materi)   │
                           └─────────────┬─────────────┘
                                         │ Mengunggah & Membagikan
                                         ▼
                           ┌───────────────────────────┐
                           │   BAHAN AJAR & REFERENSI  │
                           │  • Judul & Deskripsi      │
                           │  • Teks Bacaan Kaya       │
                           │  • Berkas Dokumen / PDF   │
                           │  • Video Kajian / Link    │
                           └─────────────┬─────────────┘
                                         │ Akses Baca & Unduh
                                         ▼
                           ┌───────────────────────────┐
                           │     PORTAL LMS SANTRI     │
                           │     (Learning Library)    │
                           └───────────────────────────┘
```

### 2.1 Ruang Lingkup Guru / Asatidz
Guru memiliki wewenang penuh dalam konteks kelas ajar yang diampunya:
1. **Konteks Kelas Terikat**: Konteks ruang kelas tidak dibuat manual dari nol, melainkan **langsung mewarisi penugasan mengajar aktif** (`TeachingAssignment`: Guru + Mapel + Kelas + Tahun Ajaran).
2. **Operasi CRUD Materi Lengkap**:
   - **Membuat Materi Baru (*Create*)**: Judul, uraian singkat, teks materi kajian lengkap, serta status tayang (*Draft* atau *Published*).
   - **Melampirkan Berkas (*Files*)**: Mengunggah satu atau beberapa berkas dokumen (PDF kitab, Word, PowerPoint) dengan batas ukuran aman.
   - **Melampirkan Media Eksternal**: Memasukkan URL video kajian (YouTube) atau tautan penyimpanan awan (Google Drive).
   - **Memperbarui Materi (*Update*)**: Menyunting isi teks, memperbarui berkas lampiran, atau mengubah kelas target.
   - **Menghapus / Mengarsipkan (*Delete*)**: Menghapus materi ajar secara aman.

### 2.2 Ruang Lingkup Santri (Read-Only)
Santri berperan sebagai konsumen pengetahuan dengan akses baca murni:
1. **Melihat Katalog Materi**: Santri melihat daftar materi yang relevan dengan kelas tempat santri terdaftar (`AcademicEnrollment`).
2. **Membaca Konten Teks**: Membaca catatan kajian dan penjelasan materi di antarmuka web yang bersih dan bebas distraksi.
3. **Mengunduh Berkas Lampiran**: Mengunduh berkas PDF pegangan ke komputer/perangkat untuk dibaca luring (*offline*).
4. **Membuka Tautan Video**: Membuka tautan YouTube atau referensi kajian luar yang disematkan oleh guru.

---

## 3. Fitur yang Sengaja Ditiadakan & Alasan Arsitekturnya

Untuk menjaga modul tetap ringan, stabil, dan relevan dengan kultur pesantren, fitur-fitur berikut **secara sengaja tidak disertakan**:

### 1. Mesin Kuis Otomatis (*Quiz Engine*)
- **Alasan**: Pembuatan soal kuis interaktif (pilihan ganda berbasis waktu, pengacakan soal) membutuhkan infrastruktur server real-time yang berat dan rentan terhadap kendala jaringan internet di daerah pesantren. Penilaian santri di pesantren lebih teruji melalui ujian berkala dan evaluasi komprehensif yang telah difasilitasi oleh modul `AssessmentComponent`.

### 2. Pengumpulan Berkas Tugas Santri (*Assignment Submission*)
- **Alasan**: Jika ribuan santri mengunggah foto lembar jawaban atau berkas tugas ke server lokal pesantren, ruang penyimpanan (*disk space*) server akan cepat habis, memicu beban backup yang besar. Evaluasi tugas di pesantren lebih efektif dikumpulkan fisik atau disetor lisan (sorogan) dan nilainya diinput langsung oleh asatidz ke form `StudentAssessmentScore`.

### 3. Forum Diskusi & Fitur Obrolan (*Discussion Forum & Chat*)
- **Alasan**: 
  - Budaya kepesantrenan sangat mengutamakan adab bertatap muka langsung saat bertanya kepada guru (*ta'dhim*).
  - Fitur obrolan bebas sering kali membutuhkan moderasi ketat dari pengurus asrama agar santri tidak menyalahgunakan platform untuk percakapan di luar jam belajar.
  - Menghilangkan *chat engine* menghindarkan ketergantungan pada koneksi WebSocket yang menghabiskan memori server.

---

## 4. Keunggulan Desain Ini

1. **Hemat Sumber Daya Server**: Tidak memerlukan Redis cluster atau layanan socket pihak ketiga, cukup berjalan dengan stack standar PHP 8.2+ dan MySQL.
2. **Waktu Adopsi Cepat (*Zero Learning Curve*)**: Guru dan santri tidak bingung dengan ratusan tombol yang rumit. Tampilan sederhana membuat asatidz senior sekalipun langsung mahir menggunakannya dalam hitungan menit.
3. **Penyimpanan Terkendali**: Dengan hanya memfasilitasi unggah materi dari asatidz (bukan kiriman berkas dari ribuan santri), pertumbuhan data penyimpanan pondok dapat diprediksi dengan sangat presisi.
