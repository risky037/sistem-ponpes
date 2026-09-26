---
title: Desain Arsitektur Layanan LMS (LMS Service Architecture)
category: architecture
role:
  - Administrator
  - Pengurus
  - Guru
estimated_time: 20 menit
difficulty: intermediate
version: 5.8.9
---

# Desain Arsitektur Layanan LMS (LMS Service Architecture)

Dokumen ini mendefinisikan desain lapisan layanan (*Service Layer Architecture*) untuk modul *Learning Management System* (LMS) DIGITREN. Arsitektur ini mematuhi prinsip **Clean Architecture**, **SOLID Principles**, enkapsulasi transaksi database, dan pemisahan logika bisnis dari HTTP Controller.

---

## 1. Tanggung Jawab Lapisan Layanan (*Service Layer Pattern*)

Dalam arsitektur DIGITREN, controller hanya bertindak sebagai orkestrator HTTP (menerima request, validasi input FormRequest, memanggil service, dan mengembalikan respons/view). Seluruh logika bisnis ditangani oleh **`LearningMaterialService`**.

```
┌─────────────────────────────────┐
│     HTTP Request (Guru/Santri)  │
└────────────────┬────────────────┘
                 │
                 ▼
┌─────────────────────────────────┐
│  FormRequest & Otorisasi Policy │
└────────────────┬────────────────┘
                 │
                 ▼
┌─────────────────────────────────┐
│     LmsController (Web/API)     │
└────────────────┬────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────┐
│             LearningMaterialService                    │
│  • Validasi kepemilikan SK Mengajar (TeachingAssign)   │
│  • Orkestrasi Transaksi Database (DB::transaction)     │
│  • Pemrosesan & Penyimpanan Berkas Fisik (Storage)    │
│  • Sinkronisasi Target Rombel Kelas                    │
│  • Manajemen Cache & Penghitung Unduhan                │
└────────────────┬───────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────┐
│  Eloquent Models / Database DB  │
└─────────────────────────────────┘
```

---

## 2. Spesifikasi Layanan: `LearningMaterialService`

Kelas layanan akan ditempatkan pada `app/Services/Lms/LearningMaterialService.php`.

### Tanggung Jawab Utama:
1. **`createMaterial(User $teacher, array $data, array $targetClassIds, array $files = []): LearningMaterial`**
   - Validasi bahwa guru memiliki penugasan ajar aktif untuk mapel yang dipilih.
   - Buat slug unik materi secara otomatis.
   - Simpan rekaman materi ke tabel `learning_materials` di dalam `DB::transaction`.
   - Hubungkan rombel kelas yang dicentang ke tabel `learning_material_targets`.
   - Unggah dan catat berkas fisik lampiran ke tabel `learning_material_files`.

2. **`updateMaterial(LearningMaterial $material, array $data, ?array $targetClassIds = null): LearningMaterial`**
   - Perbarui atribut judul, uraian, konten teks kaya, tautan video, atau tautan eksternal.
   - Jika judul berubah, perbarui slug dengan tetap menjaga keunikan.
   - Sinkronisasi ulang kelas target jika ada perubahan rombel penerima.

3. **`publishMaterial(LearningMaterial $material): LearningMaterial`**
   - Mengubah status dari `draft` menjadi `published`.
   - Mengisi cap waktu `published_at` dengan `now()` jika sebelumnya masih kosong.
   - Menghapus (*bust*) cache katalog materi santri terkait.

4. **`archiveMaterial(LearningMaterial $material): LearningMaterial`**
   - Mengubah status materi menjadi `archived`. Santri tidak dapat lagi melihat materi ini di katalog utama, namun riwayat pengajaran tetap tersimpan rapi.

5. **`manageAttachments(LearningMaterial $material, array $uploadedFiles): Collection`**
   - Validasi ekstensi berkas aman (PDF, DOCX, PPTX, JPG, PNG).
   - Validasi batas ukuran berkas (maksimal 25 MB per berkas).
   - Simpan berkas ke direktori terisolasi `storage/app/materials/{year}/{slug}/`.
   - Simpan metadata (nama asli, path, tipe mime, ukuran bita) ke `learning_material_files`.

