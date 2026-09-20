<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TeachingAssignment;
use App\Models\TeachingSession;
use App\Models\User;
use App\Services\Academic\AcademicScheduleService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassScheduleCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $santriUser;

    protected Kelas $kelas;

    protected Mapel $mapel;

    protected AcademicYear $academicYear;

    protected TeachingAssignment $teachingAssignment;

    protected AcademicScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        $santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Jadwal',
            'email' => 'admin.jadwal@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->pengurus = User::create([
            'name' => 'Pengurus Jadwal',
            'email' => 'pengurus.jadwal@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->pengurus->assignRole($pengurusRole);

        $this->santriUser = User::create([
            'name' => 'Santri Jadwal',
            'email' => 'santri.jadwal@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->santriUser->assignRole($santriRole);

        $this->kelas = Kelas::create([
            'kode' => 'KLS-SCH01',
            'tingkatan' => 'ALFIYAH',
            'kelas' => 'Alfiyah 1',
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
            'code' => 'MPL-SCH-01',
            'name' => 'Nahwu Alfiyah',
            'is_active' => true,
        ]);

        $this->teachingAssignment = TeachingAssignment::create([
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $this->mapel->id,
            'user_id' => $this->pengurus->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => 'Aktif',
        ]);

        $this->service = app(AcademicScheduleService::class);
    }

    public function test_class_schedule_index_renders_for_authorized_users(): void
    {
        $response = $this->actingAs($this->admin)->get(route('class-schedule.index'));
        $response->assertStatus(200);
        $response->assertSee('Jadwal Pelajaran');

        $response = $this->actingAs($this->pengurus)->get(route('class-schedule.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->santriUser)->get(route('class-schedule.index'));
        $response->assertStatus(403);
    }

    public function test_class_schedule_datatable_ajax_returns_json(): void
    {
        $this->service->createSchedule(
            kelas: $this->kelas,
            teachingAssignment: $this->teachingAssignment,
            academicYear: $this->academicYear,
            dayOfWeek: 'Senin',
            startTime: '07:30',
            endTime: '09:00',
            room: 'Gedung A'
        );

        $response = $this->actingAs($this->admin)
            ->getJson(route('class-schedule.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'recordsTotal']);
        $this->assertStringContainsString('Nahwu Alfiyah', $response->getContent());
    }

    public function test_service_creates_schedule_successfully(): void
    {
        $schedule = $this->service->createSchedule(
            kelas: $this->kelas,
            teachingAssignment: $this->teachingAssignment,
            academicYear: $this->academicYear,
            dayOfWeek: 'Selasa',
            startTime: '08:00',
            endTime: '09:30',
            room: 'Masjid Lt 2',
            notes: 'Kajian pagi'
        );

        $this->assertInstanceOf(ClassSchedule::class, $schedule);
        $this->assertDatabaseHas('class_schedules', [
            'kelas_id' => $this->kelas->id,
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'day_of_week' => 'Selasa',
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
            'room' => 'Masjid Lt 2',
        ]);
    }

    public function test_service_rejects_invalid_time_range(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Waktu mulai harus lebih awal');

        $this->service->createSchedule(
            kelas: $this->kelas,
            teachingAssignment: $this->teachingAssignment,
            academicYear: $this->academicYear,
            dayOfWeek: 'Rabu',
            startTime: '10:00',
            endTime: '09:00'
        );
    }

    public function test_service_rejects_duplicate_slot(): void
    {
        $this->service->createSchedule(
            kelas: $this->kelas,
            teachingAssignment: $this->teachingAssignment,
            academicYear: $this->academicYear,
            dayOfWeek: 'Kamis',
            startTime: '07:30',
            endTime: '09:00'
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('sudah ada');

        $this->service->createSchedule(
            kelas: $this->kelas,
            teachingAssignment: $this->teachingAssignment,
            academicYear: $this->academicYear,
            dayOfWeek: 'Kamis',
            startTime: '07:30',
            endTime: '09:00'
        );
    }

    public function test_controller_store_creates_schedule_via_request(): void
    {
        $response = $this->actingAs($this->admin)->post(route('class-schedule.store'), [
            'kelas_id' => $this->kelas->id,
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'academic_year_id' => $this->academicYear->id,
            'day_of_week' => 'Jumat',
            'start_time' => '07:30',
            'end_time' => '09:00',
            'room' => 'Kelas 1A',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('class_schedules', [
            'kelas_id' => $this->kelas->id,
            'day_of_week' => 'Jumat',
        ]);
    }

    public function test_schedule_deletion_blocked_when_teaching_sessions_exist(): void
    {
        $schedule = $this->service->createSchedule(
            kelas: $this->kelas,
            teachingAssignment: $this->teachingAssignment,
            academicYear: $this->academicYear,
            dayOfWeek: 'Sabtu',
            startTime: '08:00',
            endTime: '09:30'
        );

        TeachingSession::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'class_schedule_id' => $schedule->id,
            'session_date' => '2024-08-10',
            'status' => 'Planned',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('riwayat sesi pembelajaran');

        $this->service->deleteSchedule($schedule);
    }

    public function test_kelas_cannot_be_deleted_when_schedule_exists(): void
    {
        $this->service->createSchedule(
            kelas: $this->kelas,
            teachingAssignment: $this->teachingAssignment,
            academicYear: $this->academicYear,
            dayOfWeek: 'Ahad',
            startTime: '08:00',
            endTime: '09:30'
        );

        $response = $this->actingAs($this->admin)->delete(route('kelas.destroy', $this->kelas->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('kelas', ['id' => $this->kelas->id]);
    }

    public function test_academic_year_cannot_be_deleted_when_schedule_exists(): void
    {
        $this->service->createSchedule(
            kelas: $this->kelas,
            teachingAssignment: $this->teachingAssignment,
            academicYear: $this->academicYear,
            dayOfWeek: 'Ahad',
            startTime: '10:00',
            endTime: '11:30'
        );

        $response = $this->actingAs($this->admin)->delete(route('academic-year.destroy', $this->academicYear->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('academic_years', ['id' => $this->academicYear->id]);
    }
}
