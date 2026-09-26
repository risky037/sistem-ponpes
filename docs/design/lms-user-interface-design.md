---
title: Desain Antarmuka & Tata Letak LMS (LMS UI/UX Design)
category: design
role:
  - Administrator
  - Pengurus
  - Guru
estimated_time: 25 menit
difficulty: intermediate
version: 5.8.9
---

# Desain Antarmuka & Tata Letak LMS (LMS UI/UX Design)

Dokumen ini memuat spesifikasi desain antarmuka (*UI*) dan pengalaman pengguna (*UX*) untuk modul *Learning Management System* (LMS) DIGITREN. Desain ini dirancang selaras dengan filosofi desain pesantren: **tenang, terstruktur, ramah gawai (*mobile-friendly*), dan berorientasi pada kemudahan akses bahan ajar**.

---

## 1. Antarmuka Guru: Ruang Kelas & Dasbor Materi (*Classroom Dashboard*)

Antarmuka guru berfokus pada kemudahan mengelola bahan ajar berdasarkan konteks kelas yang diampu (*Teaching Assignment*).

### 1.1 Diagram Kawat Konseptual (*Wireframe: Guru Classroom Dashboard*)

```
┌────────────────────────────────────────────────────────────────────────┐
│ [LOGO DIGITREN]  Dashboard  Presensi  Nilai  [LMS Materi]   (Profil)   │
├────────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  ┌─ HERO BANNER: RUANG KELAS GURU ───────────────────────────────────┐ │
│  │ Ustadz Ahmad Fauzi, S.Pd.I                                        │ │
│  │ Tahun Ajaran: 2026/2027 - Semester Ganjil                         │ │
│  │ [ + Tambah Materi Baru ]  [ Kelola Berkas Saya ]                  │ │
│  └───────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  KELAS SAYA (TABS PENUGASAN AJAR):                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                  │
│  │ [VII-A Fiqih]│  │  VII-B Fiqih │  │ VIII-A Hadits│                  │
│  │ 15 Materi    │  │  12 Materi   │  │  8 Materi    │                  │
│  └──────────────┘  └──────────────┘  └──────────────┘                  │
│                                                                        │
│  DAFTAR MATERI PELAJARAN: KELAS VII-A (FIQIH)                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ [ICON PDF]  Bab Thaharah: Wudhu dan Tayammum     [PUBLISHED]     │  │
│  │ Mapel: Fiqih I  •  Target: VII-A, VII-B  •  Lampiran: 2 Berkas   │  │
│  │ Diperbarui: 24 Sep 2026  •  Unduhan: 42x                         │  │
│  │ [ Pratinjau ]  [ Edit Materi ]  [ Kelola Lampiran ]  [ Opsi ▾ ]   │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ [ICON VIDEO] Kajian Tata Cara Sholat Jenazah        [DRAFT]      │  │
│  │ Mapel: Fiqih I  •  Target: VII-A  •  Tautan: YouTube Video       │  │
│  │ Diperbarui: Kemarin, 14:20  •  Unduhan: 0x                       │  │
│  │ [ Lanjutkan Edit ]  [ Terbitkan Sekarang ]  [ Hapus ]            │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Anatomi Komponen Kartu Materi Guru (*Teacher Material Card Component*)
Setiap materi ajar ditampilkan dalam kartu yang padat informasi namun rapi:
1. **Header Kartu**:
   - Ikon jenis konten (`bx bxs-file-pdf` warna merah, `bx bxs-video` warna marun, `bx bx-file-blank` warna biru).
   - Judul materi tebal (`fw-bold font-15`).
   - Lencana status:
     - `Published`: `<span class="badge bg-light-success text-success radius-8">Ditayangkan</span>`
     - `Draft`: `<span class="badge bg-light-warning text-dark radius-8">Draf Guru</span>`
     - `Archived`: `<span class="badge bg-light-secondary text-secondary radius-8">Diarsipkan</span>`
2. **Metadata Baris**:
   - Label mapel dan rombel kelas target.
   - Indikator lampiran: Jumlah berkas dan ukuran total.
   - Cap waktu pembaruan dan metrik unduhan santri.
3. **Baris Aksi Cepat (*Quick Actions*)**:
   - Tombol utama untuk pratinjau dan sunting.
   - Menu aksi titik tiga (*dropdown*) untuk opsi lanjutan: Salin tautan, ubah target kelas, atau hapus draf.

---

## 2. Antarmuka Santri: Pustaka Belajar Mandiri (*Learning Library*)

Antarmuka santri dirancang layaknya katalog buku digital yang bersih, menyenangkan untuk dibaca, dan bebas dari kebingungan teknis.

### 2.1 Diagram Kawat Konseptual (*Wireframe: Santri Learning Library*)

```
┌────────────────────────────────────────────────────────────────────────┐
│ [DIGITREN SANTRI]  Beranda  Jadwal KBM  Nilai  [Pustaka Materi] (NIS) │
├────────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  ┌─ PUSTAKA DIGITAL SANTRI ──────────────────────────────────────────┐ │
│  │ Kelas: VII-A • Semester Ganjil 2026/2027                          │ │
│  │ Tersedia 27 materi ajar resmi dari asatidz                        │ │
│  └───────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  BARIS PENCARIAN & FILTER:                                             │
│  [ Cari materi, judul bab, atau nama ustadz...               ] [🔍]   │
│  Filter Mapel: [ Semua Mapel ▾ ]  Urutan: [ Terbaru ▾ ]               │
│                                                                        │
│  GRID MATERI PEMBELAJARAN:                                             │
│  ┌───────────────────────┐ ┌───────────────────────┐ ┌───────────────┐ │
│  │ [PDF KITAB]           │ │ [VIDEO KAJIAN]        │ │ [RINGKASAN]   │ │
│  │ Fiqih - Ust. Ahmad    │ │ Nahwu - Ust. Sholihin │ │ Shorof - Ust. │ │
│  │ Bab Wudhu & Tayammum  │ │ Mengenal I'rob Rafa'  │ │ Bina' Fi'il   │ │
│  │ 2 Berkas (PDF 2.4 MB) │ │ Video YouTube (18 m)  │ │ Teks & Nadhom │ │
│  │ [ Buka & Baca ]       │ │ [ Tonton Kajian ]     │ │ [ Buka Baca ] │ │
│  │ [ ⬇ Unduh PDF ]       │ │                       │ │               │ │
│  └───────────────────────┘ └───────────────────────┘ └───────────────┘ │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Antarmuka Halaman Pembaca Santri (*Reading View*)
Ketika santri menekan tombol **"Buka & Baca"**, sistem membuka tampilan fokus baca (*Focus Reading Mode*):
- Lebar artikel dibatasi maksimal `840px` di tengah layar untuk kenyamanan retina mata.
- Tipografi menggunakan jenis huruf yang mudah dibaca (Inter / Roboto) dengan spasi baris `line-height: 1.7`.
- **Area Sematan Media (*Media Embed Zone*)**:
  - Jika materi memiliki video YouTube, pemutar video responsif (`ratio ratio-16x9`) disematkan di atas teks materi.
