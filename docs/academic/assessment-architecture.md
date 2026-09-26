# Tinjauan Arsitektur Penilaian & Evaluasi Akademik (Assessment Architecture)

Dokumen ini membedah arsitektur teknis dan logika domain penilaian (*assessment domain*) yang telah berjalan di DIGITREN, serta merumuskan batasan tegas dan strategi integrasi masa depan antara sistem penilaian dan modul *Learning Management System* (LMS).

---

## 1. Analisis Entitas & Layanan Penilaian Eksisting

Arsitektur evaluasi akademik saat ini disusun oleh 3 model Eloquent utama dan 2 service domain:

```
┌───────────────────────────┐
│   AssessmentDefinition    │ ◄── Master skema evaluasi institusi per tahun ajaran
└─────────────┬─────────────┘
              │ 1:N
              ▼
┌───────────────────────────┐
│    AssessmentComponent    │ ◄── Komponen penilaian kelas per TeachingAssignment
└─────────────┬─────────────┘
              │ 1:N
              ▼
┌───────────────────────────┐
│   StudentAssessmentScore  │ ◄── Nilai kuantitatif per santri (AcademicEnrollment)
└───────────────────────────┘
```

### 1.1 `AssessmentDefinition`
- **Tanggung Jawab**: Mendefinisikan standar evaluasi pesantren pada satu tahun ajaran (`academic_year_id`).
- **Atribut Kunci**:
  - `name`: Nama kategori (misal: "Ujian Akhir Semester Ganjil").
  - `type`: Tipe evaluasi resmi dari konstanta `ALLOWED_TYPES` (`UTS`, `UAS`, `Tugas`, `Praktik`, `Ulangan Harian`, `Lainnya`).
  - `weight`: Bobot standar persentase bawaan (0 - 100%).
  - `is_active`: Status keaktifan skema.

### 1.2 `AssessmentComponent`
- **Tanggung Jawab**: Instansiasi riil penilaian pada suatu penugasan mengajar tertentu (`teaching_assignment_id`).
- **Atribut Kunci**:
  - `teaching_assignment_id`: Menghubungkan guru, mapel, kelas, dan tahun ajaran.
  - `assessment_definition_id`: Merujuk pada aturan induk yang berlaku.
  - `weight`: Bobot penilaian spesifik untuk kelas tersebut (bisa mewarisi atau menimpa bobot master).

### 1.3 `StudentAssessmentScore`
- **Tanggung Jawab**: Mencatat rekaman nilai individual santri.
- **Atribut Kunci**:
  - `assessment_component_id`: Mengaitkan ke komponen penilaian terkait.
  - `academic_enrollment_id`: Mengaitkan ke santri aktif yang terdaftar di kelas rombel tersebut.
  - `score`: Angka nilai desimal presisi dua angka (`decimal:2`, rentang `0.00` sampai `100.00`).
  - `notes`: Catatan evaluasi guru terhadap santri.
  - `graded_by`: ID user guru/penilai yang melakukan input nilai.
  - `graded_at`: Cap waktu saat nilai dimasukkan atau disunting.

### 1.4 `AssessmentService`
- Menyediakan metode transaksi bisnis aman yang dibungkus `DB::transaction`:
  - `createDefinition()` / `updateDefinition()` / `deactivateDefinition()`: Pengelolaan skema institusional dengan kebijakan *soft-deactivation* jika sudah memiliki nilai riil.
  - `createComponent()` / `deleteComponent()`: Menjaga konsistensi tahun ajaran antara SK mengajar dan definisi penilaian.
  - `recordScore()` / `bulkRecordScores()` / `updateScore()`: Validasi integritas rentang nilai, validasi kepesertaan santri di kelas rombel aktif (`validateEnrollmentForComponent`), dan penyimpanan massal.

### 1.5 `AcademicPerformanceService`
- Bertanggung jawab atas agregasi performa santri:
  - Mengambil seluruh nilai santri (`StudentAssessmentScore`) yang terhubung dengan kelas aktifnya.
  - Menghitung nilai berbobot (*weighted score*) dan rata-rata akhir (*average score*).
  - Menggabungkan data absensi (`AttendanceRecord`) untuk menghasilkan rasio kehadiran (*attendance rate*).
  - Menyimpan kalkulasi ke tabel cache performa `AcademicPerformanceSummary` dengan status (*Kosong, Sebagian, Lengkap*).

---

## 2. Jawaban atas 5 Pertanyaan Kunci Evaluasi

### 1. Bagaimana alur kerja penilaian saat ini?
Alur kerja penilaian diawali oleh penetapan master `AssessmentDefinition` oleh Administrator/Pengurus per tahun ajaran. Setelah itu, Guru Pengampu mengambil definisi tersebut untuk membentuk `AssessmentComponent` pada kelas yang diampunya, lalu melakukan input nilai santri (`StudentAssessmentScore`) baik per santri maupun secara massal per kelas. Nilai yang tersimpan kemudian memicu pembaruan `AcademicPerformanceSummary`.

