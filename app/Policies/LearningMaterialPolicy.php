<?php

namespace App\Policies;

use App\Models\AcademicEnrollment;
use App\Models\LearningMaterial;
use App\Models\Santri;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LearningMaterialPolicy
{
    use HandlesAuthorization;

    /**
     * Administrator bypass: Administrator has full access over all learning materials.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view the list of learning materials.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Guru', 'Pengurus', 'Santri']);
    }

    /**
     * Determine whether the user can view a specific learning material.
     */
    public function view(User $user, LearningMaterial $material): bool
    {
        // 1. Pengurus has read access for institutional academic supervision
        if ($user->hasRole('Pengurus')) {
            return true;
        }

        // 2. Guru can view if author, or if teaching the same subject in active assignments and material is published
        if ($user->hasRole('Guru')) {
            if ($material->teacher_id === $user->id) {
                return true;
            }

            $teachesSameMapel = TeachingAssignment::where('user_id', $user->id)
                ->where('mapel_id', $material->mapel_id)
                ->where('status', TeachingAssignment::STATUS_AKTIF)
                ->exists();

            return $teachesSameMapel && $material->status === LearningMaterial::STATUS_PUBLISHED;
        }

        // 3. Santri can view only if material is published and targeted to their enrolled active class
        if ($user->hasRole('Santri')) {
            if ($material->status !== LearningMaterial::STATUS_PUBLISHED) {
                return false;
            }

            $santriId = $user->santri?->id ?? Santri::where('user_id', $user->id)->value('id');
            if (! $santriId) {
                return false;
            }

            $activeClassIds = AcademicEnrollment::where('santri_id', $santriId)
                ->where('academic_year_id', $material->academic_year_id)
                ->where('status', AcademicEnrollment::STATUS_AKTIF)
                ->pluck('kelas_id')
                ->toArray();

            if (empty($activeClassIds)) {
                return false;
            }

            if ($material->kelas_id && in_array($material->kelas_id, $activeClassIds)) {
                return true;
            }

            return $material->targets()
                ->whereIn('kelas_id', $activeClassIds)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create a new learning material.
     */
    public function create(User $user): bool
    {
        if (! $user->hasRole('Guru')) {
            return false;
        }

        return TeachingAssignment::where('user_id', $user->id)
            ->where('status', TeachingAssignment::STATUS_AKTIF)
            ->exists();
    }

    /**
     * Determine whether the user can update the learning material.
     */
    public function update(User $user, LearningMaterial $material): bool
    {
        return $user->hasRole('Guru') && $material->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can delete the learning material.
     */
    public function delete(User $user, LearningMaterial $material): bool
    {
        return $user->hasRole('Guru') && $material->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can publish the learning material.
     */
    public function publish(User $user, LearningMaterial $material): bool
    {
        return $user->hasRole('Guru') && $material->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can archive the learning material.
     */
    public function archive(User $user, LearningMaterial $material): bool
    {
        return $user->hasRole('Guru') && $material->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can upload attachments to the learning material.
     */
    public function uploadAttachment(User $user, LearningMaterial $material): bool
    {
        return $user->hasRole('Guru') && $material->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can restore the learning material.
     */
    public function restore(User $user, LearningMaterial $material): bool
    {
        return $user->hasRole('Guru') && $material->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the learning material.
     */
    public function forceDelete(User $user, LearningMaterial $material): bool
    {
        return $user->hasRole('Guru') && $material->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can download file attachments from the material.
     */
    public function downloadFile(User $user, LearningMaterial $material): bool
    {
        return $this->view($user, $material);
    }
}
