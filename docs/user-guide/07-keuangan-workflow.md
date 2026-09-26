# 07. Alur Pengelolaan Keuangan & Tabungan Santri (Keuangan Workflow)

Buku panduan ini diperuntukkan bagi **Bendahara Pesantren** dan **Staf Keuangan/Kasir** Pondok Pesantren Fatimah Az-Zahra. Modul Keuangan DIGITREN mengamankan perputaran uang santri dengan sistem pencatatan ganda (*ledger recording*) yang transparan dan dapat dipertanggungjawabkan.

---

## 1. Konsep Dua Kantong Keuangan Santri

Untuk menjaga pola hidup hemat dan mencegah santri memegang uang tunai berlebihan di kamar asrama, sistem membagi dana santri menjadi dua kantong:

```
                          ┌──────────────────────────┐
                          │   KIRIMAN ORANG TUA      │
                          └─────────────┬────────────┘
                                        │ (Setor Tabungan)
                                        ▼
                          ┌──────────────────────────┐
                          │     TABUNGAN UTAMA       │
                          │   (Simpanan Pokok Santri)│
                          └─────────────┬────────────┘
                                        │ (Pencairan / Transfer)
                                        ▼
                          ┌──────────────────────────┐
                          │     UANG SAKU HARIAN     │
                          │ (Belanja Kantin/Koperasi)│
                          └──────────────────────────┘
```

1. **Tabungan Santri (Simpanan)**: Rekening induk tempat menampung kiriman dari orang tua/wali santri atau sisa bekal santri.
2. **Uang Saku Santri (Operasional Harian)**: Rekening belanja harian santri yang dapat ditarik atau dibelanjakan di lingkungan pondok sesuai batas maksimal harian yang diizinkan pengasuhan.

---

## 2. Prosedur Penerimaan Setoran Tabungan (Deposit)

Ketika orang tua santri datang ke kantor keuangan atau mentransfer bekal santri:

### Langkah-Langkah:
1. Buka menu **Tabungan Santri** pada bilah samping.
2. Cari nama santri atau masukkan Nomor Induk Santri (NIS) pada kolom pencarian.
3. Klik tombol **Setor Dana** pada baris santri yang bersangkutan.
4. Masukkan nominal setoran (misal: `Rp 500.000`).
5. Pilih metode penerimaan: **Tunai** atau **Transfer Bank**.
6. Tuliskan keterangan (contoh: *Kiriman bekal bulan Oktober dari Bapak Ridwan*).
7. Klik tombol **Simpan Transaksi**.
8. Klik tombol **Cetak Bukti Setoran** untuk mencetak struk/tanda terima bagi penyetor.

### Apa yang Terjadi Setelah Selesai?
Saldo Tabungan Santri langsung bertambah saat itu juga, dan riwayat setoran tercatat permanen pada buku kas keuangan.

---

## 3. Prosedur Penarikan Uang Saku Santri (Withdrawal)

Ketika santri mengantre di loket keuangan untuk mengambil uang saku mingguan/harian:

### Langkah-Langkah:
1. Buka menu **Uang Saku Santri** > **Penarikan Uang Saku**.
2. Cari nama santri berdasarkan nama atau NIS.
3. Sistem akan menampilkan saldo uang saku yang tersedia.
4. Masukkan jumlah uang tunai yang diserahkan kepada santri (misal: `Rp 25.000`).
5. Sistem akan memvalidasi apakah jumlah penarikan melebihi saldo yang ada atau melebihi batas penarikan harian (*daily limit*).
6. Jika valid, klik **Konfirmasi Penarikan**.
7. Serahkan uang fisik kepada santri.

---

## 4. Prosedur Pemindahan Dana (Transfer Tabungan ke Uang Saku)

Jika saldo uang saku santri habis, namun santri masih memiliki simpanan di rekening Tabungan Utama, staf keuangan dapat memindahkan dana antar-kantong santri:

### Langkah-Langkah:
1. Buka menu **Transfer Dana Santri**.
2. Pilih nama santri.
3. Masukkan jumlah dana yang ingin dipindahkan dari **Tabungan** ke **Uang Saku** (misal: `Rp 100.000`).
4. Berikan catatan (contoh: *Pencairan bekal mingguan atas izin santri*).
5. Klik **Proses Pemindahan**.

### Apa yang Terjadi Setelah Selesai?
Saldo Tabungan Utama berkurang otomatis sebesar Rp 100.000, dan saldo Uang Saku Santri bertambah sebesar Rp 100.000 tanpa memengaruhi total kas fisik pondok.

---

## 5. Rekonsiliasi Kas Harian & Laporan Keuangan

Pada setiap akhir jam operasional loket keuangan:
1. Buka menu **Laporan Transaksi Keuangan**.
2. Pilih filter tanggal **Hari Ini**.
3. Sistem akan merangkum:
   - Total Setoran Tunai Masuk.
   - Total Penarikan Tunai Keluar.
   - Sisa Kas Fisik yang seharusnya ada di laci kasir (*Brankas*).
4. Cocokkan jumlah fisik uang di brankas dengan angka rekapitulasi sistem.
5. Klik **Cetak Laporan Harian Kasir** untuk ditandatangani oleh kasir dan diserahkan kepada Bendahara Umum.