6. **`deleteAttachment(LearningMaterialFile $file): bool`**
   - Hapus berkas fisik dari penyimpanan `Storage::delete()`.
   - Hapus rekaman dari basis data.

7. **`validateVisibility(User $user, LearningMaterial $material): bool`**
   - Menguji apakah santri atau guru berhak melihat materi tertentu berdasarkan relasi akademik aktif.

---

## 3. Cetak Biru Kode Layanan (*Service Skeleton Implementation*)

```php
<?php

namespace App\Services\Lms;

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialFile;
use App\Models\TeachingAssignment;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LearningMaterialService
{
    /**
     * Membuat materi ajar baru lengkap dengan target kelas dan lampiran.
     *
     * @throws DomainException
     */
    public function createMaterial(User $teacher, array $data, array $targetClassIds, array $files = []): LearningMaterial
    {
        $this->validateTeachingAssignment($teacher, $data['mapel_id'], $data['academic_year_id']);

        return DB::transaction(function () use ($teacher, $data, $targetClassIds, $files) {
            $slug = $this->generateUniqueSlug($data['title']);

            $material = LearningMaterial::create([
                'teacher_id' => $teacher->id,
                'mapel_id' => $data['mapel_id'],
                'kelas_id' => $targetClassIds[0] ?? null,
                'academic_year_id' => $data['academic_year_id'],
                'title' => trim($data['title']),
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'content_type' => $data['content_type'] ?? 'text',
                'content' => $data['content'] ?? null,
                'video_url' => $data['video_url'] ?? null,
                'external_url' => $data['external_url'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'published_at' => ($data['status'] ?? '') === 'published' ? now() : null,
                'created_by' => $teacher->id,
            ]);

            // Sinkronisasi target kelas rombel
            if (!empty($targetClassIds)) {
                $material->targets()->sync($targetClassIds);
            }

            // Simpan lampiran berkas jika ada
            if (!empty($files)) {
                $this->storeAttachments($material, $files);
            }

            return $material->load(['mapel', 'teacher', 'targets', 'files']);
        });
    }

    /**
     * Memperbarui materi ajar yang sudah ada.
     */
    public function updateMaterial(LearningMaterial $material, array $data, ?array $targetClassIds = null): LearningMaterial
    {
        return DB::transaction(function () use ($material, $data, $targetClassIds) {
            $updateData = [];

            if (isset($data['title']) && $data['title'] !== $material->title) {
                $updateData['title'] = trim($data['title']);
                $updateData['slug'] = $this->generateUniqueSlug($data['title'], $material->id);
            }

            foreach (['description', 'content_type', 'content', 'video_url', 'external_url', 'status'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (($data['status'] ?? '') === 'published' && !$material->published_at) {
                $updateData['published_at'] = now();
            }

            $material->update($updateData);

            if ($targetClassIds !== null) {
                $material->targets()->sync($targetClassIds);
            }

            return $material->fresh(['mapel', 'targets', 'files']);
        });
    }

    /**
     * Mengunggah dan mencatat berkas lampiran.
     *
     * @param array<UploadedFile> $files
     * @return Collection<int, LearningMaterialFile>
     */
    public function storeAttachments(LearningMaterial $material, array $files): Collection
    {
        $saved = collect();
        $baseDir = "materials/{$material->academic_year_id}/{$material->id}";

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();

            $storedPath = $file->store($baseDir, 'local');

            $attachment = LearningMaterialFile::create([
                'learning_material_id' => $material->id,
                'file_name' => $originalName,
                'file_path' => $storedPath,
                'file_type' => $mimeType,
                'file_extension' => $extension,
                'file_size' => $fileSize,
                'download_count' => 0,
            ]);

            $saved->push($attachment);
        }

        return $saved;
    }

    /**
     * Mengambil daftar materi yang tersedia untuk seorang santri aktif.
     */
    public function getMaterialsForSantri(
        User $user,
        AcademicYear $academicYear,
        array $filters = []
    ): LengthAwarePaginator {
        $santriId = $user->santri?->id ?? $user->santri_id;

        $enrollment = AcademicEnrollment::where('santri_id', $santriId)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', AcademicEnrollment::STATUS_AKTIF)
            ->first();

        if (!$enrollment) {
            return LearningMaterial::whereRaw('1 = 0')->paginate(12);
        }

        $query = LearningMaterial::query()
            ->with(['mapel', 'teacher', 'files'])
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'published')
            ->whereHas('targets', function ($q) use ($enrollment) {
                $q->where('kelas_id', $enrollment->kelas_id);
            });

        // Filter berdasarkan mapel
        if (!empty($filters['mapel_id'])) {
            $query->where('mapel_id', $filters['mapel_id']);
        }

        // Pencarian kata kunci
        if (!empty($filters['search'])) {
            $keyword = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', $keyword)
                  ->orWhere('description', 'like', $keyword)
                  ->orWhere('content', 'like', $keyword);
            });
        }

        return $query->latest('published_at')->paginate(12);
    }

    /**
     * Validasi apakah guru memiliki SK Mengajar aktif pada mapel yang ditentukan.
     */
    protected function validateTeachingAssignment(User $teacher, int $mapelId, int $academicYearId): void
    {
        $hasAssignment = TeachingAssignment::where('user_id', $teacher->id)
            ->where('mapel_id', $mapelId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', TeachingAssignment::STATUS_AKTIF)
            ->exists();

        if (!$hasAssignment && !$teacher->hasRole('Administrator')) {
            throw new DomainException('Guru tidak memiliki SK Mengajar aktif untuk mata pelajaran ini pada semester berjalan.');
        }
    }

    /**
     * Membangun slug unik yang aman dari benturan.
     */
    protected function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (
            LearningMaterial::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-" . Str::lower(Str::random(4));
            $counter++;
            if ($counter > 10) {
                $slug = "{$baseSlug}-" . time();
                break;
            }
        }

        return $slug;
    }
}
```

