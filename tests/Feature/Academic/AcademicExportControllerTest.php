<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicExportLog;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Santri;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AcademicExportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $santriUser;

    protected AcademicYear $academicYear;

    protected Kelas $kelas;

    protected Mapel $mapel;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions from config/permission.php
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin Controller',
            'email' => 'admin.ctrl@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole('Administrator');

        $this->pengurus = User::create([
            'name' => 'Pengurus Controller',
            'email' => 'pengurus.ctrl@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->pengurus->assignRole('Pengurus');

        $this->santriUser = User::create([
            'name' => 'Santri Controller',
            'email' => 'santri.ctrl@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->santriUser->assignRole('Santri');

        $this->academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'Ganjil',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
        ]);

        $this->kelas = Kelas::create([
            'kode' => 'KLS-7A',
            'tingkatan' => '7',
            'kelas' => 'A',
            'kapasitas' => 30,
        ]);

        $this->mapel = Mapel::create([
            'name' => 'Fiqih Ibadah',
            'code' => 'FQH-01',
            'kelas_id' => $this->kelas->id,
            'description' => 'Fiqih ibadah dasar',
        ]);
    }

    protected function createSantriWithEnrollment(string $nis, string $name): AcademicEnrollment
    {
        $user = User::create([
            'name' => $name,
            'email' => "santri.{$nis}@example.com",
            'password' => Hash::make('Secret123!'),
        ]);
        $user->assignRole('Santri');

        $santri = Santri::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'no_induk' => $nis,
            'nama_lengkap' => $name,
            'jenis_kelamin' => 'Laki-Laki',
            'nik' => '357801234567'.str_pad($nis, 4, '0', STR_PAD_LEFT),
            'kk' => '3578012345670000',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Kediri',
            'tahun_masuk' => '2025-07-01',
            'tahun_masuk_hijriyah' => '1447',
            'status' => 'Santri Aktif',
            'foto' => 'santri.png',
        ]);

        return AcademicEnrollment::create([
            'academic_year_id' => $this->academicYear->id,
            'santri_id' => $santri->id,
            'kelas_id' => $this->kelas->id,
            'status' => 'Aktif',
            'enrolled_at' => now(),
        ]);
    }

    public function test_admin_and_pengurus_can_access_administration_index(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(route('academic.administration.index'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertViewIs('pages.academic.administration.index');
        $responseAdmin->assertViewHas('stats');

        $responsePengurus = $this->actingAs($this->pengurus)->get(route('academic.administration.index'));
        $responsePengurus->assertStatus(200);
    }

    public function test_santri_cannot_access_administration_index(): void
    {
        $response = $this->actingAs($this->santriUser)->get(route('academic.administration.index'));
        $response->assertStatus(403);
    }

    public function test_guest_redirected_to_login(): void
    {
        $response = $this->get(route('academic.administration.index'));
        $response->assertRedirect('/login');

        $responseExport = $this->get(route('academic.export.index'));
        $responseExport->assertRedirect('/login');
    }

    public function test_admin_can_access_export_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('academic.export.index'));
        $response->assertStatus(200);
        $response->assertViewIs('pages.academic.export.index');
        $response->assertViewHas(['academicYears', 'kelasList', 'mapels', 'teachers', 'logs']);
    }

    public function test_export_enrollment_excel_and_print_views(): void
    {
        $this->createSantriWithEnrollment('2001', 'Faisal Akbar');

        // Test Print view
        $responsePrint = $this->actingAs($this->admin)->post(route('academic.export.enrollment'), [
            'format' => 'print',
            'academic_year_id' => $this->academicYear->id,
            'kelas_id' => $this->kelas->id,
        ]);
        $responsePrint->assertStatus(200);
        $responsePrint->assertViewIs('pages.academic.export.print_enrollment');
        $responsePrint->assertSee('PONDOK PESANTREN FATIMAH AZ-ZAHRA');
        $responsePrint->assertSee('Faisal Akbar');

        // Test Excel download
        $responseXlsx = $this->actingAs($this->admin)->post(route('academic.export.enrollment'), [
            'format' => 'xlsx',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $responseXlsx->assertStatus(200);
        $this->assertStringContainsString('attachment;', (string) $responseXlsx->headers->get('content-disposition'));

        // Assert audit log created
        $this->assertDatabaseHas('academic_export_logs', [
            'user_id' => $this->admin->id,
            'export_type' => AcademicExportLog::TYPE_ENROLLMENT,
            'format' => 'xlsx',
        ]);
    }

    public function test_export_teaching_assignment_excel_and_print(): void
    {
        $teacher = User::create([
            'name' => 'Guru Fiqih',
            'email' => 'fiqih.teacher@example.com',
            'password' => Hash::make('Secret123!'),
        ]);

        TeachingAssignment::create([
            'academic_year_id' => $this->academicYear->id,
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $this->mapel->id,
            'user_id' => $teacher->id,
            'status' => 'Aktif',
        ]);

        // Print view
        $responsePrint = $this->actingAs($this->admin)->post(route('academic.export.teaching-assignment'), [
            'format' => 'print',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $responsePrint->assertStatus(200);
        $responsePrint->assertViewIs('pages.academic.export.print_teaching_assignment');
        $responsePrint->assertSee('Fiqih Ibadah');

        // Excel download
        $responseXlsx = $this->actingAs($this->admin)->post(route('academic.export.teaching-assignment'), [
            'format' => 'xlsx',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $responseXlsx->assertStatus(200);
    }

    public function test_export_attendance_excel_and_print(): void
    {
        $this->createSantriWithEnrollment('2002', 'Galih Pratama');

        $responsePrint = $this->actingAs($this->admin)->post(route('academic.export.attendance'), [
            'format' => 'print',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $responsePrint->assertStatus(200);
        $responsePrint->assertViewIs('pages.academic.export.print_attendance_summary');
        $responsePrint->assertSee('Galih Pratama');

        $responseXlsx = $this->actingAs($this->admin)->post(route('academic.export.attendance'), [
            'format' => 'xlsx',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $responseXlsx->assertStatus(200);
    }

    public function test_export_assessment_excel_and_print(): void
    {
        $responsePrint = $this->actingAs($this->admin)->post(route('academic.export.assessment'), [
            'format' => 'print',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $responsePrint->assertStatus(200);
        $responsePrint->assertViewIs('pages.academic.export.print_assessment_summary');

        $responseXlsx = $this->actingAs($this->admin)->post(route('academic.export.assessment'), [
            'format' => 'xlsx',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $responseXlsx->assertStatus(200);
    }

    public function test_export_performance_excel_and_print(): void
    {
        $responsePrint = $this->actingAs($this->admin)->post(route('academic.export.performance'), [
            'format' => 'print',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $responsePrint->assertStatus(200);
        $responsePrint->assertViewIs('pages.academic.export.print_performance');

        $responseXlsx = $this->actingAs($this->admin)->post(route('academic.export.performance'), [
            'format' => 'xlsx',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $responseXlsx->assertStatus(200);
    }

    public function test_santri_forbidden_from_all_export_routes(): void
    {
        $routes = [
            'academic.export.index' => 'get',
            'academic.export.enrollment' => 'post',
            'academic.export.teaching-assignment' => 'post',
            'academic.export.attendance' => 'post',
            'academic.export.assessment' => 'post',
            'academic.export.performance' => 'post',
        ];

        foreach ($routes as $routeName => $method) {
            $response = $this->actingAs($this->santriUser)->$method(route($routeName), ['format' => 'print']);
            $response->assertStatus(403);
        }
    }

    public function test_invalid_format_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(route('academic.export.enrollment'), [
            'format' => 'unsupported_format',
        ]);
        $response->assertSessionHasErrors('format');
    }
}
