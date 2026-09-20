<?php

namespace App\Services\Academic;

use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\User;
use App\Models\WaliKelasAssignment;
use DomainException;
use Illuminate\Support\Facades\DB;

class WaliKelasAssignmentService
{
    /**
     * Assign a teacher as wali kelas for a class in a specific academic year.
     *
     * @throws DomainException
     */
    public function assign(
        Kelas $kelas,
        User $user,
        AcademicYear $academicYear,
        ?string $notes = null
    ): WaliKelasAssignment {
        return DB::transaction(function () use ($kelas, $user, $academicYear, $notes) {
            $existing = WaliKelasAssignment::where('kelas_id', $kelas->id)
                ->where('academic_year_id', $academicYear->id)
                ->first();

            if ($existing) {
                throw new DomainException("Kelas {$kelas->kelas} sudah memiliki wali kelas untuk tahun ajaran {$academicYear->name} ({$academicYear->semester}).");
            }

            return WaliKelasAssignment::create([
                'kelas_id' => $kelas->id,
                'user_id' => $user->id,
                'academic_year_id' => $academicYear->id,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Update the assigned teacher or notes for an existing assignment.
     */
    public function update(
        WaliKelasAssignment $assignment,
        User $newUser,
        ?string $notes = null
    ): WaliKelasAssignment {
        return DB::transaction(function () use ($assignment, $newUser, $notes) {
            $data = ['user_id' => $newUser->id];
            if ($notes !== null) {
                $data['notes'] = $notes;
            }

            $assignment->update($data);

            return $assignment->fresh(['kelas', 'user', 'academic_year']);
        });
    }

    /**
     * Delete an assignment record.
     */
    public function delete(WaliKelasAssignment $assignment): void
    {
        $assignment->delete();
    }
}
