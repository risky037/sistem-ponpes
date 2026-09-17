# Sistem Informasi Pondok Pesantren Fatimah Az-Zahra

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](docs/OPEN_SOURCE_NOTICE.md)
[![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.4-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x%20Ready-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![CI Pipeline](https://img.shields.io/badge/CI-GitHub%20Actions-2088FF?logo=github-actions&logoColor=white)](.github/workflows/ci.yml)

A comprehensive, role-based management information system tailored for **Pondok Pesantren Fatimah Az-Zahra**. Built on Laravel and MySQL, the platform provides institutional administration across student records, residential room allocations, academic classes, digital savings ledgers, student transfer transactions, automated WhatsApp notifications, and cloud synchronization.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Architecture Overview](#2-architecture-overview)
3. [Installation & Setup](#3-installation--setup)
4. [Development Workflow](#4-development-workflow)
5. [Security Guidelines](#5-security-guidelines)
6. [Open Source Attribution](#6-open-source-attribution)
7. [License](#7-license)

---

## 1. Project Overview

### 1.1. System Purpose
The application centralizes the day-to-day operational workflow of Pondok Pesantren Fatimah Az-Zahra into a unified, auditable digital portal, reducing paper-based administration, ensuring financial integrity in student savings, and keeping parents informed via automated transaction notices.

### 1.2. Target Users & Role-Based Access
- **Administrator:** Full administrative control over institutional settings, user management, role assignments, student lifecycle, and audit logs.
- **Pengurus (Board / Administrators):** Management of student admissions, classroom assignments, dormitory rooms (*kamar*), and reporting.
- **Keuangan (Finance Officer):** Student savings ledger management (*tabungan*), cash deposits (*setoran*), cash withdrawals (*penarikan*), and internal balance transfers.
- **Santri / Wali (Student & Guardian):** Profile review, academic status, and balance history.

### 1.3. Main Modules
- 👨‍🎓 **Manajemen Santri:** Student registry, biographical data, address hierarchy (provinsi, kabupaten, kecamatan, kelurahan), and Excel batch import/export.
- 🏢 **Kamar & Asrama:** Dormitory capacity tracking, room assignments, and resident occupancy limits.
- 📚 **Kelas & Akademik:** Class level management, student distribution, and academic tracking.
- 💳 **Tabungan & Keuangan:** Digital balance ledger, deposits, withdrawals, and peer-to-peer student balance transfers.
- 🪪 **Cetak Kartu Santri (KTS):** Dynamic student identity card generation with integrated barcode printing (`milon/barcode`).
- 📲 **WhatsApp Gateway:** Automated instant notification dispatch to guardians upon deposit or withdrawal.
- ☁️ **Google Sheets Synchronization:** Real-time two-way synchronization of active and alumni student lists via Google Sheets API.
- 📜 **Audit Trail & Riwayat:** Comprehensive activity logging for administrative accountability.

### 1.4. Technology Stack
- **Backend Framework:** Laravel 10.x (Prepared for Laravel 11.x migration)
- **Language:** PHP 8.2+ (PHP 8.4 runtime ready)
- **Database:** MySQL 8.0 / MariaDB 10.3+
- **Frontend / Asset Bundling:** Blade Templates, Bootstrap 5, Vite, jQuery, Yajra DataTables
- **Package Ecosystem:**
  - `spatie/laravel-permission`: Role and permission management
  - `yajra/laravel-datatables`: Server-side pagination and filtering
  - `maatwebsite/excel`: Spreadsheet import and export
  - `milon/barcode`: Dynamic 1D/2D barcode generation
  - `revolution/laravel-google-sheets`: Google Sheets API v4 integration
  - `intervention/image`: Profile image resizing and optimization

---

## 2. Architecture Overview

```
                         ┌─────────────────────────┐
                         │   HTTP / HTTPS Client   │
                         └────────────┬────────────┘
                                      │
                         ┌────────────▼────────────┐
                         │      Vite / Assets      │
                         │    (Bootstrap 5/CSS)    │
                         └────────────┬────────────┘
                                      │
                         ┌────────────▼────────────┐
                         │   Routing & Middleware  │
                         │   - Session Auth Guard  │
                         │   - Spatie RBAC Check   │
                         └────────────┬────────────┘
                                      │
            ┌─────────────────────────┼─────────────────────────┐
            │                         │                         │
 ┌──────────▼──────────┐   ┌──────────▼──────────┐   ┌──────────▼──────────┐
 │   Web Controllers   │   │  Api Controllers    │   │  View Composers     │
 │  (Santri, Keuangan, │   │  (Sync Engine)      │   │  (ViewServiceProvider│
 │   Settings, Kamar)  │   │                     │   │   Global UI State)  │
 └──────────┬──────────┘   └──────────┬──────────┘   └─────────────────────┘
            │                         │
            ├─────────────────────────┴─────────────────────────┐
            │                                                   │
 ┌──────────▼──────────┐                             ┌──────────▼──────────┐
 │   Eloquent Models   │                             │ Third-Party Gateway │
 │ - LogActivity Trait │                             │ - Labelin WhatsApp  │
 │ - Santri, Tabungan  │                             │ - Google Sheets API │
 └──────────┬──────────┘                             └─────────────────────┘
            │
 ┌──────────▼──────────┐
 │   MySQL Database    │
 └─────────────────────┘
```

- **Authentication & RBAC:** Session-based guard using custom `AuthController` and Spatie Permission gates (`Administrator`, `Keuangan`, `Pengurus`, `Santri`).
- **Data Integrity:** Database transactions (`DB::transaction`) safeguard balance modifications during deposits, withdrawals, and student-to-student transfers.
- **Model Lifecycle Observers:** Eloquent observers (`TransaksiTabunganObserver`, `SantriObserver`) trigger automated WhatsApp alerts and maintain dormitory headcounts.

---

## 3. Installation & Setup

### 3.1. System Requirements
| Component | Minimum Version | Recommended Version |
| :--- | :--- | :--- |
| **PHP** | `8.2.0` | `8.3` or `8.4` |
| **Composer** | `2.5.0+` | Latest stable |
| **Node.js** | `18.x` LTS | `20.x` LTS |
| **NPM** | `9.x+` | Latest stable |
| **Database** | MySQL `8.0` / MariaDB `10.3` | MySQL `8.0+` |

### 3.2. Step-by-Step Installation

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/risky037/sistem-ponpes.git
   cd sistem-ponpes
   ```

2. **Install PHP Dependencies:**
   ```bash
   composer install
   ```

3. **Install Frontend Dependencies:**
   ```bash
   npm install
   ```

4. **Configure Environment Variables:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Open `.env` and configure your database and third-party service credentials:
   ```env
   APP_NAME="Sistem Ponpes Fatimah Az-Zahra"
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=digitren
   DB_USERNAME=root
   DB_PASSWORD=your_password

   # WhatsApp Gateway Credentials
   WA_API_KEY=your_labelin_api_key
   WA_SENDER_NUMBER=your_sender_phone_number

   # Google Sheets Integration
   SPREADSHEET_ID=your_google_spreadsheet_id
   ```

5. **Run Database Migrations & Initial Seeders:**
   ```bash
   php artisan migrate --seed
   ```

6. **Create Public Storage Symbolic Link:**
   ```bash
   php artisan storage:link
   ```

7. **Build Frontend Assets:**
   ```bash
   npm run build
   ```

8. **Start Local Development Server:**
   ```bash
   php artisan serve
   ```
   Access the dashboard at `http://127.0.0.1:8000`. Default administrative credentials will be seeded via `DatabaseSeeder`.

---

## 4. Development Workflow

### 4.1. Branch Model
- `main`: Production-ready releases. Protected branch; merges only via Pull Request.
- `develop`: Primary integration branch for ongoing features.
- `feature/*`: Dedicated branches for distinct features (e.g. `feature/rapor-santri`).
- `bugfix/*`: Fixes targeting existing bugs (e.g. `bugfix/pre-laravel11-stabilization`).
- `upgrade/*`: Major framework and architectural version upgrades (e.g. `upgrade/laravel-11-preparation`).

### 4.2. Commit Convention
All commits must adhere to the **Conventional Commits** specification:
```text
<type>(<scope>): <short imperative description>

Examples:
- feat(santri): add photo upload validation
- fix(transaksi): resolve balance decrement calculation
- security(api): protect synchronization endpoints
- refactor(observers): decouple whatsapp dispatch into queued jobs
```

For complete workflow guidelines, please consult [CONTRIBUTING.md](CONTRIBUTING.md).

---

## 5. Security Guidelines

- **Never Commit Secrets:** Never commit `.env`, private keys, API tokens, or service account JSON files to git.
- **Configuration Caching:** Avoid invoking `env()` outside configuration files. Always access values via `config(...)` to maintain compatibility with `php artisan config:cache`.
- **Upload Validation:** Validate all file uploads with explicit MIME types, extensions, and maximum file sizes.
- **Reporting Security Issues:** If you discover a vulnerability or security flaw, please do **not** open a public issue. Email security reports directly to `admin@digitren.net` or submit via GitHub Private Vulnerability Reporting.

---

## 6. Open Source Attribution

This repository is derived from the open-source project **Digitren** created by **Ahmad Muzayyin** ([@AhmadMuzayyin](https://github.com/AhmadMuzayyin)).

For full historical lineage, original copyright notices, and license acknowledgements, please see [docs/OPEN_SOURCE_NOTICE.md](docs/OPEN_SOURCE_NOTICE.md).

---

## 7. License

Distributed under the **MIT License**. See [LICENSE](LICENSE) and [docs/OPEN_SOURCE_NOTICE.md](docs/OPEN_SOURCE_NOTICE.md) for details.