---

## 4. Evaluasi Pola Repository (*When is LearningMaterialRepository Needed?*)

Di komunitas Laravel, penggunaan Repository Pattern sering diperdebatkan. Berikut adalah pedoman arsitektur kapan Repository diperlukan di DIGITREN:

```
┌────────────────────────────────────────────────────────┐
│        KEPUTUSAN PENGGUNAAN REPOSITORY PATTERN         │
├────────────────────────────┬───────────────────────────┤
│ Kapan Cukup Menggunakan    │ Kapan Repository Pattern  │
│ Service + Eloquent         │ Wajib Diperkenalkan       │
├────────────────────────────┼───────────────────────────┤
│ • Operasi CRUD standar     │ • Kueri analitik lintas   │
│   menggunakan relasi ORM   │   basis data eksternal    │
│ • Database tunggal (MySQL) │ • Unit test murni yang    │
│ • Pemfilteran query wajar  │   menolak koneksi DB SQLite│
│ • Skala aplikasi pesantren │ • Kebutuhan berganti driver│
│   menengah                 │   penyimpanan (misal NoSQL)│
└────────────────────────────┴───────────────────────────┘
```

### Keputusan untuk Modul LMS DIGITREN:
1. **Fase 5.8.10 - 5.8.13**:
   - Gunakan pendekatan **Layanan Mandiri (*Service Layer + Eloquent Active Record*)**.
   - Eloquent di Laravel 12 sudah sangat ekspresif, aman, dan mudah di-test menggunakan `RefreshDatabase` dengan SQLite in-memory yang sudah terbukti andal di rangkaian 373 tes DIGITREN.
2. **Kapan `LearningMaterialRepository` Dibuat?**:
   - Jika di masa mendatang pesantren mengadopsi pencarian teks lengkap terdistribusi (*Elasticsearch/Meilisearch*) atau penyimpanan materi skala multi-kampus, maka kontrak `LearningMaterialRepositoryInterface` akan dibuat untuk mengabstraksi sumber data.
