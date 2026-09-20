<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\User;
use App\Services\Academic\AcademicEnrollmentService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicEnrollmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected AcademicEnrollmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Akademik',
            'email' => 'admin.enrollment@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->service = app(AcademicEnrollmentService::class);
    }

    protected function createSantri(string $name = 'Santri Baru', string $noInduk = '20261001'): Santri
    {
        $user = User::create([
            'name' => $name,
            'email' => 'santri_'.strtolower($noInduk).'@example.com',
            'password' => Hash::make('Password123!'),
        ]);
        $user->assignRole('Santri');

        return Santri::create([
            'no_induk' => $noInduk,
            'user_id' => $user->id,
            'jenis_kelamin' => 'Laki-Laki',
            'nik' => '3578012345671001',
            'kk' => '3578012345671002',
            'whatsapp' => '081234567891',
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Kediri',
            'tahun_masuk' => '2026-07-01',
            'tahun_masuk_hijriyah' => '1447',
            'status' => 'Santri Aktif',
            'foto' => 'santri.png',
        ]);
    }

    public function test_enroll_creates_enrollment_record(): void
    {
        $santri = $this->createSantri();
        $kelas = Kelas::create(['kode' => 'KLS-7A', 'tingkatan' => 'Tsanawiyah', 'kelas' => '7-A']);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $enrollment = $this->service->enroll($santri, $year, $kelas, 'Penerimaan santri baru');

        $this->assertDatabaseHas('academic_enrollments', [
            'id' => $enrollment->id,
            'academic_year_id' => $year->id,
            'santri_id' => $santri->id,
            'kelas_id' => $kelas->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
        ]);
        $this->assertNotNull($enrollment->enrolled_at);
    }

    public function test_enroll_rejects_duplicate_enrollment_in_same_academic_year(): void
    {
        $santri = $this->createSantri();
        $kelas1 = Kelas::create(['kode' => 'KLS-7A', 'tingkatan' => 'Tsanawiyah', 'kelas' => '7-A']);
        $kelas2 = Kelas::create(['kode' => 'KLS-7B', 'tingkatan' => 'Tsanawiyah', 'kelas' => '7-B']);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $this->service->enroll($santri, $year, $kelas1);

        $this->expectException(DomainException::class);
        $this->service->enroll($santri, $year, $kelas2);
    }

    public function test_enroll_rejects_invalid_status(): void
    {
        $santri = $this->createSantri();
        $kelas = Kelas::create(['kode' => 'KLS-7A', 'tingkatan' => 'Tsanawiyah', 'kelas' => '7-A']);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->enroll($santri, $year, $kelas, status: 'InvalidStatus');
    }

    public function test_update_kelas_transfers_student(): void
    {
        $santri = $this->createSantri();
        $kelas1 = Kelas::create(['kode' => 'KLS-7A', 'tingkatan' => 'Tsanawiyah', 'kelas' => '7-A']);
        $kelas2 = Kelas::create(['kode' => 'KLS-7B', 'tingkatan' => 'Tsanawiyah', 'kelas' => '7-B']);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $enrollment = $this->service->enroll($santri, $year, $kelas1);
        $this->service->updateKelas($enrollment, $kelas2, 'Pindah ke kelas B');

        $this->assertEquals($kelas2->id, $enrollment->fresh()->kelas_id);
        $this->assertEquals('Pindah ke kelas B', $enrollment->fresh()->notes);
    }

    public function test_deactivate_preserves_history(): void
    {
        $santri = $this->createSantri();
        $kelas = Kelas::create(['kode' => 'KLS-7A', 'tingkatan' => 'Tsanawiyah', 'kelas' => '7-A']);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $enrollment = $this->service->enroll($santri, $year, $kelas);
        $this->service->deactivate($enrollment, 'Santri nonaktif sementara');

        $this->assertDatabaseHas('academic_enrollments', [
            'id' => $enrollment->id,
            'status' => AcademicEnrollment::STATUS_NONAKTIF,
        ]);
    }

    public function test_enrollment_controller_index_and_filters(): void
    {
        $santri = $this->createSantri();
        $kelas = Kelas::create(['kode' => 'KLS-7A', 'tingkatan' => 'Tsanawiyah', 'kelas' => '7-A']);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $this->service->enroll($santri, $year, $kelas);

        $response = $this->actingAs($this->admin)->get(route('academic-enrollment.index'));
        $response->assertStatus(200);
        $response->assertSee('Pendaftaran Akademik');

        $ajaxResponse = $this->actingAs($this->admin)->getJson(
            route('academic-enrollment.index', ['academic_year_id' => $year->id]),
            ['X-Requested-With' => 'XMLHttpRequest']
        );
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJsonStructure(['data']);
    }

    public function test_enrollment_controller_store_and_destroy_flow(): void
    {
        $santri = $this->createSantri();
        $kelas = Kelas::create(['kode' => 'KLS-7A', 'tingkatan' => 'Tsanawiyah', 'kelas' => '7-A']);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('academic-enrollment.store'), [
            'santri_id' => $santri->id,
            'academic_year_id' => $year->id,
            'kelas_id' => $kelas->id,
            'enrolled_at' => '2026-07-15',
            'notes' => 'Pendaftaran via UI',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('academic_enrollments', [
            'santri_id' => $santri->id,
            'academic_year_id' => $year->id,
            'kelas_id' => $kelas->id,
        ]);

        $enrollment = AcademicEnrollment::first();

        // Destroy deactivates rather than hard deleting
        $deleteResponse = $this->actingAs($this->admin)->delete(route('academic-enrollment.destroy', $enrollment));
        $deleteResponse->assertRedirect();

        $this->assertDatabaseHas('academic_enrollments', [
            'id' => $enrollment->id,
            'status' => AcademicEnrollment::STATUS_NONAKTIF,
        ]);
    }
}