- **Area Lampiran Berkas (*Download Box*)**:
  - Kotak unduh berwarna hijau terang/putih bersih dengan nama berkas, ukuran, dan tombol **"Unduh Berkas Pegangan"**.

---

## 3. Spesifikasi Token Desain & Kelas CSS

Sesuai panduan *DIGITREN Design System*, modul LMS mengadopsi token berikut:

```css
/* Card Container */
.doc-lms-card {
    border-radius: 15px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.doc-lms-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08);
}

/* Content Type Badges */
.badge-content-pdf {
    background-color: #fee2e2;
    color: #dc2626;
}
.badge-content-video {
    background-color: #fef3c7;
    color: #d97706;
}
.badge-content-text {
    background-color: #e0f2fe;
    color: #0284c7;
}

/* Reading View Container */
.lms-reading-container {
    max-width: 840px;
    margin: 0 auto;
    font-size: 15.5px;
    line-height: 1.75;
    color: #2b2f32;
}
```

---

## 4. Struktur Rencana Berkas Tampilan Blade (*Blade View Hierarchy*)

Seluruh tampilan akan ditempatkan di bawah folder `resources/views/pages/lms/`:

```
resources/views/pages/lms/
├── teacher/
│   ├── index.blade.php           # Classroom Dashboard guru (pemilihan kelas & ringkasan)
│   ├── create.blade.php          # Form pembuatan materi baru & unggah berkas
│   ├── edit.blade.php            # Form penyuntingan materi & manajemen lampiran
│   └── show.blade.php            # Pratinjau materi versi guru
├── student/
│   ├── index.blade.php           # Katalog Pustaka Belajar Santri (Grid materi & filter)
│   └── read.blade.php            # Tampilan membaca materi & pemutar video santri
└── components/
    ├── material-card.blade.php   # Komponen kartu materi reusable
    ├── file-item.blade.php       # Komponen item berkas lampiran
    └── empty-state.blade.php     # Tampilan ramah saat belum ada materi
```

---

## 5. Pertimbangan Aksesibilitas & Kompatibilitas Ponsel (*Mobile Responsiveness*)

1. **Jarak Antar Tombol (*Hit Targets*)**:
   - Seluruh tautan dan tombol di versi ponsel memiliki tinggi minimal `42px` untuk mencegah salah tekan di layar sentuh.
2. **Optimasi Bandwidth di Asrama**:
   - Gambar *thumbnail* dan ikon menggunakan SVG dan font icon bawaan (`boxicons`), tanpa memuat pustaka eksternal yang lambat.
   - Berkas PDF dibuka di pembaca peramban bawaan (*native browser viewer*), tidak memaksa santri mengunduh berkas berulang kali jika hanya ingin membaca selintas.
