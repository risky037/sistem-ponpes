# 02. Pembagian Peran dan Hak Akses Pengguna

Di dalam aplikasi DIGITREN, setiap pengguna memiliki peran (*Role*) tertentu yang menentukan menu apa saja yang dapat dilihat, tombol apa yang dapat diklik, serta data apa yang boleh diubah. Pengaturan ini bertujuan untuk menjaga ketertiban, keamanan berkas, dan membagi tugas sesuai tanggung jawab masing-masing.

---

## 1. Daftar Peran (*Roles*) Resmi

Sistem membagi pengguna ke dalam 5 (lima) peran utama:

| Peran (*Role*) | Pihak yang Menjabat | Ruang Lingkup Utama |
| :--- | :--- | :--- |
| **Administrator** | Kepala TI / Pengelola Utama Sistem | Pengaturan sistem, manajemen akun pengguna, reset sandi, penentuan hak akses, dan pencadangan data. |
| **Pengurus** | Pimpinan Pesantren, Sekretariat, Tata Usaha (TU), Pengasuhan Santri | Pendaftaran santri, pembagian asrama/kamar, penugasan mengajar guru, penyusunan jadwal, dan pengawasan dashboard. |
| **Guru / Asatidz** | Dewan Guru, Asatidz Pengampu Kitab & Pelajaran Formal | Pengisian jurnal mengajar harian, presensi kehadiran santri di kelas, pembagian materi ajar, dan input nilai santri. |
| **Keuangan** | Bendahara Pondok, Kasir Pembayaran | Pengelolaan tabungan santri, pencatatan uang saku harian, mutasi setoran dan penarikan, serta rekonsiliasi kas. |
| **Santri / Wali** | Santri Aktif & Orang Tua/Wali Santri | Melihat data pribadi santri, mengecek kamar & kelas, melihat rekap presensi, memantau nilai ujian, dan cek saldo tabungan. |

---

## 2. Matriks Hak Akses Antar-Modul

Berikut adalah tabel rincian kewenangan setiap peran di dalam aplikasi:

| Modul Aplikasi | Administrator | Pengurus | Guru | Keuangan | Santri |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Pengaturan Lembaga & Profil** | Penuh (CRUD) | Lihat | Tidak | Tidak | Tidak |
| **Manajemen Akun & Role** | Penuh (CRUD) | Tidak | Tidak | Tidak | Tidak |
| **Data Master Santri** | Penuh (CRUD) | Penuh (CRUD) | Lihat | Lihat | Data Sendiri |
| **Penempatan Kamar & Asrama** | Penuh (CRUD) | Penuh (CRUD) | Lihat | Tidak | Data Sendiri |
| **Tahun Ajaran & Kalender** | Penuh (CRUD) | Penuh (CRUD) | Lihat | Lihat | Lihat |
| **SK Mengajar & Jadwal KBM** | Penuh (CRUD) | Penuh (CRUD) | Jadwal Sendiri | Lihat | Jadwal Kelas |
| **Sesi Tatap Muka & Presensi** | Penuh (CRUD) | Penuh (CRUD) | Kelas Ampuan | Tidak | Riwayat Sendiri|
| **Definisi Penilaian (Master)** | Penuh (CRUD) | Penuh (CRUD) | Lihat | Tidak | Tidak |
| **Input Nilai Santri** | Penuh (CRUD) | Lihat | Kelas Ampuan | Tidak | Nilai Sendiri |
| **Dashboard Intelijen Akademik**| Penuh | Penuh | Ringkasan Diri| Tidak | Tidak |
| **Materi Belajar (LMS)** | Penuh (CRUD) | Penuh (CRUD) | Kelas Ampuan | Tidak | Unduh & Baca |
| **Tabungan & Uang Saku** | Penuh (CRUD) | Lihat | Tidak | Penuh (CRUD) | Saldo Sendiri |

*Keterangan: CRUD = Create (Buat), Read (Lihat), Update (Ubah), Delete (Hapus).*

---

## 3. Prinsip Keamanan & Pembatasan Akses

1. **Prinsip Kelas Ampuan (Bagi Guru)**:
   - Seorang guru hanya dapat menginput presensi dan memberikan nilai pada mata pelajaran dan kelas yang secara resmi ditugaskan kepadanya melalui SK Mengajar.
   - Guru tidak diperkenankan mengubah nilai santri pada mata pelajaran yang diampu oleh asatidz lain.
2. **Prinsip Data Mandiri (Bagi Santri & Wali)**:
   - Santri hanya dapat melihat profil, riwayat kehadiran, nilai rapor, dan catatan tabungan miliknya sendiri.
   - Santri tidak memiliki akses untuk melihat maupun mengedit data santri lainnya demi menjaga privasi dan ketenangan belajar.
3. **Pemisahan Jalur Keuangan**:
   - Asatidz dan guru umum tidak memiliki akses ke pencatatan transaksi kas dan uang tabungan santri. Seluruh arus dana berada sepenuhnya di bawah kendali staf Keuangan dan Bendahara.
4. **Perlindungan Audit Trail**:
   - Seluruh tindakan penting (seperti perubahan nilai santri, penginputan transaksi kas, dan pengubahan akun) dicatat oleh sistem beserta nama pelaku dan waktu kejadian (*timestamp*).

---

## 4. Cara Menentukan Peran Pengguna Baru

Bagi Administrator yang bertugas mendaftarkan pengguna baru:
1. Pastikan setiap guru memiliki akun ber-role `Guru`.
2. Setiap santri yang diterima secara otomatis dikaitkan dengan akun ber-role `Santri`.
3. Petugas kasir hanya diberikan peran `Keuangan`.
4. Berikan peran `Pengurus` hanya kepada pimpinan, kepala madrasah, kepala asrama, atau staf tata usaha resmi.
5. Peran `Administrator` dibatasi seminimal mungkin (maksimal 2 orang penanggung jawab teknis pesantren) untuk menghindari risiko kesalahan konfigurasi sistem.
