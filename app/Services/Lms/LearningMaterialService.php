<?php

namespace App\Services\Lms;

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialFile;
use App\Models\Santri;
use App\Models\TeachingAssignment;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LearningMaterialService
{
    /**
     * Create a new learning material with target classes and optional file attachments.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int>  $targetClassIds
     * @param  array<UploadedFile>  $files
     *
     * @throws DomainException
     */
    public function createMaterial(User $teacher, array $data, array $targetClassIds = [], array $files = []): LearningMaterial
    {
        $mapelId = (int) ($data['mapel_id'] ?? 0);
        $academicYearId = (int) ($data['academic_year_id'] ?? 0);

        $this->validateTeachingAssignment($teacher, $mapelId, $academicYearId);

        return DB::transaction(function () use ($teacher, $data, $targetClassIds, $files, $mapelId, $academicYearId) {
            $title = trim($data['title'] ?? '');
            if ($title === '') {
                throw new DomainException('Judul materi pembelajaran tidak boleh kosong.');
            }

            $slug = $this->generateUniqueSlug($title);
            $status = $data['status'] ?? LearningMaterial::STATUS_DRAFT;
            $publishedAt = ($status === LearningMaterial::STATUS_PUBLISHED)
                ? ($data['published_at'] ?? now())
                : null;

            $primaryKelasId = $data['kelas_id'] ?? ($targetClassIds[0] ?? null);

            $material = LearningMaterial::create([
                'teacher_id' => $teacher->id,
                'mapel_id' => $mapelId,
                'kelas_id' => $primaryKelasId,
                'academic_year_id' => $academicYearId,
                'title' => $title,
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'content_type' => $data['content_type'] ?? LearningMaterial::CONTENT_TYPE_TEXT,
                'content' => $data['content'] ?? null,
                'video_url' => $data['video_url'] ?? null,
                'external_url' => $data['external_url'] ?? null,
                'status' => $status,
                'published_at' => $publishedAt,
                'created_by' => $teacher->id,
            ]);

            if (! empty($targetClassIds)) {
                $material->targets()->sync($targetClassIds);
            } elseif ($primaryKelasId) {
                $material->targets()->sync([$primaryKelasId]);
            }

            if (! empty($files)) {
                $this->storeAttachments($material, $files);
            }

            return $material->load(['mapel', 'teacher', 'targets', 'files', 'academicYear']);
        });
    }

    /**
     * Update an existing learning material.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int>|null  $targetClassIds
     * @param  array<UploadedFile>  $files
     *
     * @throws DomainException
     */
    public function updateMaterial(
        LearningMaterial $material,
        array $data,
        ?array $targetClassIds = null,
        array $files = []
    ): LearningMaterial {
        return DB::transaction(function () use ($material, $data, $targetClassIds, $files) {
            $updateData = [];

            if (isset($data['title'])) {
                $newTitle = trim($data['title']);
                if ($newTitle === '') {
                    throw new DomainException('Judul materi pembelajaran tidak boleh kosong.');
                }
                if ($newTitle !== $material->title) {
                    $updateData['title'] = $newTitle;
                    $updateData['slug'] = $this->generateUniqueSlug($newTitle, $material->id);
                }
            }

            $updatableFields = [
                'description',
                'content_type',
                'content',
                'video_url',
                'external_url',
                'status',
                'kelas_id',
            ];

            foreach ($updatableFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (isset($updateData['status'])) {
                if ($updateData['status'] === LearningMaterial::STATUS_PUBLISHED && ! $material->published_at) {
                    $updateData['published_at'] = now();
                } elseif ($updateData['status'] === LearningMaterial::STATUS_DRAFT) {
                    $updateData['published_at'] = null;
                }
            }

            if (! empty($updateData)) {
                $material->update($updateData);
            }

            if ($targetClassIds !== null) {
                $material->targets()->sync($targetClassIds);
            }

            if (! empty($data['remove_file_ids']) && is_array($data['remove_file_ids'])) {
                $filesToRemove = $material->files()->whereIn('id', $data['remove_file_ids'])->get();
                foreach ($filesToRemove as $fileToRemove) {
                    $this->deleteAttachment($fileToRemove);
                }
            }

            if (! empty($files)) {
                $this->storeAttachments($material, $files);
            }

            return $material->fresh(['mapel', 'teacher', 'targets', 'files', 'academicYear']);
        });
    }

    /**
     * Publish a draft material.
     */
    public function publishMaterial(LearningMaterial $material): LearningMaterial
    {
        $material->update([
            'status' => LearningMaterial::STATUS_PUBLISHED,
            'published_at' => $material->published_at ?? now(),
        ]);

        return $material->fresh();
    }

    /**
     * Archive an active material.
     */
    public function archiveMaterial(LearningMaterial $material): LearningMaterial
    {
        $material->update([
            'status' => LearningMaterial::STATUS_ARCHIVED,
        ]);

        return $material->fresh();
    }

    /**
     * Delete a learning material (soft delete by default, or force delete with physical file cleanup).
     */
    public function deleteMaterial(LearningMaterial $material, bool $force = false): bool
    {
        return DB::transaction(function () use ($material, $force) {
            if ($force) {
                foreach ($material->files as $file) {
                    $this->deleteAttachment($file);
                }

                $material->targets()->detach();

                return (bool) $material->forceDelete();
            }

            return (bool) $material->delete();
        });
    }

    /**
     * Restore a soft-deleted learning material.
     */
    public function restoreMaterial(LearningMaterial|int $material): LearningMaterial
    {
        $instance = $material instanceof LearningMaterial
            ? $material
            : LearningMaterial::withTrashed()->findOrFail($material);

        $instance->restore();

        return $instance->fresh(['mapel', 'teacher', 'targets', 'files', 'academicYear']);
    }

    /**
     * Store multiple file attachments for a learning material.
     *
     * @param  array<UploadedFile>  $files
     * @return Collection<int, LearningMaterialFile>
     */
    public function storeAttachments(LearningMaterial $material, array $files): Collection
    {
        $saved = collect();
        $baseDir = "materials/{$material->academic_year_id}/{$material->id}";

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
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
     * Remove a file attachment and delete it from storage.
     */
    public function deleteAttachment(LearningMaterialFile $file): bool
    {
        if ($file->file_path && Storage::disk('local')->exists($file->file_path)) {
            Storage::disk('local')->delete($file->file_path);
        }

        return (bool) $file->delete();
    }

    /**
     * Atomically increment download count on a file.
     */
    public function incrementDownloadCount(LearningMaterialFile $file): int
    {
        $file->increment('download_count');

        return (int) $file->fresh()->download_count;
    }

    /**
     * Retrieve paginated materials accessible to a santri in an academic year.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getMaterialsForSantri(
        User $user,
        AcademicYear $academicYear,
        array $filters = [],
        int $perPage = 12
    ): LengthAwarePaginator {
        $santriId = $user->santri?->id ?? Santri::where('user_id', $user->id)->value('id');

        if (! $santriId) {
            return LearningMaterial::whereRaw('1 = 0')->paginate($perPage);
        }

        $enrollment = AcademicEnrollment::where('santri_id', $santriId)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', AcademicEnrollment::STATUS_AKTIF)
            ->first();

        if (! $enrollment) {
            return LearningMaterial::whereRaw('1 = 0')->paginate($perPage);
        }

        $query = LearningMaterial::query()
            ->with(['mapel', 'teacher', 'files', 'targets'])
            ->where('academic_year_id', $academicYear->id)
            ->published()
            ->forClass($enrollment->kelas_id);

        if (! empty($filters['mapel_id'])) {
            $query->where('mapel_id', (int) $filters['mapel_id']);
        }

        if (! empty($filters['content_type'])) {
            $query->where('content_type', $filters['content_type']);
        }

        if (! empty($filters['search'])) {
            $keyword = '%'.trim($filters['search']).'%';
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', $keyword)
                    ->orWhere('description', 'like', $keyword)
                    ->orWhere('content', 'like', $keyword);
            });
        }

        return $query->latest('published_at')->paginate($perPage);
    }

    /**
     * Retrieve materials created by or assigned to a teacher.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, LearningMaterial>
     */
    public function getMaterialsForTeacher(
        User $teacher,
        ?int $academicYearId = null,
        array $filters = []
    ): Collection {
        $query = LearningMaterial::query()
            ->with(['mapel', 'targets', 'files', 'academicYear'])
            ->where('teacher_id', $teacher->id);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['mapel_id'])) {
            $query->where('mapel_id', (int) $filters['mapel_id']);
        }

        if (! empty($filters['kelas_id'])) {
            $query->forClass((int) $filters['kelas_id']);
        }

        return $query->latest('created_at')->get();
    }

    /**
     * Check if a user is permitted to view a material.
     */
    public function validateVisibility(User $user, LearningMaterial $material): bool
    {
        return Gate::forUser($user)->allows('view', $material);
    }

    /**
     * Validate that a teacher has an active teaching assignment for the subject in the academic year.
     *
     * @throws DomainException
     */
    public function validateTeachingAssignment(User $teacher, int $mapelId, int $academicYearId): void
    {
        if ($teacher->hasRole('Administrator')) {
            return;
        }

        $hasAssignment = TeachingAssignment::where('user_id', $teacher->id)
            ->where('mapel_id', $mapelId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', TeachingAssignment::STATUS_AKTIF)
            ->exists();

        if (! $hasAssignment) {
            throw new DomainException('Guru tidak memiliki SK Mengajar aktif untuk mata pelajaran ini pada semester berjalan.');
        }
    }

    /**
     * Generate an URL-safe unique slug for a learning material.
     */
    public function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        if ($baseSlug === '') {
            $baseSlug = 'materi-'.time();
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            LearningMaterial::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-".Str::lower(Str::random(4));
            $counter++;
            if ($counter > 15) {
                $slug = "{$baseSlug}-".time();
                break;
            }
        }

        return $slug;
    }
}