### 2. Di mana komponen penilaian didefinisikan?
Komponen penilaian (`AssessmentComponent`) didefinisikan pada tingkat **Penugasan Mengajar Guru** (`TeachingAssignment`). Hal ini memberikan fleksibilitas tinggi: meskipun pesantren menetapkan master jenis ujian yang seragam, guru pengampu memiliki otonomi untuk mengaktifkan komponen yang relevan dengan metode pengajaran di kelasnya masing-masing.

### 3. Bagaimana bobot penilaian (*weights*) dikelola?
Bobot penilaian dikelola dengan sistem dua tingkat (*two-tier weighting*):
- **Tingkat Institusi**: Nilai awal ditentukan di `AssessmentDefinition.weight`.
- **Tingkat Komponen Kelas**: Guru dapat mewarisi bobot standar tersebut atau menentukan bobot tersendiri di `AssessmentComponent.weight` (skala 0 - 100%).
- Saat agregasi nilai rapor dihitung oleh `AcademicPerformanceService`, rumus pembobotan yang digunakan adalah:
  $$\text{Rata-rata Nilai} = \frac{\sum (\text{Nilai Santri} \times \text{Bobot Komponen})}{\sum \text{Bobot Komponen Terpakai}}$$

### 4. Bagaimana cara guru menginput nilai?
Guru menginput nilai melalui formulir terpadu berbasis web:
- Halaman `manageScore` memuat daftar seluruh santri aktif dalam kelas rombel yang bersangkutan.
- Guru menginput angka desimal (skala 0 - 100) dan catatan kualitatif santri.
- Ketika formulir dikirim, sistem menggunakan metode `bulkRecordScores()` yang melakukan `updateOrCreate` secara atomik di dalam database transaction, mencatat ID guru penilai (`graded_by = auth()->id()`) dan waktu penilaian (`graded_at = now()`).

### 5. Bagaimana LMS harus berinteraksi dengan sistem penilaian?
LMS **TIDAK BOLEH** mengubah atau mengambil alih tabel-tabel evaluasi penilaian yang sudah ada. Interaksi harus bersifat pasif dan terpisah secara tegas (loose coupling).

---

## 3. Keputusan Arsitektur Kritis: Materi Belajar vs Penilaian

Dalam sistem pendidikan berbasis teknologi, sering terjadi kerancuan antara **Materi Belajar (*Learning Material*)** dan **Penilaian (*Assessment*)**. DIGITREN menetapkan pemisahan konsep ini secara fundamental:

```
┌──────────────────────────────────────────────┐  ┌──────────────────────────────────────────────┐
│        MATERI BELAJAR (LMS RESOURCE)         │  │             PENILAIAN (ASSESSMENT)           │
├──────────────────────────────────────────────┤  ├──────────────────────────────────────────────┤
│ • Sumber literasi, kajian, dan referensi     │  │ • Instrumen evaluasi capaian belajar        │
│ • Berkas PDF kitab kuning, dokumen modul     │  │ • Nilai kuantitatif terukur (skala 0 - 100)  │
│ • Tautan video YouTube rekaman pengajian     │  │ • Bobot persentase evaluasi kelas            │
│ • Sifat: Akses baca & unduh mandiri (Santri) │  │ • Berdampak pada rapor santri & kelulusan    │
│ • Tidak memiliki skor angka atau bobot rapor │  │ • Dikelola ketat oleh guru & tata usaha      │
│ • Bebas batasan waktu pengerjaan             │  │ • Terikat kalender UTS, UAS, Ulangan Harian  │
└──────────────────────────────────────────────┘  └──────────────────────────────────────────────┘
```

### Mengapa Pemisahan Ini Mutlak Diperlukan?
1. **Integritas Rapor Santri**: Nilai santri pada tabel `student_assessment_scores` dan agregat pada `academic_performance_summaries` adalah dokumen hukum akademis yang digunakan untuk penerbitan rapor, kelulusan, dan akreditasi madrasah. Mencampur berkas bacaan materi ke dalam tabel penilaian akan merusak rumus kalkulasi bobot rata-rata.
2. **Kenyamanan Belajar**: Santri dapat mengakses materi belajar berulang kali untuk muraja'ah tanpa terbebani kekhawatiran nilai rapor berubah jika mereka sekadar mengunduh atau membaca dokumen kitab.
3. **Penyederhanaan Modul**: Menjaga modul LMS tetap fokus pada penyediaan materi (ala perpustakaan digital kelas) tanpa komplikasi logika kuis atau batas waktu pengumpulan tugas (*assignment submission engine*).

---

## 4. Rekomendasi Integrasi Masa Depan

Bila di masa mendatang lembaga pesantren menginginkan kuis atau tugas berbasis unggah dokumen dari santri:
1. **Pertahankan Isolasi Domain**: Bangun modul kuis/pengumpulan tugas sebagai entitas pelengkap yang terpisah (`student_submissions`).
2. **Kaitkan Melalui Komponen Nilai**: Jadikan hasil kuis/tugas tersebut sebagai *sumber nilai* yang diumpankan (*fed*) ke dalam `StudentAssessmentScore` pada `AssessmentComponent` yang relevan via Service Layer, bukan dengan mengubah skema database penilaian yang sudah ada.
3. **Pertahankan Single Source of Truth**: Tabel `student_assessment_scores` tetap menjadi satu-satunya sumber data resmi bagi kalkulasi nilai akhir dan pencetakan rapor santri.
