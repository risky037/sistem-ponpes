<?php

namespace App\Services\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AttendanceRecord;
use App\Models\TeachingSession;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Mark single attendance for a student in a teaching session.
     *
     * @throws DomainException
     */
    public function markAttendance(
        TeachingSession $session,
        AcademicEnrollment $enrollment,
        string $status,
        ?User $marker = null,
        ?string $notes = null
    ): AttendanceRecord {
        $this->validateSessionForAttendance($session);
        $this->validateEnrollmentForSession($session, $enrollment);
        $this->validateStatus($status);

        $existing = AttendanceRecord::where('teaching_session_id', $session->id)
            ->where('academic_enrollment_id', $enrollment->id)
            ->first();

        if ($existing) {
            throw new DomainException('Catatan kehadiran untuk santri ini pada sesi tersebut sudah ada.');
        }

        $markerId = $marker?->id ?? auth()->id();

        return DB::transaction(function () use ($session, $enrollment, $status, $markerId, $notes) {
            return AttendanceRecord::create([
                'teaching_session_id' => $session->id,
                'academic_enrollment_id' => $enrollment->id,
                'status' => $status,
                'notes' => $notes,
                'marked_at' => now(),
                'marked_by' => $markerId,
            ]);
        });
    }

    /**
     * Bulk mark or update attendance for all students in a teaching session.
     *
     * @param  array<int, array{academic_enrollment_id: int, status: string, notes?: string|null}>  $records
     * @return Collection<int, AttendanceRecord>
     *
     * @throws DomainException
     */
    public function bulkMarkAttendance(
        TeachingSession $session,
        array $records,
        ?User $marker = null
    ): Collection {
        $this->validateSessionForAttendance($session);

        $markerId = $marker?->id ?? auth()->id();

        return DB::transaction(function () use ($session, $records, $markerId) {
            $results = collect();

            foreach ($records as $item) {
                $enrollmentId = $item['academic_enrollment_id'] ?? null;
                $status = $item['status'] ?? null;
                $notes = $item['notes'] ?? null;

                if (! $enrollmentId || ! $status) {
                    continue;
                }

                $enrollment = AcademicEnrollment::findOrFail($enrollmentId);
                $this->validateEnrollmentForSession($session, $enrollment);
                $this->validateStatus($status);

                $record = AttendanceRecord::updateOrCreate(
                    [
                        'teaching_session_id' => $session->id,
                        'academic_enrollment_id' => $enrollment->id,
                    ],
                    [
                        'status' => $status,
                        'notes' => $notes,
                        'marked_at' => now(),
                        'marked_by' => $markerId,
                    ]
                );

                $results->push($record);
            }

            return $results;
        });
    }

    /**
     * Update an existing attendance record.
     *
     * @throws DomainException
     */
    public function updateAttendance(
        AttendanceRecord $record,
        string $status,
        ?User $marker = null,
        ?string $notes = null
    ): AttendanceRecord {
        $session = $record->teachingSession;
        if ($session) {
            $this->validateSessionForAttendance($session);
        }

        $this->validateStatus($status);

        $markerId = $marker?->id ?? auth()->id();

        return DB::transaction(function () use ($record, $status, $markerId, $notes) {
            $record->update([
                'status' => $status,
                'notes' => $notes,
                'marked_at' => now(),
                'marked_by' => $markerId,
            ]);

            return $record->fresh(['teachingSession', 'academicEnrollment.santri', 'marker']);
        });
    }

    /**
     * Validate attendance completeness for a teaching session without mutating session status.
     *
     * @throws DomainException
     */
    public function completeAttendance(TeachingSession $session): bool
    {
        $this->validateSessionForAttendance($session);

        $assignment = $session->teachingAssignment;
        if (! $assignment) {
            throw new DomainException('Sesi pembelajaran tidak memiliki penugasan mengajar yang valid.');
        }

        // Get total active enrollments for this class & academic year
        $activeEnrollmentCount = AcademicEnrollment::where('kelas_id', $assignment->kelas_id)
            ->where('academic_year_id', $assignment->academic_year_id)
            ->where('status', AcademicEnrollment::STATUS_AKTIF)
            ->count();

        // Get total marked attendance for this session
        $markedCount = AttendanceRecord::where('teaching_session_id', $session->id)
            ->whereHas('academicEnrollment', function ($q) use ($assignment) {
                $q->where('kelas_id', $assignment->kelas_id)
                    ->where('academic_year_id', $assignment->academic_year_id)
                    ->where('status', AcademicEnrollment::STATUS_AKTIF);
            })
            ->count();

        if ($markedCount < $activeEnrollmentCount) {
            $missing = $activeEnrollmentCount - $markedCount;
            throw new DomainException("Presensi belum lengkap: masih terdapat {$missing} santri aktif yang belum dicatat.");
        }

        return true;
    }

    /**
     * Validate that the session is eligible for attendance recording.
     *
     * @throws DomainException
     */
    protected function validateSessionForAttendance(TeachingSession $session): void
    {
        if ($session->status === TeachingSession::STATUS_CANCELLED) {
            throw new DomainException('Tidak dapat mencatat kehadiran untuk sesi pembelajaran yang telah dibatalkan.');
        }
    }

    /**
     * Validate that the student enrollment belongs to the session class and academic year.
     *
     * @throws DomainException
     */
    protected function validateEnrollmentForSession(TeachingSession $session, AcademicEnrollment $enrollment): void
    {
        $assignment = $session->teachingAssignment;
        if (! $assignment) {
            throw new DomainException('Sesi pembelajaran tidak memiliki penugasan mengajar.');
        }

        if ((int) $enrollment->kelas_id !== (int) $assignment->kelas_id) {
            throw new DomainException('Santri tidak terdaftar di kelas sesi pembelajaran ini.');
        }

        if ((int) $enrollment->academic_year_id !== (int) $assignment->academic_year_id) {
            throw new DomainException('Santri tidak terdaftar di tahun ajaran sesi pembelajaran ini.');
        }

        if ($enrollment->status !== AcademicEnrollment::STATUS_AKTIF) {
            throw new DomainException('Santri tidak berstatus aktif dalam penempatan akademik ini.');
        }
    }

    /**
     * Validate attendance status.
     *
     * @throws DomainException
     */
    protected function validateStatus(string $status): void
    {
        if (! in_array($status, AttendanceRecord::ALLOWED_STATUSES, true)) {
            throw new DomainException("Status kehadiran '{$status}' tidak valid.");
        }
    }
}
