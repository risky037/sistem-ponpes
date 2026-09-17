# Synchronization API Architecture & Documentation

## Overview
The Synchronization API (`/api/v1/sync/*`) provides endpoints for external clients and administrative integration tools to synchronize institutional academic and student records with the **Sistem Informasi Pondok Pesantren Fatimah Az-Zahra**.

---

## 1. Authentication & Security

All synchronization endpoints are protected by a defense-in-depth security stack:

1. **Authentication:** Laravel Sanctum personal access tokens (`auth:sanctum`).  
   Clients must pass a Bearer token in the `Authorization` header:
   ```http
   Authorization: Bearer <personal-access-token>
   Accept: application/json
   ```
2. **Authorization:** Role-based access control (`role:Administrator|Pengurus`).  
   Only users possessing the `Administrator` or `Pengurus` role are authorized to access synchronization resources. Non-administrative users receive HTTP `403 Forbidden`.
3. **Rate Limiting (`sync-api`):**  
   A dedicated rate limiter restricts traffic to **60 requests per minute per authenticated user** (or IP address fallback). Requests exceeding this limit receive HTTP `429 Too Many Requests`.
4. **Audit Logging:**  
   Every state-modifying request (`POST /api/v1/sync/*`) logs an audit trail to both `activity_logs` table and system log files, recording the authenticated user ID, endpoint, action, affected resource, and timestamp.

---

## 2. Sanctum Token Lifecycle & Strategy

### 2.1 Token Creation
Tokens should be generated using administrative tools or artisan command:
```bash
php artisan tinker --execute="\$u = App\Models\User::where('email', 'admin@example.com')->first(); echo \$u->createToken('sync-prod-client', ['sync:read', 'sync:write'])->plainTextToken;"
```

### 2.2 Token Naming Convention
Follow standard environment-scoped naming conventions:
- `sync-prod-mobile`: Production mobile client.
- `sync-prod-desktop`: Production desktop sync client.
- `sync-staging-test`: Staging and testing integration.

### 2.3 Token Abilities (Prepared Architecture)
The architecture supports granular token abilities for future fine-grained enforcement:
- `sync:read`: Grants read-only access to `/sync/*` endpoints.
- `sync:write`: Grants creation and update access to `/sync/*` endpoints.

### 2.4 Token Revocation & Expiration
- **Revocation:** Tokens can be revoked immediately upon credential rotation or compromise:
  ```php
  $user->tokens()->where('name', 'sync-prod-mobile')->delete();
  ```
- **Expiration:** Configurable in `config/sanctum.php` with recommended 30-day periodic rotation.

---

## 3. Active API Endpoints

### 3.1 Get All Classes
- **URL:** `GET /api/v1/sync/kelas`
- **Method:** `GET`
- **Middleware:** `api`, `throttle:sync-api`, `auth:sanctum`, `role:Administrator|Pengurus`
- **Response `200 OK`:**
  ```json
  {
    "status": true,
    "message": "get all data kelas",
    "data": [
      {
        "id": 1,
        "kode": "K1A001",
        "tingkatan": "Ula",
        "kelas": "1A",
        "keterangan": null
      }
    ]
  }
  ```

### 3.2 Create Class
- **URL:** `POST /api/v1/sync/kelas`
- **Method:** `POST`
- **Middleware:** `api`, `throttle:sync-api`, `auth:sanctum`, `role:Administrator|Pengurus`
- **Request Body:**
  ```json
  {
    "tingkatan": "Ula",
    "kelas": "1A",
    "keterangan": "Kelas Reguler"
  }
  ```
- **Response `201 Created`:**
  ```json
  {
    "status": true,
    "message": "successfully created data",
    "data": { ... }
  }
  ```

### 3.3 Get All Santri
- **URL:** `GET /api/v1/sync/santri`
- **Method:** `GET`
- **Middleware:** `api`, `throttle:sync-api`, `auth:sanctum`, `role:Administrator|Pengurus`
- **Response `200 OK`:**
  ```json
  {
    "status": true,
    "message": "get all data santri",
    "data": [ ... ]
  }
  ```

### 3.4 Create Santri
- **URL:** `POST /api/v1/sync/santri`
- **Method:** `POST`
- **Middleware:** `api`, `throttle:sync-api`, `auth:sanctum`, `role:Administrator|Pengurus`
- **Request Body:** Full student profile payload (validates `kelas`, `kamar`, `nama_lengkap`, `nik`, `kk`, `whatsapp`, `tanggal_lahir`, `tempat_lahir`, `tahun_masuk`, `nama_ayah`, `nama_ibu`).
- **Response `201 Created`:**
  ```json
  {
    "status": true,
    "message": "successfully created data",
    "data": { ... }
  }
  ```

---

## 4. Removed Endpoints & Historical Analysis

The following routes were declared in `routes/api.php` during the initial commit (`cdcd3876`, Sep 6, 2023) but were never implemented in upstream controller logic:
- `GET /api/v1/sync/tabungan` (`get_tabungan`)
- `POST /api/v1/sync/tabungan` (`store_tabungan`)
- `GET /api/v1/sync/transaksi` (`get_transaksi`)
- `POST /api/v1/sync/transaksi` (`store_transaksi`)

### Reason for Removal
Calling any of these endpoints resulted in an unhandled `BadMethodCallException` (HTTP 500). In accordance with repository governance, dead routes without business logic were removed to prevent security vulnerabilities and unexpected service exceptions.

### Future Requirements
If digital ledger synchronization is required in future releases:
1. Functional requirements must define atomic transaction handling, ledger reconciliation, and double-entry safeguards.
2. A dedicated feature issue and pull request will introduce `SyncTabunganRequest` and `SyncTransaksiRequest` with strict financial validation.
