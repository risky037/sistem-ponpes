# Panduan Deployment Shared Hosting — DIGITREN (Sistem Ponpes Fatimah Az-Zahra)

Dokumen ini menyediakan panduan langkah demi langkah untuk menyebarkan (deploy) aplikasi **DIGITREN** pada lingkungan **Shared Hosting** (cPanel, DirectAdmin, Plesk, atau server web berbasis Apache/LiteSpeed).

---

## 1. Persyaratan Server (Server Requirements)

Pastikan shared hosting Anda memenuhi spesifikasi berikut:

- **PHP**: Versi **8.2** atau **8.3 / 8.4** (disarankan PHP 8.2+)
- **Database**: **MariaDB 10.4+** atau **MySQL 8.0+**
- **Web Server**: Apache 2.4+ atau LiteSpeed dengan modul `mod_rewrite` aktif
- **Ekstensi PHP Wajib**:
  - `BCMath`
  - `Ctype`
  - `Fileinfo`
  - `JSON`
  - `Mbstring`
  - `OpenSSL`
  - `PDO` & `pdo_mysql`
  - `Tokenizer`
  - `XML`
  - `GD` atau `Imagick` (diperlukan untuk pemrosesan foto santri & logo setting)
  - `Zip`

---

## 2. Struktur Direktori yang Direkomendasikan

Demi keamanan data dan kepatuhan best practice Laravel, **jangan tempatkan seluruh file aplikasi di dalam `public_html`**. Pisahkan file core Laravel dari web root publik.

### Struktur Pemisahan Direktori:
```
/home/username/
├── digitren_app/               <-- Seluruh core Laravel (app, bootstrap, config, database, resources, storage, vendor, .env)
└── public_html/                <-- Isi dari folder public/ Laravel (index.php, .htaccess, assets/, build/, favicon, dll.)
```

### Konfigurasi `public_html/index.php`:
Sesuaikan path autoload dan bootstrap di `public_html/index.php`:
```php
// Ganti path relatif standar ke lokasi digitren_app:
require __DIR__.'/../digitren_app/vendor/autoload.php';

$app = require_once __DIR__.'/../digitren_app/bootstrap/app.php';
```

---

## 3. Langkah Instalasi & Deployment

