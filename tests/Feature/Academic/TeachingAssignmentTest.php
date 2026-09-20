<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Academic\TeachingAssignmentService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeachingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher1;

    protected User $teacher2;

    protected User $santriUser;

    protected Kelas $kelasA;

    protected Kelas $kelasB;

    protected Mapel $mapelA;

    protected Mapel $mapelB;

    protected AcademicYear $academicYear;

    protected TeachingAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        $santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Pengajaran',
            'email' => 'admin.teach@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->teacher1 = User::create([
            'name' => 'Ustadz Zaid',
            'email' => 'zaid@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->teacher1->assignRole($pengurusRole);

        $this->teacher2 = User::create([
            'name' => 'Ustadz Umar',
            'email' => 'umar@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->teacher2->assignRole($pengurusRole);

        $this->santriUser = User::create([
            'name' => 'Santri Mengajar',
            'email' => 'santri.teach@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->santriUser->assignRole($santriRole);

        $this->kelasA = Kelas::create([
            'kode' => 'KLS-TCH01',
            'tingkatan' => 'ALFIYAH',
            'kelas' => 'Alfiyah A',
        ]);

        $this->kelasB = Kelas::create([
            'kode' => 'KLS-TCH02',
            'tingkatan' => 'ALFIYAH',
            'kelas' => 'Alfiyah B',
        ]);

        $this->mapelA = Mapel::create([
            'kelas_id' => $this->kelasA->id,
            'code' => 'MPL-TCH-A',
            'name' => 'Nahwu Alfiyah A',
            'is_active' => true,
        ]);

        $this->mapelB = Mapel::create([
            'kelas_id' => $this->kelasB->id,
            'code' => 'MPL-TCH-B',
            'name' => 'Nahwu Alfiyah B',
            'is_active' => true,
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-15',
            'end_date' => '2024-12-20',
            'is_active' => true,
        ]);

        $this->service = app(TeachingAssignmentService::class);
    }

    public function test_teaching_assignment_index_renders_for_authorized_users(): void
    {
        $response = $this->actingAs($this->admin)->get(route('teaching-assignment.index'));
        $response->assertStatus(200);
        $response->assertSee('Penugasan Pengajar Mata Pelajaran', false);

        $response = $this->actingAs($this->teacher1)->get(route('teaching-assignment.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->santriUser)->get(route('teaching-assignment.index'));
        $response->assertStatus(403);
    }

    public function test_teaching_assignment_datatable_ajax_returns_json(): void
    {
        $this->service->assign(
            kelas: $this->kelasA,
            mapel: $this->mapelA,
            teacher: $this->teacher1,
            academicYear: $this->academicYear
        );

        $response = $this->actingAs($this->admin)
            ->getJson(route('teaching-assignment.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'recordsTotal']);
        $this->assertStringContainsString('Ustadz Zaid', $response->getContent());
    }

    public function test_service_assigns_teaching_successfully(): void
    {
        $assignment = $this->service->assign(
            kelas: $this->kelasA,
            mapel: $this->mapelA,
            teacher: $this->teacher1,
            academicYear: $this->academicYear,
            notes: 'Pengampu reguler'
        );

        $this->assertInstanceOf(TeachingAssignment::class, $assignment);
        $this->assertDatabaseHas('teaching_assignments', [
            'kelas_id' => $this->kelasA->id,
            'mapel_id' => $this->mapelA->id,
            'user_id' => $this->teacher1->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => 'Aktif',
            'notes' => 'Pengampu reguler',
        ]);
    }

    public function test_service_rejects_subject_not_belonging_to_class(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('tidak terdaftar pada kelas');

        // Assigning mapelB (belongs to kelasB) to kelasA
        $this->service->assign(
            kelas: $this->kelasA,
            mapel: $this->mapelB,
            teacher: $this->teacher1,
            academicYear: $this->academicYear
        );
    }

    public function test_service_rejects_duplicate_subject_in_same_class_and_year(): void
    {
        $this->service->assign(
            kelas: $this->kelasA,
            mapel: $this->mapelA,
            teacher: $this->teacher1,
            academicYear: $this->academicYear
        );

        $this->expectException(DomainException::class);
        // Attempting to assign teacher2 to the exact same subject in classA and year
        $this->service->assign(
            kelas: $this->kelasA,
            mapel: $this->mapelA,
            teacher: $this->teacher2,
            academicYear: $this->academicYear
        );
    }

    public function test_one_teacher_can_teach_in_multiple_classes_in_same_year(): void
    {
        // Teacher 1 teaches in Kelas A
        $assignA = $this->service->assign(
            kelas: $this->kelasA,
            mapel: $this->mapelA,
            teacher: $this->teacher1,
            academicYear: $this->academicYear
        );

        // Teacher 1 ALSO teaches in Kelas B in the same academic year
        $assignB = $this->service->assign(
            kelas: $this->kelasB,
            mapel: $this->mapelB,
            teacher: $this->teacher1,
            academicYear: $this->academicYear
        );

        $this->assertEquals($this->teacher1->id, $assignA->user_id);
        $this->assertEquals($this->teacher1->id, $assignB->user_id);
        $this->assertDatabaseCount('teaching_assignments', 2);
    }

    public function test_service_deactivates_and_reactivates_assignment(): void
    {
        $assignment = $this->service->assign(
            kelas: $this->kelasA,
            mapel: $this->mapelA,
            teacher: $this->teacher1,
            academicYear: $this->academicYear
        );

        $deactivated = $this->service->deactivate($assignment, 'Cuti mengajar');
        $this->assertEquals(TeachingAssignment::STATUS_NONAKTIF, $deactivated->status);
        $this->assertEquals('Cuti mengajar', $deactivated->notes);

        $reactivated = $this->service->reactivate($assignment, 'Aktif kembali');
        $this->assertEquals(TeachingAssignment::STATUS_AKTIF, $reactivated->status);
    }

    public function test_controller_destroy_deactivates_assignment_without_hard_delete(): void
    {
        $assignment = $this->service->assign(
            kelas: $this->kelasA,
            mapel: $this->mapelA,
            teacher: $this->teacher1,
            academicYear: $this->academicYear
        );

        $response = $this->actingAs($this->admin)->delete(route('teaching-assignment.destroy', $assignment->id));
        $response->assertRedirect();

        // Must still exist in database, status Nonaktif
        $this->assertDatabaseHas('teaching_assignments', [
            'id' => $assignment->id,
            'status' => 'Nonaktif',
        ]);
    }

    public function test_kelas_cannot_be_deleted_when_teaching_assignment_exists(): void
    {
        $this->service->assign(
            kelas: $this->kelasA,
            mapel: $this->mapelA,
            teacher: $this->teacher1,
            academicYear: $this->academicYear
        );

        $response = $this->actingAs($this->admin)->delete(route('kelas.destroy', $this->kelasA->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('kelas', ['id' => $this->kelasA->id]);
    }

    public function test_academic_year_cannot_be_deleted_when_teaching_assignment_exists(): void
    {
        $this->service->assign(
            kelas: $this->kelasA,
            mapel: $this->mapelA,
            teacher: $this->teacher1,
            academicYear: $this->academicYear
        );

        $response = $this->actingAs($this->admin)->delete(route('academic-year.destroy', $this->academicYear->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('academic_years', ['id' => $this->academicYear->id]);
    }
}
