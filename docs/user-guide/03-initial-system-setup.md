# 03. Pengaturan Awal Sistem (Initial System Setup)

Panduan ini ditujukan khusus bagi **Administrator Pesantren** yang baru pertama kali mengoperasikan aplikasi DIGITREN. Pada kondisi awal saat sistem baru dipasang (*fresh installation*), basis data masih dalam keadaan kosong. 

Ikuti urutan langkah di bawah ini secara runut untuk memastikan sistem siap dipakai oleh dewan pengurus, asatidz, dan santri.

---

## 1. Pengaturan Identitas Lembaga & Pesantren

Pada kondisi awal aplikasi masih kosong, Administrator harus melakukan pengisian data profil lembaga pesantren terlebih dahulu.

### Mengapa Langkah Ini Diperlukan?
Data identitas ini menjadi kepala surat (kop resmi), judul aplikasi, penanda tanda tangan pimpinan pada rapor dan kwitansi, serta identitas legalitas pondok yang muncul di seluruh sistem dan laporan cetak.

### Data yang Perlu Disiapkan:
1. Nama Resmi Pesantren (misal: *Pondok Pesantren Fatimah Az-Zahra*).
2. Nama Pengasuh / Pimpinan Pondok.
3. Alamat lengkap, Kelurahan, Kecamatan, Kota/Kabupaten, dan Kode Pos.
4. Nomor telepon / WhatsApp resmi sekretariat.
5. Alamat surat elektronik (email) dan situs web (bila ada).
6. Berkas logo pesantren format PNG/JPG dengan latar belakang transparan.

### Langkah-Langkah:
1. Masuk (*login*) ke sistem menggunakan akun **Administrator**.
2. Di menu bilah samping (*sidebar*), klik menu **Pengaturan** atau **Setting**.
3. Isi kolom nama pesantren, alamat lengkap, dan nomor kontak yang aktif.
4. Unggah berkas logo pesantren pada kolom logo yang disediakan.
5. Klik tombol **Simpan Perubahan**.

### Apa yang Terjadi Setelah Selesai?
Logo dan nama pesantren langsung muncul di bagian atas aplikasi (header) dan secara otomatis akan terpasang pada kop surat bukti pembayaran maupun cetakan rapor santri.

---

## 2. Pembuatan Master Kamar & Asrama

Sebelum santri baru dimasukkan ke dalam sistem, kamar-kamar pemondokan harus sudah terdaftar.

### Mengapa Langkah Ini Diperlukan?
Pesantren adalah lembaga berasrama (*boarding*). Setiap santri yang bermukim wajib memiliki tempat tinggal yang jelas. Sistem menggunakan data kamar untuk memantau kapasitas asrama dan menghindari kelebihan muatan (*overcapacity*).

### Data yang Perlu Disiapkan:
1. Nama Gedung / Blok Asrama (contoh: *Gedung Khodijah*, *Gedung Aisyah*).
2. Nomor / Nama Kamar (contoh: *Kamar 01*, *Kamar 02*).
3. Batas daya tampung maksimal santri per kamar (misal: 10 orang).

### Langkah-Langkah:
1. Buka menu **Kamar Santri** pada bilah samping.
2. Klik tombol **Tambah Kamar Baru**.
3. Masukkan nama kamar, blok gedung, serta kapasitas maksimalnya.
4. Klik **Simpan**. Ulangi untuk seluruh kamar yang ada di pondok.

### Apa yang Terjadi Setelah Selesai?
Daftar kamar siap dipilih saat Pengurus melakukan pendaftaran atau pemetaan kamar santri.

---

## 3. Pembuatan Master Kelas Rombel

Selain kamar asrama, santri juga belajar dalam rombongan belajar (rombel) formal maupun kepesantrenan.

### Mengapa Langkah Ini Diperlukan?
Kelas rombel merupakan wadah pengelompokan santri dalam proses belajar-mengajar. Tanpa adanya data kelas, guru tidak dapat ditugaskan mengajar dan jadwal pelajaran tidak dapat dibuat.

### Data yang Perlu Disiapkan:
1. Tingkatan pendidikan (contoh: *Tingkat VII*, *Tingkat VIII*, *Tingkat IX*, atau *Tingkat Ula*, *Wustha*, *Ulya*).
2. Nama/Sub-kelas rombel (contoh: *Kelas VII-A*, *Kelas VII-B*).

### Langkah-Langkah:
1. Buka menu **Data Akademik** lalu pilih **Kelas**.
2. Klik tombol **Tambah Kelas**.
3. Pilih tingkatan dan masukkan nama kelas.
4. Klik **Simpan**.

### Apa yang Terjadi Setelah Selesai?
Rombongan belajar telah terbentuk di sistem dan siap diisi oleh santri pada tahun ajaran yang akan dibuka.

---

## 4. Pembuatan Master Angkatan Santri

### Mengapa Langkah Ini Diperlukan?
Angkatan santri (*Student Batch*) mempermudah pelacakan tahun masuk santri saat pendaftaran hingga kelulusan kelak (misal: *Angkatan 2026/2027* atau *Angkatan Ke-10*).

### Data yang Perlu Disiapkan:
1. Nama angkatan (contoh: *Angkatan 2026*).
2. Tahun masuk Masehi dan Hijriyah.

### Langkah-Langkah:
1. Buka menu **Data Santri** lalu pilih sub-menu **Angkatan**.
2. Klik **Tambah Angkatan**, masukkan nama dan tahun masuk.
3. Klik **Simpan**.

---

## 5. Ringkasan Kesiapan Tahap Awal

Setelah menyelesaikan 4 langkah di atas:
- [x] Identitas lembaga terpasang rapi.
- [x] Kamar asrama siap menampung santri.
- [x] Kelas rombel siap menampung kegiatan belajar.
- [x] Angkatan telah didefinisikan.

Sistem kini siap melangkah ke tahap berikutnya, yaitu **Setup Akademik dan Tahun Ajaran**, sebagaimana dijelaskan pada Buku Panduan 04.
