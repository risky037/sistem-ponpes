# 08. Alur Penilaian & Evaluasi Akademik (Penilaian Workflow)

Buku panduan ini menjelaskan tata cara penyelenggaraan evaluasi belajar santri di DIGITREN, mulai dari perumusan kategori ujian oleh Administrator/Pengurus hingga penginputan nilai harian oleh Dewan Asatidz.

---

## 1. Menyiapkan Definisi Penilaian Master (Oleh Administrator/Pengurus)

Sebelum guru dapat memasukkan nilai, lembaga pesantren menetapkan aturan baku mengenai jenis evaluasi yang berlaku pada tahun ajaran tersebut.

### Mengapa Langkah Ini Diperlukan?
Standarisasi kategori penilaian memastikan seluruh mata pelajaran memiliki struktur penilaian yang seragam (misal: ada komponen Tugas, Ulangan Harian, UTS, dan UAS) dengan proporsi bobot yang adil.

### Kategori Resmi yang Disediakan Sistem:
- **UTS**: Ujian Tengah Semester.
- **UAS**: Ujian Akhir Semester.
- **Tugas**: Tugas mandiri, PR, atau hafalan bait nadhom.
- **Praktik**: Praktik ibadah, qira'at kitab, atau laboratorium.
- **Ulangan Harian**: Evaluasi berkala per bab materi.
- **Lainnya**: Penilaian adab, kedisiplinan, atau kegiatan ekstra.

### Langkah-Langkah:
1. Masuk sebagai **Administrator** atau **Pengurus**.
2. Buka menu **Penilaian** > **Definisi Penilaian**.
3. Pastikan Tahun Ajaran Aktif telah terpilih.
4. Klik tombol **Tambah Definisi Penilaian**.
5. Isi rincian:
   - Nama Penilaian: (contoh: *Ujian Tengah Semester Ganjil*).
   - Tipe: Pilih salah satu tipe resmi (misal: *UTS*).
   - Bobot Standar: Masukkan angka persentase (misal: `30` untuk 30%).
   - Status: Pastikan dicentang **Aktif**.
6. Klik **Simpan**. Ulangi untuk komponen lain (misal: Tugas 20%, Ulangan Harian 20%, UAS 30%).

---

## 2. Mengaitkan Komponen Nilai ke Kelas Ajar (Oleh Guru)

Setelah master definisi dibuat oleh pengurus, setiap guru pengampu mengaktifkan komponen tersebut pada kelas yang diampunya.

### Langkah-Langkah:
1. Masuk sebagai **Guru** / Asatidz.
2. Buka menu **Penilaian** > **Komponen Penilaian** (atau melalui **Portal Guru**).
3. Klik tombol **Tambah Komponen Penilaian**.
4. Pilih **Penugasan Mengajar** Anda (misal: *Fiqih - Kelas VII-A*).
5. Pilih **Definisi Penilaian** yang ingin digunakan (misal: *Tugas*, *UTS*, atau *UAS*).
6. Tentukan Bobot Kelas (bisa mengikuti bobot standar atau disesuaikan).
7. Klik **Simpan Komponen**.

---

## 3. Menginput Nilai Santri Secara Massal (Bulk Score Input)

Setelah komponen penilaian terbentuk, guru dapat memasukkan nilai untuk seluruh santri di kelas tersebut dalam satu halaman praktis.

```
┌────────────────────────────────────────────────────────────────────────┐
│ PENGELOLAAN NILAI: KELAS VII-A — MATA PELAJARAN: FIQIH                 │
│ Komponen: Tugas 1 (Bobot: 20%)                                         │
├─────┬───────────────────────┬──────────────┬───────────────────────────┤
│ No  │ Nama Santri           │ Nilai (0-100)│ Catatan Guru              │
├─────┼───────────────────────┼──────────────┼───────────────────────────┤
│ 1   │ Ahmad Fauzi           │ [   88.50  ] │ Tulisan rapi, fasih       │
│ 2   │ Muhammad Zaki         │ [   92.00  ] │ Sangat menguasai matan    │
│ 3   │ Siti Fatimah          │ [   85.00  ] │ Memuaskan                 │
└─────┴───────────────────────┴──────────────┴───────────────────────────┘
```

### Langkah-Langkah:
1. Pada daftar komponen penilaian, klik tombol biru **Kelola Nilai**.
2. Sistem akan menampilkan daftar seluruh santri aktif yang terdaftar di kelas Anda.
3. Ketikkan nilai pada masing-masing kotak input:
   - Nilai berupa angka antara **0** hingga **100** (dapat menggunakan pecahan desimal, misal `87.5`).
   - Nilai tidak boleh bernilai negatif atau melebihi 100.
4. Anda dapat menambahkan catatan khusus pada kolom catatan di sebelah nilai santri.
5. Klik tombol hijau **Simpan Nilai Santri**.

### Validasi Keamanan Sistem:
- Santri yang berstatus *Pindah* atau *Lulus* tidak akan dimunculkan dalam form input.
- Setiap kali nilai disimpan, sistem mencatat waktu penyimpanan dan akun guru yang menginput demi keamanan arsip.

---

## 4. Perhitungan Nilai Rata-Rata Otomatis

Guru tidak perlu lagi menghitung nilai akhir secara manual dengan kalkulator. Sistem secara cerdas menjalankan rumus penilaian berbobot (*Weighted Average Score*):

$$\text{Nilai Akhir} = \frac{\sum (\text{Nilai Komponen} \times \text{Bobot})}{\sum \text{Bobot Terpakai}}$$

Hasil perhitungan ini akan otomatis dimuat ke dalam kartu ringkasan capaian santri dan siap dicetak menjadi Laporan Capaian Santri (Rapor).
