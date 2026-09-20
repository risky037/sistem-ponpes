<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\User;
use App\Models\WaliKelasAssignment;
use App\Services\Academic\WaliKelasAssignmentService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WaliKelasAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $santriUser;

    protected Kelas $kelas;

    protected AcademicYear $academicYear;

    protected WaliKelasAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        $santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Wali',
            'email' => 'admin.wali@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->pengurus = User::create([
            'name' => 'Ustadz Ahmad',
            'email' => 'ahmad@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->pengurus->assignRole($pengurusRole);

        $this->santriUser = User::create([
            'name' => 'Santri User',
            'email' => 'santri.wali@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->santriUser->assignRole($santriRole);

        $this->kelas = Kelas::create([
            'kode' => 'KLS-WAL01',
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

        $this->service = app(WaliKelasAssignmentService::class);
    }

    public function test_wali_kelas_index_renders_for_authorized_users(): void
    {
        $response = $this->actingAs($this->admin)->get(route('wali-kelas-assignment.index'));
        $response->assertStatus(200);
        $response->assertSee('Penugasan Wali Kelas', false);

        $response = $this->actingAs($this->pengurus)->get(route('wali-kelas-assignment.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->santriUser)->get(route('wali-kelas-assignment.index'));
        $response->assertStatus(403);
    }

    public function test_wali_kelas_datatable_ajax_returns_json(): void
    {
        $this->service->assign(
            kelas: $this->kelas,
            user: $this->pengurus,
            academicYear: $this->academicYear,
            notes: 'Ustadz pembimbing utama'
        );

        $response = $this->actingAs($this->admin)
            ->getJson(route('wali-kelas-assignment.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'recordsTotal']);
        $this->assertStringContainsString('Ustadz Ahmad', $response->getContent());
    }

    public function test_service_assigns_wali_kelas_successfully(): void
    {
        $assignment = $this->service->assign(
            kelas: $this->kelas,
            user: $this->pengurus,
            academicYear: $this->academicYear,
            notes: 'Catatan wali'
        );

        $this->assertInstanceOf(WaliKelasAssignment::class, $assignment);
        $this->assertDatabaseHas('wali_kelas_assignments', [
            'kelas_id' => $this->kelas->id,
            'user_id' => $this->pengurus->id,
            'academic_year_id' => $this->academicYear->id,
            'notes' => 'Catatan wali',
        ]);
    }

    public function test_service_rejects_duplicate_wali_kelas_for_same_class_and_year(): void
    {
        $this->service->assign(
            kelas: $this->kelas,
            user: $this->pengurus,
            academicYear: $this->academicYear
        );

        $anotherTeacher = User::create([
            'name' => 'Ustadz Ali',
            'email' => 'ali@example.com',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->expectException(DomainException::class);
        $this->service->assign(
            kelas: $this->kelas,
            user: $anotherTeacher,
            academicYear: $this->academicYear
        );
    }

    public function test_controller_store_redirects_with_success(): void
    {
        $response = $this->actingAs($this->admin)->post(route('wali-kelas-assignment.store'), [
            'kelas_id' => $this->kelas->id,
            'user_id' => $this->pengurus->id,
            'academic_year_id' => $this->academicYear->id,
            'notes' => 'Pengasuh kelas',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('wali_kelas_assignments', [
            'kelas_id' => $this->kelas->id,
            'user_id' => $this->pengurus->id,
        ]);
    }

    public function test_service_updates_assigned_teacher(): void
    {
        $assignment = $this->service->assign(
            kelas: $this->kelas,
            user: $this->pengurus,
            academicYear: $this->academicYear
        );

        $newTeacher = User::create([
            'name' => 'Ustadz Baru',
            'email' => 'ustadz.baru@example.com',
            'password' => Hash::make('Secret123!'),
        ]);

        $updated = $this->service->update($assignment, $newTeacher, 'Pengganti');

        $this->assertEquals($newTeacher->id, $updated->user_id);
        $this->assertEquals('Pengganti', $updated->notes);
    }

    public function test_service_deletes_assignment(): void
    {
        $assignment = $this->service->assign(
            kelas: $this->kelas,
            user: $this->pengurus,
            academicYear: $this->academicYear
        );

        $this->service->delete($assignment);

        $this->assertDatabaseMissing('wali_kelas_assignments', [
            'id' => $assignment->id,
        ]);
    }

    public function test_kelas_cannot_be_deleted_when_wali_kelas_assignment_exists(): void
    {
        $this->service->assign(
            kelas: $this->kelas,
            user: $this->pengurus,
            academicYear: $this->academicYear
        );

        $response = $this->actingAs($this->admin)->delete(route('kelas.destroy', $this->kelas->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('kelas', ['id' => $this->kelas->id]);
    }

    public function test_academic_year_cannot_be_deleted_when_wali_kelas_assignment_exists(): void
    {
        $this->service->assign(
            kelas: $this->kelas,
            user: $this->pengurus,
            academicYear: $this->academicYear
        );

        $response = $this->actingAs($this->admin)->delete(route('academic-year.destroy', $this->academicYear->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('academic_years', ['id' => $this->academicYear->id]);
    }
}
