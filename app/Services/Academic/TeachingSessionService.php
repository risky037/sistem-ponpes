<?php

namespace App\Services\Academic;

use App\Models\ClassSchedule;
use App\Models\TeachingAssignment;
use App\Models\TeachingSession;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TeachingSessionService
{
    /**
     * Generate or plan a teaching session.
     *
     * @throws DomainException
     */
    public function generateSession(
        TeachingAssignment $teachingAssignment,
        string $sessionDate,
        ?ClassSchedule $classSchedule = null,
        ?string $notes = null
    ): TeachingSession {
        if ($classSchedule && $classSchedule->teaching_assignment_id !== $teachingAssignment->id) {
            throw new DomainException('Jadwal kelas tidak cocok dengan penugasan mengajar.');
        }

        return DB::transaction(function () use ($teachingAssignment, $sessionDate, $classSchedule, $notes) {
            return TeachingSession::create([
                'teaching_assignment_id' => $teachingAssignment->id,
                'class_schedule_id' => $classSchedule?->id,
                'session_date' => Carbon::parse($sessionDate)->toDateString(),
                'status' => TeachingSession::STATUS_PLANNED,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Mark a teaching session as completed.
     *
     * @throws DomainException
     */
    public function completeSession(TeachingSession $session, ?string $notes = null): TeachingSession
    {
        if ($session->status === TeachingSession::STATUS_CANCELLED) {
            throw new DomainException('Sesi yang telah dibatalkan tidak dapat diselesaikan secara langsung.');
        }

        return DB::transaction(function () use ($session, $notes) {
            $data = ['status' => TeachingSession::STATUS_COMPLETED];
            if ($notes !== null) {
                $data['notes'] = $notes;
            }

            $session->update($data);

            return $session->fresh(['teachingAssignment.mapel', 'teachingAssignment.user', 'classSchedule']);
        });
    }

    /**
     * Cancel a planned teaching session.
     *
     * @throws DomainException
     */
    public function cancelSession(TeachingSession $session, ?string $notes = null): TeachingSession
    {
        return DB::transaction(function () use ($session, $notes) {
            $data = ['status' => TeachingSession::STATUS_CANCELLED];
            if ($notes !== null) {
                $data['notes'] = $notes;
            }

            $session->update($data);

            return $session->fresh(['teachingAssignment.mapel', 'teachingAssignment.user', 'classSchedule']);
        });
    }
}
