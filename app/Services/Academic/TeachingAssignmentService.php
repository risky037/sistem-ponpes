<?php

namespace App\Services\Academic;

use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TeachingAssignment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TeachingAssignmentService
{
    /**
     * Assign a teacher to teach a subject in a class for a specific academic year.
     *
     * @throws DomainException
     * @throws InvalidArgumentException
     */
    public function assign(
        Kelas $kelas,
        Mapel $mapel,
        User $teacher,
        AcademicYear $academicYear,
        ?string $notes = null,
        string $status = TeachingAssignment::STATUS_AKTIF
    ): TeachingAssignment {
        if (! in_array($status, TeachingAssignment::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException("Status '{$status}' tidak valid.");
        }

        if ($mapel->kelas_id !== $kelas->id) {
            throw new DomainException("Mata pelajaran '{$mapel->name}' tidak terdaftar pada kelas {$kelas->kelas}.");
        }

        return DB::transaction(function () use ($kelas, $mapel, $teacher, $academicYear, $notes, $status) {
            $existing = TeachingAssignment::where('kelas_id', $kelas->id)
                ->where('mapel_id', $mapel->id)
                ->where('academic_year_id', $academicYear->id)
                ->first();

            if ($existing) {
                throw new DomainException("Mata pelajaran '{$mapel->name}' di kelas {$kelas->kelas} sudah memiliki penugasan pengajar pada tahun ajaran {$academicYear->name} ({$academicYear->semester}).");
            }

            return TeachingAssignment::create([
                'kelas_id' => $kelas->id,
                'mapel_id' => $mapel->id,
                'user_id' => $teacher->id,
                'academic_year_id' => $academicYear->id,
                'status' => $status,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Update the assigned teacher or notes for an existing assignment.
     */
    public function updateTeacher(
        TeachingAssignment $assignment,
        User $newTeacher,
        ?string $notes = null
    ): TeachingAssignment {
        return DB::transaction(function () use ($assignment, $newTeacher, $notes) {
            $data = ['user_id' => $newTeacher->id];
            if ($notes !== null) {
                $data['notes'] = $notes;
            }

            $assignment->update($data);

            return $assignment->fresh(['kelas', 'mapel', 'user', 'academic_year']);
        });
    }

    /**
     * Update assignment status.
     *
     * @throws InvalidArgumentException
     */
    public function updateStatus(
        TeachingAssignment $assignment,
        string $status,
        ?string $notes = null
    ): TeachingAssignment {
        if (! in_array($status, TeachingAssignment::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException("Status '{$status}' tidak valid.");
        }

        return DB::transaction(function () use ($assignment, $status, $notes) {
            $data = ['status' => $status];
            if ($notes !== null) {
                $data['notes'] = $notes;
            }

            $assignment->update($data);

            return $assignment->fresh(['kelas', 'mapel', 'user', 'academic_year']);
        });
    }

    /**
     * Soft-deactivate an assignment while preserving history.
     */
    public function deactivate(TeachingAssignment $assignment, ?string $notes = null): TeachingAssignment
    {
        return $this->updateStatus($assignment, TeachingAssignment::STATUS_NONAKTIF, $notes);
    }

    /**
     * Reactivate an assignment.
     */
    public function reactivate(TeachingAssignment $assignment, ?string $notes = null): TeachingAssignment
    {
        return $this->updateStatus($assignment, TeachingAssignment::STATUS_AKTIF, $notes);
    }
}
