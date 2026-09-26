# Desain Arsitektur Pusat Dokumentasi DIGITREN (Documentation Center Architecture)

Dokumen ini memaparkan arsitektur teknis dari platform **Documentation Center** terintegrasi pada sistem informasi DIGITREN (Pondok Pesantren Fatimah Az-Zahra). Modul ini membaca berkas-berkas dokumentasi Markdown yang berada di dalam direktori `/docs` sebagai sumber kebenaran tunggal (*single source of truth*) dan merendernya menjadi portal dokumentasi web modern, interaktif, cepat, dan aman tanpa menggunakan tabel basis data khusus.

---

## 1. Diagram Arsitektur & Alur Kerja (*Architecture Diagram*)

```mermaid
flowchart TD
    subgraph Client ["Klien / Peramban Pengguna"]
        UI_Home["/panduan (Katalog & Pencarian)"]
        UI_Reader["/panduan/{category}/{slug} (Reader)"]
        Search_JS["Client-side Search (Debounce)"]
    end

    subgraph Controller_Layer ["Controller Layer"]
        DocController["DocumentationController"]
    end

    subgraph Service_Layer ["Service & Engine Layer"]
        DocService["DocumentationService"]
        Registry["DocumentationRegistry"]
        Parser["MarkdownParser (CommonMark + Custom)"]
    end

    subgraph Storage_Cache ["Penyimpanan & Cache"]
        MD_Files["/docs Markdown Files (Single Source of Truth)"]
        Laravel_Cache["Laravel Cache (File Modification Check)"]
        Search_Index["search-index.json (Role-Filtered)"]
    end

    UI_Home -->|GET /panduan| DocController
    UI_Reader -->|GET /panduan/{cat}/{slug}| DocController
    Search_JS -->|GET /panduan/search-index.json| DocController

    DocController --> DocService
    DocService --> Registry
    DocService --> Laravel_Cache
    Laravel_Cache -. Cache Miss .-> MD_Files
    DocService --> Parser
    Parser -->|Rendered HTML + TOC| DocService
    DocService --> DocController
    DocController --> UI_Reader
```

---

## 2. Struktur Direktori Kode (*Folder Structure*)

```
app/
 ├── Http/
 │    └── Controllers/
 │          └── DocumentationController.php       # Controller pengatur rute & tampilan
 └── Services/
      └── Documentation/
            ├── DocumentationRegistry.php          # Konfigurasi metadata, katalog & role guard
            ├── MarkdownParser.php                 # Parser Markdown, Mermaid, callouts & TOC
            └── DocumentationService.php           # Service layer, navigasi, cache & search index

resources/
 └── views/
      └── pages/
           └── documentation/
                 ├── index.blade.php               # Beranda pusat dokumentasi, KPI & pencarian
                 └── show.blade.php                # Reader dokumen interaktif, progress bar & TOC

routes/
 └── web.php                                       # Rute /panduan, search-index, dan /{category}/{slug}
```

---

## 3. Alur Perenderan Dokumen (*Rendering Flow*)

1. **Permintaan Pengguna (*User Request*)**:
   - Pengguna membuka URL `/panduan/user-guide/pengenalan-sistem`.
2. **Otorisasi & Metadata Resolusi**:
   - `DocumentationService` meminta metadata dokumen dari `DocumentationRegistry`.
   - Menggunakan Spatie Permission untuk memvalidasi peran pengguna saat ini terhadap array izin dokumen (`canAccess`). Bila dilarang, sistem mengembalikan respon `404 Not Found` (mencegah enumerasi berkas).
3. **Pengecekan Cache Pintar (*Smart Cache Lookup*)**:
   - Sistem mengambil waktu perubahan fisik berkas (`filemtime($filePath)`).
   - Kunci cache dibentuk dengan format: `documentation:doc:{category}:{slug}:{filemtime}`.
   - Bila berkas belum diubah dan cache tersedia, HTML hasil parse dikembalikan secara instan tanpa proses ulang.
