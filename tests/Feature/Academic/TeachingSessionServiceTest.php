<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TeachingAssignment;
use App\Models\TeachingSession;
use App\Models\User;
use App\Services\Academic\TeachingSessionService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeachingSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;

    protected Kelas $kelas;

    protected Mapel $mapel;

    protected AcademicYear $academicYear;

    protected TeachingAssignment $teachingAssignment;

    protected ClassSchedule $classSchedule;

    protected TeachingSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);

        $this->guru = User::create([
            'name' => 'Ustadz Zaid',
            'email' => 'ustadz.zaid@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->guru->assignRole($pengurusRole);

        $this->kelas = Kelas::create([
            'kode' => 'KLS-TS01',
            'tingkatan' => 'ALFIYAH',
            'kelas' => 'Alfiyah 1 Reguler',
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-15',
            'end_date' => '2024-12-20',
            'is_active' => true,
        ]);

        $this->mapel = Mapel::create([
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-TS-01',
            'name' => 'Nahwu Jurumiyah',
            'is_active' => true,
        ]);

        $this->teachingAssignment = TeachingAssignment::create([
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $this->mapel->id,
            'user_id' => $this->guru->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => 'Aktif',
        ]);

        $this->classSchedule = ClassSchedule::create([
            'kelas_id' => $this->kelas->id,
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'academic_year_id' => $this->academicYear->id,
            'day_of_week' => 'Senin',
            'start_time' => '07:30',
            'end_time' => '09:00',
            'room' => 'Ruang 101',
        ]);

        $this->service = app(TeachingSessionService::class);
    }

    public function test_generate_session_creates_planned_session_without_schedule(): void
    {
        $session = $this->service->generateSession(
            teachingAssignment: $this->teachingAssignment,
            sessionDate: '2024-08-01',
            classSchedule: null,
            notes: 'Sesi pengenalan silabus'
        );

        $this->assertInstanceOf(TeachingSession::class, $session);
        $this->assertEquals(TeachingSession::STATUS_PLANNED, $session->status);
        $this->assertEquals('2024-08-01', $session->session_date->format('Y-m-d'));
        $this->assertNull($session->class_schedule_id);
        $this->assertEquals('Sesi pengenalan silabus', $session->notes);
        $this->assertDatabaseHas('teaching_sessions', [
            'id' => $session->id,
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'status' => TeachingSession::STATUS_PLANNED,
        ]);
    }

    public function test_generate_session_creates_planned_session_with_schedule(): void
    {
        $session = $this->service->generateSession(
            teachingAssignment: $this->teachingAssignment,
            sessionDate: '2024-08-05',
            classSchedule: $this->classSchedule,
            notes: 'Pertemuan Bab 1'
        );

        $this->assertInstanceOf(TeachingSession::class, $session);
        $this->assertEquals($this->classSchedule->id, $session->class_schedule_id);
        $this->assertEquals(TeachingSession::STATUS_PLANNED, $session->status);
        $this->assertDatabaseHas('teaching_sessions', [
            'id' => $session->id,
            'class_schedule_id' => $this->classSchedule->id,
            'status' => 'Planned',
        ]);
    }

    public function test_generate_session_rejects_mismatched_teaching_assignment_and_schedule(): void
    {
        $otherMapel = Mapel::create([
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-TS-02',
            'name' => 'Shorof',
            'is_active' => true,
        ]);

        $otherAssignment = TeachingAssignment::create([
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $otherMapel->id,
            'user_id' => $this->guru->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => 'Aktif',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Jadwal kelas tidak cocok');

        // Trying to bind classSchedule (which belongs to $this->teachingAssignment) to $otherAssignment
        $this->service->generateSession(
            teachingAssignment: $otherAssignment,
            sessionDate: '2024-08-05',
            classSchedule: $this->classSchedule
        );
    }

    public function test_complete_session_transitions_status_to_completed(): void
    {
        $session = $this->service->generateSession(
            teachingAssignment: $this->teachingAssignment,
            sessionDate: '2024-08-05',
            classSchedule: $this->classSchedule
        );

        $completed = $this->service->completeSession($session, 'Materi bab 1 tuntas dibahas.');

        $this->assertEquals(TeachingSession::STATUS_COMPLETED, $completed->status);
        $this->assertEquals('Materi bab 1 tuntas dibahas.', $completed->notes);
        $this->assertDatabaseHas('teaching_sessions', [
            'id' => $session->id,
            'status' => 'Completed',
            'notes' => 'Materi bab 1 tuntas dibahas.',
        ]);
    }

    public function test_complete_session_rejects_cancelled_session(): void
    {
        $session = $this->service->generateSession(
            teachingAssignment: $this->teachingAssignment,
            sessionDate: '2024-08-05',
            classSchedule: $this->classSchedule
        );

        $this->service->cancelSession($session, 'Ustadz berhalangan hadir sakit.');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('telah dibatalkan tidak dapat diselesaikan');

        $this->service->completeSession($session);
    }

    public function test_cancel_session_transitions_status_to_cancelled(): void
    {
        $session = $this->service->generateSession(
            teachingAssignment: $this->teachingAssignment,
            sessionDate: '2024-08-05',
            classSchedule: $this->classSchedule
        );

        $cancelled = $this->service->cancelSession($session, 'Hari libur mendadak');

        $this->assertEquals(TeachingSession::STATUS_CANCELLED, $cancelled->status);
        $this->assertEquals('Hari libur mendadak', $cancelled->notes);
        $this->assertDatabaseHas('teaching_sessions', [
            'id' => $session->id,
            'status' => 'Cancelled',
            'notes' => 'Hari libur mendadak',
        ]);
    }
}
