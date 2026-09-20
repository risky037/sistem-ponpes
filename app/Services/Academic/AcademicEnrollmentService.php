<?php

namespace App\Services\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Santri;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AcademicEnrollmentService
{
    /**
     * Enroll a student into a class for a specific academic year.
     *
     * @throws DomainException
     * @throws InvalidArgumentException
     */
    public function enroll(
        Santri $santri,
        AcademicYear $academicYear,
        Kelas $kelas,
        ?string $notes = null,
        ?string $enrolledAt = null,
        string $status = AcademicEnrollment::STATUS_AKTIF
    ): AcademicEnrollment {
        if (! in_array($status, AcademicEnrollment::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException("Status '{$status}' tidak valid.");
        }

        return DB::transaction(function () use ($santri, $academicYear, $kelas, $notes, $enrolledAt, $status) {
            $existing = AcademicEnrollment::where('academic_year_id', $academicYear->id)
                ->where('santri_id', $santri->id)
                ->exists();

            if ($existing) {
                throw new DomainException("Santri '{$santri->no_induk}' sudah terdaftar pada tahun ajaran {$academicYear->name} ({$academicYear->semester}).");
            }

            return AcademicEnrollment::create([
                'academic_year_id' => $academicYear->id,
                'santri_id' => $santri->id,
                'kelas_id' => $kelas->id,
                'status' => $status,
                'enrolled_at' => $enrolledAt ? Carbon::parse($enrolledAt)->toDateString() : Carbon::now()->toDateString(),
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Update the class assignment for an existing enrollment.
     */
    public function updateKelas(AcademicEnrollment $enrollment, Kelas $kelas, ?string $notes = null): AcademicEnrollment
    {
        return DB::transaction(function () use ($enrollment, $kelas, $notes) {
            $data = ['kelas_id' => $kelas->id];
            if ($notes !== null) {
                $data['notes'] = $notes;
            }

            $enrollment->update($data);

            return $enrollment->fresh(['academic_year', 'santri', 'kelas']);
        });
    }

    /**
     * Update the enrollment status.
     *
     * @throws InvalidArgumentException
     */
    public function updateStatus(AcademicEnrollment $enrollment, string $status, ?string $notes = null): AcademicEnrollment
    {
        if (! in_array($status, AcademicEnrollment::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException("Status '{$status}' tidak valid.");
        }

        return DB::transaction(function () use ($enrollment, $status, $notes) {
            $data = ['status' => $status];
            if ($notes !== null) {
                $data['notes'] = $notes;
            }

            $enrollment->update($data);

            return $enrollment->fresh(['academic_year', 'santri', 'kelas']);
        });
    }

    /**
     * Deactivate an enrollment record while preserving history.
     */
    public function deactivate(AcademicEnrollment $enrollment, ?string $notes = null): AcademicEnrollment
    {
        return $this->updateStatus($enrollment, AcademicEnrollment::STATUS_NONAKTIF, $notes);
    }
}