4. **Transformasi Sintaksis Lanjutan (*Enhanced Markdown Parsing*)**:
   - Bila cache belum ada:
     - **Callout Syntax**: Mengonversi `::: warning` dan `> [!NOTE]` menjadi kartu alert bernuansa DIGITREN.
     - **Mermaid Block Protection**: Mengisolasi blok ```mermaid``` menggunakan token aman `%%MERMAID_BLOCK_n%%` agar tidak terpengaruh parsing markdown standar, kemudian membungkusnya ke dalam kontainer `<div class="mermaid">` untuk dirender oleh Mermaid.js di sisi klien.
     - **CommonMark Parsing**: Mengubah teks markdown ke HTML aman via `Str::markdown()`.
     - **Table Responsiveness**: Membungkus seluruh elemen `<table>` dengan `<div class="table-responsive">` dan kelas Bootstrap `table table-bordered table-hover`.
     - **Heading Anchors & TOC**: Mendeteksi tag `<h1>`, `<h2>`, dan `<h3>`, memberikan atribut `id="slug"`, dan menyusun array *Table of Contents* untuk navigasi samping.
     - **Code Blocks**: Menambahkan tombol "Salin Kode" pada setiap blok kode pemrograman.
5. **Navigasi Maju/Mundur**:
   - `getNavigation()` meratakan seluruh dokumen yang diizinkan untuk peran pengguna, menemukan dokumen sebelumnya (*Previous*) dan dokumen berikutnya (*Next*).

---

## 4. Mekanisme Pencarian Cepat (*Search Mechanism*)

Pencarian dirancang dengan prinsip **klien-sentris tanpa database (*zero database dependency*)**:
1. **Endpoint `search-index.json`**:
   - Rute `GET /panduan/search-index.json` menghasilkan ringkasan terstruktur dari seluruh dokumen yang diizinkan untuk peran pengguna.
   - Data indeks memuat: `title`, `category`, `category_name`, `slug`, `url`, `excerpt` (ringkasan 180 karakter), dan `keywords` (potongan heading).
   - Di-cache di sisi server selama 24 jam per peran pengguna.
2. **Pencarian Sisi Klien (*Client-Side Debounce*)**:
   - Pada saat pengguna mengetik di bilah pencarian `#doc_search`:
     - Input ditahan selama 200ms (*debounce*) untuk menghindari lag performa.
     - Melakukan pencarian substring instan pada `title`, `keywords`, dan `excerpt`.
     - Menampilkan dropdown hasil pencarian lengkap dengan lencana kategori dan cuplikan kalimat yang dapat diklik langsung.

---

## 5. Model Keamanan & Otorisasi (*Security Model*)

1. **Isolasi Rute (*Auth Guard*)**:
   - Seluruh rute `/panduan` berada di bawah middleware `['auth']`, mewajibkan pengguna login terlebih dahulu.
2. **Otorisasi Berbasis Peran (*Role-Based Granular Access*)**:
   - `Administrator`: Memiliki hak penuh melihat seluruh kategori (User Guide, Akademik, Arsitektur, Desain, Rilis, Keuangan, dsb.).
   - `Pengurus`: Memiliki akses ke panduan operasional, alur akademik, dasbor intelijen, dan tata usaha.
   - `Guru`: Memiliki akses ke panduan guru, absensi, input nilai, modul LMS, dan audit alur ajar.
   - `Santri`: Dibatasi secara ketat hanya pada panduan mandiri santri dan tata cara membuka materi belajar.
   - `Keuangan`: Mengakses panduan tabungan santri, uang saku harian, dan mutasi kas.
3. **Pencegahan Path Traversal & Penetrasi**:
   - Parameter `{category}` dan `{slug}` divalidasi ketat oleh `DocumentationRegistry`.
   - Tidak ada fungsi `include` langsung dari input URL liar. Nama file dipetakan dari array statis atau divalidasi memiliki ekstensi `.md` di dalam direktori resmi.
4. **Pembersihan XSS (*Cross-Site Scripting*)**:
   - Teks masukan dan label callout diescape menggunakan `htmlspecialchars()` dengan flag `ENT_QUOTES`.

---

## 6. Skalabilitas & Rencana Masa Depan (*Scalability*)

- **Menambah Dokumen Baru**: Cukup tambahkan berkas Markdown ke dalam folder `/docs/{category}/nama-file.md` dan daftarkan ke `DocumentationRegistry`.
- **Integrasi Cetak PDF**: Di masa mendatang, modul ini dapat dipasangi fungsi cetak PDF manual SOP per bab bagi pengurus pesantren.
- **Pembaruan Offline**: Berkas markdown dapat terus disunting melalui git repository tanpa perlu menjalankan perintah seeder atau migrasi database apa pun di server produksi.