### Langkah 1: Persiapan File (Build di Lokal atau CI)
1. Jalankan instalasi dependensi dan kompilasi aset di lokal/CI:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm run build
   ```
2. Kompres file proyek menjadi file ZIP (kecualikan `.git`, `node_modules`, `tests`).

### Langkah 2: Unggah ke Hosting
1. Buka **File Manager** cPanel.
2. Unggah file ZIP ke direktori home (`/home/username/`).
3. Ekstrak ke folder `/home/username/digitren_app/`.
4. Pindahkan seluruh isi folder `digitren_app/public/` ke direktori `public_html/`.

### Langkah 3: Konfigurasi File Lingkungan (`.env`)
1. Salin `.env.example` menjadi `.env` di dalam `digitren_app/`.
2. Sesuaikan konfigurasi produksi:
   ```dotenv
   APP_NAME="DIGITREN"
   APP_ENV=production
   APP_KEY=base64:...             # Pastikan terisi kunci aplikasi
   APP_DEBUG=false
   APP_URL=https://domain-anda.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=username_digitren
   DB_USERNAME=username_dbuser
   DB_PASSWORD=PasswordKuat123!

   SESSION_DRIVER=file
   CACHE_STORE=file
   QUEUE_CONNECTION=sync
   ```

### Langkah 4: Izin Folder (Permissions)
Pastikan web server memiliki akses tulis ke direktori berikut:
```bash
chmod -R 775 /home/username/digitren_app/storage
chmod -R 775 /home/username/digitren_app/bootstrap/cache
```

---

## 4. Penanganan Storage & Aset (Storage Handling)

Pada shared hosting, pembuatan symbolic link (`php artisan storage:link`) seringkali tidak didukung atau dibatasi oleh izin hosting. DIGITREN dilengkapi dengan **mekanisme ketahanan aset (Asset Resilience)**:

### 1. Storage Fallback Route (Otomatis)
Aplikasi menyediakan controller fallback `StorageFallbackController` yang otomatis melayani file dari `storage/app/public/` melalui route `/storage/{path}` jika symlink tidak tersedia.
- Jika Apache mendeteksi symlink fisik: file disajikan langsung oleh web server.
- Jika symlink fisik tidak ada: request otomatis ditangani secara aman oleh route fallback dengan header caching HTTP (`max-age=86400`).

### 2. Resilient Logo & Favicon
Pemanggilan logo dan favicon menggunakan `Setting::getLogoUrl()` dan `Setting::getFaviconUrl()`.
- Jika logo/favicon belum diunggah atau file fisik terhapus dari server, sistem secara otomatis mengembalikan aset fallback default (`assets/images/logo-icon.png` dan `assets/images/favicon-32x32.png`).
- Tidak akan terjadi tampilan gambar rusak (*broken image icon*).

### 3. Pembuatan Symlink Manual (Jika Terminal/Cron Tersedia)
Jika cPanel menyediakan akses SSH atau Terminal:
```bash
php artisan storage:link
```
Atau buat symlink melalui Cron Job (satu kali eksekusi):
```bash
ln -s /home/username/digitren_app/storage/app/public /home/username/public_html/storage
```

---

## 5. Migrasi & Optimasi Cache

Jalankan perintah berikut melalui Terminal cPanel atau SSH:

### 1. Migrasi Database
```bash
php artisan migrate --force
```
*(Opsional untuk data awal demo)*:
```bash
php artisan db:seed --force
# Atau gunakan perintah mandiri demo:
php artisan app:install-demo
```

### 2. Kompilasi & Optimasi Cache Produksi
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Untuk membersihkan seluruh cache saat pembaruan kode:
```bash
php artisan optimize:clear
php artisan optimize
```

---

## 6. Pemecahan Masalah (Troubleshooting)

### A. Layar Putih / Error 500
1. Periksa log kesalahan di `digitren_app/storage/logs/laravel.log`.
2. Pastikan file `.env` ada dan `APP_KEY` terisi.
3. Periksa versi PHP di menu **cPanel > Select PHP Version** (wajib PHP 8.2+).
4. Pastikan folder `storage` dan `bootstrap/cache` memiliki izin `775` (atau `755` tergantung konfigurasi suPHP hosting).

### B. Gambar Santri / Logo Tidak Muncul (404)
1. Pastikan modul `mod_rewrite` aktif pada Apache/LiteSpeed.
2. Periksa keberadaan file `.htaccess` di dalam `public_html/`:
   ```apache
   <IfModule mod_rewrite.c>
       <IfModule mod_negotiation.c>
           Options -MultiViews -Indexes
       </IfModule>

       RewriteEngine On

       # Handle Authorization Header
       RewriteCond %{HTTP:Authorization} .
       RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

       # Redirect Trailing Slashes...
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteCond %{REQUEST_URI} (.+)/$
       RewriteRule ^ %1 [L,R=301]

       # Send Requests To Front Controller...
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteRule ^ index.php [L]
   </IfModule>
   ```
3. Jika symlink tidak dapat dibuat, mekanisme `StorageFallbackController` akan menangani request `/storage/...` secara otomatis melalui front controller `index.php`.

### C. Database Connection Refused / Access Denied
1. Pastikan user database telah diberikan **ALL PRIVILEGES** pada database di menu **cPanel > MySQL Databases**.
2. Pastikan `DB_HOST=127.0.0.1` atau `localhost` sesuai petunjuk penyedia hosting Anda.

---

## 7. Pembaruan Aplikasi (Maintenance & Update Flow)

Saat memperbarui kode aplikasi di shared hosting:
1. Aktifkan mode pemeliharaan:
   ```bash
   php artisan down --secret="kunci-rahasia-admin"
   ```
2. Unggah dan timpa file kode baru.
3. Jalankan migrasi dan pembaruan cache:
   ```bash
   php artisan migrate --force
   php artisan optimize:clear
   php artisan optimize
   ```
4. Nonaktifkan mode pemeliharaan:
   ```bash
   php artisan up
   ```
