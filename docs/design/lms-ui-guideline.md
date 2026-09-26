# Pedoman Desain Antarmuka & Pengalaman Pengguna LMS (LMS UI/UX Guideline)

Dokumen ini mendefinisikan panduan antarmuka (*UI*) dan pengalaman pengguna (*UX*) untuk modul *Learning Management System* (LMS) DIGITREN. Seluruh rancangan mematuhi standar desain visual, kelas utilitas CSS, dan filosofi kesederhanaan yang telah diterapkan pada portal guru dan santri eksisting.

---

## 1. Filosofi Desain DIGITREN Pesantren

1. **Amanah, Tenang, dan Bersih**: Menggunakan perpaduan warna hijau korporat islami, biru toska (*teal*), serta latar belakang abu-abu terang (*light slate*) yang nyaman di mata untuk membaca kitab dan teks panjang.
2. **Kerapian Kartu (*Card-Driven Layout*)**: Setiap informasi dikelompokkan ke dalam kartu dengan lengkungan halus (`radius-15`), bayangan lembut (`shadow-sm`), dan garis tepi terukur (`border`).
3. **Ramah Ponsel (*Mobile-First Usability*)**: Sebagian besar asatidz dan santri mengakses aplikasi menggunakan ponsel pintar berlayar kecil. Tombol dan elemen interaktif dirancang memiliki target sentuh minimal 44 piksel (*touch-friendly*).

---

## 2. Token Warna & Palet Visual Eksisting

Modul LMS wajib menggunakan token warna dan gradien resmi DIGITREN:

| Nama Token | Nilai / Kelas CSS | Peruntukan di Modul LMS |
| :--- | :--- | :--- |
| **Brand Gradient** | `bg-brand-gradient` (Linear Green-Teal) | Kartu Header Sambutan (*Hero Banner*) di bagian atas halaman. |
| **Primary** | `bg-primary` / `text-primary` (`#0d6efd` / `#1e7e34`) | Tombol utama, lencana navigasi aktif, dan ikon kartu materi. |
| **Teal / Accent** | `bg-teal` / `text-teal` (`#20c997`) | Lencana modul materi kitab, tag kategori kepesantrenan. |
| **Success** | `bg-success` / `text-success` (`#198754`) | Status materi "Published", tombol unduh berkas yang valid. |
| **Warning** | `bg-warning` / `text-warning` (`#ffc107`) | Status "Draft" (belum tayang), pengingat ukuran berkas. |
| **Secondary / Light** | `bg-light` / `text-muted` (`#6c757d`) | Tanggal publikasi, keterangan nama penulis, ukuran berkas bita. |

---

## 3. Alur Pengalaman Guru (Teacher Experience)

Alur antarmuka guru berfokus pada kemudahan mengelola materi dalam konteks kelas:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        ALUR PENGALAMAN GURU (LMS)                      │
│                                                                        │
│   Portal Guru ──► Ruang Kelas Saya ──► Pilih Mapel ──► Kelola Materi   │
│                                                            │           │
│                                                            ├─► Buat    │
│                                                            ├─► Edit    │
│                                                            └─► Bagikan │
└────────────────────────────────────────────────────────────────────────┘
```

### Komponen Dasbor Guru (Classroom Dashboard):
1. **Hero Header**:
   - Menampilkan salam, nama guru, mata pelajaran, dan tombol aksi cepat **"Tambah Materi Baru"**.
2. **Filter Kelas Rombel**:
   - Selektor *Pill Tabs* untuk berpindah antar kelas ampuan (misal: `[ Kelas VII-A ]` `[ Kelas VII-B ]`).
3. **Daftar Kartu Materi Guru**:
   - Memuat judul materi, lencana status (*Draft* / *Published*), jumlah berkas lampiran, dan menu opsi titik tiga (*Edit, Salin Tautan, Hapus*).
4. **Modal Form Input Materi Baru**:
   - Input judul materi.
   - Textarea catatan kajian dengan editor teks ringkas.
   - Kotak seret-dan-lepas (*drag & drop*) untuk unggah berkas PDF/dokumen.
   - Input URL YouTube / tautan Google Drive dengan validasi format instan.

---

## 4. Alur Pengalaman Santri (Student Experience)

Santri disajikan antarmuka **Perpustakaan Belajar Santri (*Learning Library*)** yang bersih layaknya membaca buku digital:

```
┌────────────────────────────────────────────────────────────────────────┐
│                       ALUR PENGALAMAN SANTRI (LMS)                     │
│                                                                        │
│   Portal Santri ──► Tab Materi Belajar ──► Kartu Materi ──► Baca/Unduh │
└────────────────────────────────────────────────────────────────────────┘
```

### Tata Letak Halaman Santri:
1. **Baris Pencarian & Penyaring Cepat**:
   - Kolom pencarian kata kunci materi (misal: "wudhu", "nadhom", "i'rob").
   - Dropdown filter mata pelajaran (Fiqih, Nahwu, Hadits, Matematika).
2. **Grid Kartu Materi (Responsive Cards: 1 kolom di HP, 3 kolom di Desktop)**:
   - Header kartu: Ikon tipe konten (ikon PDF dokumen merah, ikon Video merah marun, ikon Teks biru).
   - Nama mata pelajaran dan nama ustadz pengampu.
   - Judul materi tebal dan ringkasan 2 baris teks.
   - Tombol utama: **"Buka Materi"** dan tombol sekunder **"Unduh PDF"**.
3. **Halaman Pembaca Materi (Reading View)**:
   - Antarmuka ramah mata dengan lebar baca ideal (maksimal 800px rata tengah).
   - Pemutar video tersemat (*embedded responsive YouTube player*) jika materi memuat tautan video.
   - Kotak unduhan berkas lampiran lengkap dengan ikon format berkas dan indikator ukuran file (misal: `Kitab_Fiqih.pdf - 2.4 MB`).

---

## 5. Komponen Desain Standar DIGITREN

### 5.1 Desain Kartu (*Card Component*)
```html
<div class="card radius-15 border shadow-sm mb-3">
    <div class="card-body p-4">
        <!-- Konten Kartu -->
    </div>
</div>
```

### 5.2 Desain Kondisi Kosong (*Empty State*)
Jika belum ada materi yang dibagikan guru, tampilkan ilustrasi kosong yang informatif:
```html
<div class="card radius-15 border shadow-sm p-5 text-center">
    <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary mx-auto mb-3">
        <i class="bx bx-book-open font-30"></i>
    </div>
    <h5 class="fw-bold mb-1">Belum Ada Materi Pelajaran</h5>
    <p class="text-muted mb-3 font-14">Ustadz pengampu belum membagikan modul atau berkas kajian untuk kelas ini.</p>
</div>
```

### 5.3 Desain Indikator Memuat (*Loading State*)
Saat proses pencarian atau pengunggahan berkas sedang berlangsung:
```html
<div class="text-center py-4">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Memuat data...</span>
    </div>
    <p class="text-muted mt-2 font-13">Menyiapkan materi belajar...</p>
</div>
```

### 5.4 Desain Tabel Ringkasan (*Table Style*)
```html
<div class="table-responsive">
    <table class="table align-middle mb-0 table-hover">
        <thead class="table-light">
            <tr>
                <th class="ps-3">Judul Materi</th>
                <th>Mata Pelajaran</th>
                <th>Status</th>
                <th>Lampiran</th>
                <th class="text-end pe-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <!-- Baris Data -->
        </tbody>
    </table>
</div>
```
