<?php

namespace App\Services\Academic;

use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Kelas;
use App\Models\TeachingAssignment;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AcademicScheduleService
{
    /**
     * Create a new class schedule slot.
     *
     * @throws DomainException
     * @throws InvalidArgumentException
     */
    public function createSchedule(
        Kelas $kelas,
        TeachingAssignment $teachingAssignment,
        AcademicYear $academicYear,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        ?string $room = null,
        ?string $notes = null
    ): ClassSchedule {
        if (! in_array($dayOfWeek, ClassSchedule::DAYS_OF_WEEK, true)) {
            throw new InvalidArgumentException("Hari '{$dayOfWeek}' tidak valid.");
        }

        if (strtotime($startTime) >= strtotime($endTime)) {
            throw new DomainException('Waktu mulai harus lebih awal dari waktu selesai.');
        }

        if ($teachingAssignment->kelas_id !== $kelas->id) {
            throw new DomainException("Penugasan mengajar tidak sesuai dengan kelas {$kelas->kelas}.");
        }

        if ($teachingAssignment->academic_year_id !== $academicYear->id) {
            throw new DomainException("Penugasan mengajar tidak sesuai dengan tahun ajaran {$academicYear->name}.");
        }

        return DB::transaction(function () use (
            $kelas,
            $teachingAssignment,
            $academicYear,
            $dayOfWeek,
            $startTime,
            $endTime,
            $room,
            $notes
        ) {
            $existing = ClassSchedule::where('kelas_id', $kelas->id)
                ->where('teaching_assignment_id', $teachingAssignment->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $startTime)
                ->first();

            if ($existing) {
                throw new DomainException("Jadwal untuk mata pelajaran dan kelas ini pada hari {$dayOfWeek} pukul {$startTime} sudah ada.");
            }

            return ClassSchedule::create([
                'kelas_id' => $kelas->id,
                'teaching_assignment_id' => $teachingAssignment->id,
                'academic_year_id' => $academicYear->id,
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room' => $room,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Update an existing class schedule slot.
     *
     * @throws DomainException
     * @throws InvalidArgumentException
     */
    public function updateSchedule(ClassSchedule $schedule, array $data): ClassSchedule
    {
        if (isset($data['day_of_week']) && ! in_array($data['day_of_week'], ClassSchedule::DAYS_OF_WEEK, true)) {
            throw new InvalidArgumentException("Hari '{$data['day_of_week']}' tidak valid.");
        }

        $startTime = $data['start_time'] ?? $schedule->start_time;
        $endTime = $data['end_time'] ?? $schedule->end_time;

        if (strtotime($startTime) >= strtotime($endTime)) {
            throw new DomainException('Waktu mulai harus lebih awal dari waktu selesai.');
        }

        return DB::transaction(function () use ($schedule, $data) {
            $schedule->update($data);

            return $schedule->fresh(['kelas', 'teachingAssignment.mapel', 'teachingAssignment.user', 'academicYear']);
        });
    }

    /**
     * Delete a schedule slot with historical session protection.
     *
     * @throws DomainException
     */
    public function deleteSchedule(ClassSchedule $schedule): void
    {
        if ($schedule->teachingSessions()->exists()) {
            throw new DomainException('Tidak dapat menghapus jadwal yang telah memiliki riwayat sesi pembelajaran.');
        }

        $schedule->delete();
    }
}
