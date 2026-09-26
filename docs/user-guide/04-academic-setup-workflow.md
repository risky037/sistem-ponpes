# 04. Alur Penyiapan Akademik (Academic Setup Workflow)

Setelah master kelas dan kamar disiapkan, tahap krusial berikutnya adalah mengonfigurasi siklus akademik. Dokumen ini memandu **Administrator** dan **Pengurus Bagian Pendidikan (Kurikulum)** dalam menyusun seluruh rantai akademik semester baru.

---

## 1. Menentukan Tahun Ajaran Aktif (Academic Year)

Langkah awal dari seluruh kegiatan belajar-mengajar adalah membuka tahun ajaran.

### Mengapa Langkah Ini Diperlukan?
Di dalam sistem DIGITREN, seluruh data santri belajar, jadwal, presensi, dan nilai diikat oleh tahun ajaran. Tanpa adanya tahun ajaran aktif, guru tidak dapat mengabsen dan nilai tidak dapat diinput.

### Data yang Perlu Disiapkan:
1. Nama Tahun Ajaran (contoh: *2026/2027*).
2. Semester: **Ganjil** atau **Genap**.
3. Tanggal Mulai dan Tanggal Selesai kegiatan belajar semester tersebut.

### Langkah-Langkah:
1. Buka menu **Akademik** > **Tahun Ajaran**.
2. Klik tombol **Tambah Tahun Ajaran**.
3. Masukkan nama tahun (misal: *2026/2027*), pilih semester (*Ganjil*), dan tentukan tanggal mulai serta berakhir.
4. Centang opsi **Aktifkan Tahun Ajaran Ini**.
5. Klik **Simpan**.

### Apa yang Terjadi Setelah Selesai?
Sistem akan menandai tahun ajaran tersebut sebagai tahun operasional utama (`is_active = true`). Tahun ajaran sebelumnya akan diarsipkan secara otomatis sehingga nilai historis tetap aman.

---

## 2. Mendaftarkan Mata Pelajaran / Kitab Kajian (Mapel)

### Mengapa Langkah Ini Diperlukan?
Pesantren mengkombinasikan kurikulum kepesantrenan (kajian kitab kuning, tahfidz, nahwu-shorof) dan kurikulum formal nasional. Katalog mata pelajaran mendefinisikan mata pelajaran apa saja yang diajarkan di pondok.

### Data yang Perlu Disiapkan:
1. Kode Mata Pelajaran unik (contoh: *FQH-01*, *NHW-01*, *MTK-07*).
2. Nama Mata Pelajaran (contoh: *Fiqih Fathul Qorib*, *Nahwu Jurumiyah*, *Matematika*).
3. Kategori: **Kepesantrenan**, **Agama**, atau **Umum**.
4. Tingkatan Kelas yang mempelajari mapel tersebut.

### Langkah-Langkah:
1. Buka menu **Akademik** > **Mata Pelajaran**.
2. Klik **Tambah Mata Pelajaran**.
3. Masukkan Kode, Nama, Kategori, dan Tingkatan.
4. Klik **Simpan**. Ulangi untuk seluruh mata pelajaran.

### Apa yang Terjadi Setelah Selesai?
Mata pelajaran kini siap dipasangkan dengan guru pengampu pada penugasan mengajar.

---

## 3. Menetapkan Wali Kelas (Wali Kelas Assignment)

### Mengapa Langkah Ini Diperlukan?
Setiap rombel kelas santri membutuhkan seorang guru pembimbing (wali kelas) yang bertanggung jawab memantau perkembangan moral, akademik, dan absensi santri di kelas binaannya.

### Langkah-Langkah:
1. Buka menu **Akademik** > **Wali Kelas**.
2. Klik **Tetapkan Wali Kelas**.
3. Pilih Tahun Ajaran Aktif, pilih Rombel Kelas, lalu pilih nama Guru yang ditugaskan.
4. Klik **Simpan**.

### Apa yang Terjadi Setelah Selesai?
Guru yang bersangkutan secara resmi tercatat sebagai wali kelas dan dapat memantau ringkasan performa seluruh santri di kelas tersebut.

