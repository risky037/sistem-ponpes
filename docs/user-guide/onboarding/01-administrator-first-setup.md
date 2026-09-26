---
title: Panduan Pertama Administrator (First Setup)
category: onboarding
role:
  - Administrator
estimated_time: 15 menit
difficulty: beginner
version: 5.8.8.2
---

# Panduan Pertama Administrator: Setup Awal Sistem DIGITREN

Dokumen ini memandu **Administrator Pesantren** yang baru pertama kali masuk ke aplikasi DIGITREN setelah proses pemasangan (*fresh installation*). Ikuti panduan ini langkah demi langkah untuk menyiapkan sistem dari kondisi kosong hingga siap digunakan oleh para asatidz dan santri.

---

## 1. Masuk Pertama Kali (*First Login*)

1. Buka peramban web (disarankan menggunakan Google Chrome atau Microsoft Edge) dan akses alamat web DIGITREN.
2. Masukkan akun bawaan Administrator:
   - **Email / Username**: Akun admin yang diberikan teknisi instalasi.
   - **Password**: Kata sandi awal.
3. Setelah masuk, segera buka menu **Profil Saya** di pojok kanan atas untuk memperbarui kata sandi pribadi demi keamanan sistem.

---

## 2. Mengisi Profil Pesantren

::: warning
Data profil pondok wajib diisi pertama kali karena logo dan nama resmi lembaga akan otomatis tercetak pada kop rapor santri, kwitansi kasir, dan laporan administrasi.
:::

1. Buka menu **Setting** / **Pengaturan** pada bilah samping (*sidebar*).
2. Isi formulir data lembaga:
   - **Nama Pesantren**: Contoh: *Pondok Pesantren Fatimah Az-Zahra*.
   - **Nama Pimpinan**: Nama Pengasuh / Mudirul Ma'had.
   - **Alamat Lengkap**: Jalan, Desa/Kelurahan, Kecamatan, Kota/Kabupaten, Kode Pos.
   - **Nomor Kontak / WhatsApp**: Kontak resmi sekretariat pondok.
   - **Logo Lembaga**: Unggah gambar logo pesantren format PNG/JPG berlatar transparan.
3. Klik **Simpan Perubahan**.

---

## 3. Membuat Master Kamar Asrama & Angkatan

Pesantren adalah lembaga berasrama (*boarding school*). Siapkan tempat mukim dan kohort angkatan:
1. Buka menu **Kamar Santri** > Klik **Tambah Kamar**:
   - Masukkan nama kamar (misal: *Kamar 01*), nama gedung (misal: *Gedung Khadijah*), dan daya tampung maksimal santri (misal: *10 santri*).
   - Ulangi untuk semua kamar santri.
2. Buka menu **Data Santri** > **Angkatan** > Klik **Tambah Angkatan**:
   - Masukkan nama angkatan (misal: *Angkatan 2026/2027* atau *Angkatan Ke-10*) beserta tahun masuk Masehi dan Hijriyah.

---

## 4. Menetapkan Tahun Ajaran Aktif

Seluruh kegiatan KBM, absensi, dan nilai diikat oleh tahun ajaran aktif:
1. Buka menu **Akademik** > **Tahun Ajaran**.
2. Klik tombol **Tambah Tahun Ajaran**.
3. Masukkan nama tahun (misal: *2026/2027*), pilih semester (*Ganjil*), dan tentukan tanggal mulai serta selesai KBM.
4. Centang **Aktifkan Tahun Ajaran Ini** (`is_active = true`).
5. Klik **Simpan**.

---

## 5. Membuat Kelas Rombel & Mata Pelajaran

1. **Kelas Rombel**:
   - Buka menu **Data Akademik** > **Kelas** > Klik **Tambah Kelas**.
   - Tentukan tingkatan (misal: *Tingkat VII*) dan nama rombel (misal: *Kelas VII-A*).
2. **Katalog Mata Pelajaran (Kitab & Umum)**:
   - Buka menu **Akademik** > **Mata Pelajaran** > Klik **Tambah Mata Pelajaran**.
   - Masukkan kode mapel unik (misal: *FQH-01*), nama kitab (misal: *Fiqih Fathul Qorib*), kategori (*Kepesantrenan*), dan tingkatan kelas.

---

## 6. Mendaftarkan Akun Guru & Menerbitkan SK Mengajar

1. **Pendaftaran Guru**:
   - Buka menu **Users** > Klik **Tambah User**.
   - Masukkan nama asatidz, email/username, dan kata sandi sementara.
   - Pada kolom Peran (*Role*), pilih **Guru**.
2. **Penerbitan SK Mengajar (*Teaching Assignment*)**:
   - Buka menu **Akademik** > **Penugasan Mengajar** > Klik **Tambah Penugasan Mengajar**.
   - Pasangkan kombinasi: **Tahun Ajaran Aktif** + **Kelas (VII-A)** + **Mapel (Fiqih)** + **Guru Pengampu (Ustadz Ahmad)**.
   - Klik **Simpan**.

---

## 7. Menyusun Jadwal KBM & Menempatkan Santri

1. **Jadwal Pelajaran**:
   - Buka menu **Akademik** > **Jadwal Pelajaran**.
   - Pilih penugasan mengajar Ustadz Ahmad, tentukan hari (misal: *Senin*), jam pelajaran (*07.30 - 09.00*), dan ruang kelas (*Ruang 101*).
2. **Penempatan Santri (*Academic Enrollment*)**:
   - Buka menu **Akademik** > **Penempatan Santri**.
   - Pilih Kelas VII-A dan tempatkan santri-santri yang terdaftar ke rombel tersebut.

---

## 8. Memantau Sistem Lewat Dashboard Utama

Setelah seluruh langkah di atas tuntas:
- Buka menu **Dashboard** untuk memantau ringkasan statistik santri aktif, kamar terisi, dan jadwal hari ini.
- Sistem kini siap diserahkan kepada para asatidz untuk memulai presensi kelas harian.