---

## 4. Menerbitkan SK Penugasan Mengajar Guru (Teaching Assignment)

Ini adalah langkah inti paling penting dalam domain akademik.

### Mengapa Langkah Ini Diperlukan?
Penugasan mengajar (*Teaching Assignment*) adalah jangkar yang mempertemukan 4 unsur:
$$\text{Guru Pengampu} + \text{Mata Pelajaran} + \text{Kelas Rombel} + \text{Tahun Ajaran Aktif}$$
Jika penugasan mengajar belum dibuat, guru tidak akan menemukan kelasnya saat login ke sistem.

### Data yang Perlu Disiapkan:
1. Nama Guru/Ustadz pengampu.
2. Mata pelajaran yang diampu.
3. Rombel kelas tempat guru tersebut mengajar.

### Langkah-Langkah:
1. Buka menu **Akademik** > **Penugasan Mengajar**.
2. Klik **Tambah Penugasan Mengajar**.
3. Pilih Tahun Ajaran Aktif (terpilih otomatis).
4. Pilih Kelas (misal: *Kelas VII-A*), pilih Mapel (misal: *Fiqih*), lalu pilih Guru Pengampu (misal: *Ustadz Ahmad*).
5. Pastikan status penugasan adalah **Aktif**.
6. Klik **Simpan**.

### Apa yang Terjadi Setelah Selesai?
Saat Ustadz Ahmad masuk ke **Portal Guru**, kelas VII-A untuk mapel Fiqih akan langsung muncul di dasbor pribadinya.

---

## 5. Menyusun Jadwal Pembelajaran Mingguan (Class Schedule)

### Mengapa Langkah Ini Diperlukan?
Membantu asatidz dan santri mengetahui hari, jam pelajaran, dan ruangan kelas tempat belajar berlangsung. Jadwal ini juga menjadi dasar sistem untuk membuka sesi presensi tatap muka harian.

### Data yang Perlu Disiapkan:
1. Penugasan Mengajar guru yang telah dibuat sebelumnya.
2. Hari KBM (contoh: *Senin*, *Selasa*, dst.).
3. Jam Mulai dan Jam Selesai (contoh: *07.30 - 09.00*).
4. Ruangan Belajar (misal: *Ruang Kelas 101*, *Musholla Lt. 2*).

### Langkah-Langkah:
1. Buka menu **Akademik** > **Jadwal Pelajaran**.
2. Klik **Tambah Jadwal Pelajaran**.
3. Pilih Penugasan Mengajar yang sesuai.
4. Tentukan Hari, Jam Mulai, Jam Selesai, dan Ruangan.
5. Klik **Simpan**.

### Apa yang Terjadi Setelah Selesai?
Jadwal pelajaran akan tampil pada Portal Santri dan Portal Guru sesuai dengan jadwal masing-masing.

---

## 6. Menempatkan Santri ke Kelas Rombel (Academic Enrollment)

### Mengapa Langkah Ini Diperlukan?
Santri yang terdaftar di pondok harus dimasukkan ke dalam daftar kelas untuk semester yang berjalan. Daftar santri inilah yang akan muncul pada lembar absensi guru dan buku nilai.

### Langkah-Langkah:
1. Buka menu **Akademik** > **Penempatan Santri** (atau **Academic Enrollment**).
2. Klik tombol **Tempatkan Santri**.
3. Pilih Tahun Ajaran Aktif dan Kelas tujuan (misal: *Kelas VII-A*).
4. Pilih nama-nama santri yang ditempatkan ke kelas tersebut (dapat dilakukan pemilihan santri sekaligus).
5. Pastikan status penempatan adalah **Aktif**.
6. Klik **Simpan Penempatan**.

### Apa yang Terjadi Setelah Selesai?
Santri resmi tercatat sebagai anggota kelas VII-A untuk tahun ajaran aktif. Saat guru kelas VII-A membuka form presensi atau form nilai, nama seluruh santri tersebut akan langsung tampil rapi secara otomatis.
