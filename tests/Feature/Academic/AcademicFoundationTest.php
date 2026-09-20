<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\StudentBatch;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Akademik',
            'email' => 'admin.akademik@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->pengurus = User::create([
            'name' => 'Pengurus Akademik',
            'email' => 'pengurus.akademik@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->pengurus->assignRole($pengurusRole);
    }

    protected function createSantri(string $name = 'Santri Akademik', string $noInduk = '20260001'): Santri
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
            'nik' => '3578012345670001',
            'kk' => '3578012345670002',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Kediri',
            'tahun_masuk' => '2026-07-01',
            'tahun_masuk_hijriyah' => '1447',
            'status' => 'Santri Aktif',
            'maksimal_perizinan' => 10,
            'foto' => 'santri.png',
        ]);
    }

    public function test_academic_year_index_renders_and_returns_datatable_ajax(): void
    {
        AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('academic-year.index'));
        $response->assertStatus(200);
        $response->assertSee('Tahun Ajaran');

        $ajaxResponse = $this->actingAs($this->admin)->getJson(route('academic-year.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJsonStructure(['data']);
        $ajaxResponse->assertJsonFragment(['name' => '2026/2027']);
    }

    public function test_academic_year_create_page_renders(): void
    {
        $response = $this->actingAs($this->admin)->get(route('academic-year.create'));
        $response->assertStatus(200);
    }

    public function test_academic_year_can_be_stored(): void
    {
        $payload = [
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->admin)->post(route('academic-year.store'), $payload);
        $response->assertRedirect(route('academic-year.index'));

        $this->assertDatabaseHas('academic_years', [
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => 1,
        ]);
    }

    public function test_academic_year_edit_page_renders(): void
    {
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('academic-year.edit', $year));
        $response->assertStatus(200);
        $response->assertSee('2026/2027');
    }

    public function test_academic_year_can_be_updated(): void
    {
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('academic-year.update', $year), [
            'name' => '2026/2027',
            'semester' => 'Genap',
            'start_date' => '2027-01-05',
            'end_date' => '2027-06-20',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('academic-year.index'));

        $this->assertDatabaseHas('academic_years', [
            'id' => $year->id,
            'semester' => 'Genap',
            'is_active' => 1,
        ]);
    }

    public function test_academic_year_can_be_deleted(): void
    {
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('academic-year.destroy', $year));
        $response->assertRedirect(route('academic-year.index'));

        $this->assertDatabaseMissing('academic_years', [
            'id' => $year->id,
        ]);
    }

    public function test_activating_academic_year_deactivates_other_active_years(): void
    {
        $year1 = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'Genap',
            'start_date' => '2026-01-05',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post(route('academic-year.store'), [
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => 1,
        ]);

        $this->assertFalse($year1->fresh()->is_active);
        $this->assertDatabaseHas('academic_years', [
            'name' => '2026/2027',
            'is_active' => 1,
        ]);
    }

    public function test_academic_enrollment_creation_and_relationships(): void
    {
        $santri = $this->createSantri();
        $kelas = Kelas::create([
            'kode' => 'KLS-7A',
            'tingkatan' => 'Tsanawiyah',
            'kelas' => '7-A',
        ]);
        $academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $enrollment = AcademicEnrollment::create([
            'academic_year_id' => $academicYear->id,
            'santri_id' => $santri->id,
            'kelas_id' => $kelas->id,
            'status' => 'Aktif',
            'enrolled_at' => '2026-07-15',
            'notes' => 'Penerimaan santri baru',
        ]);

        // Assert relationships
        $this->assertEquals($academicYear->id, $enrollment->academic_year->id);
        $this->assertEquals($santri->id, $enrollment->santri->id);
        $this->assertEquals($kelas->id, $enrollment->kelas->id);

        $this->assertTrue($santri->academic_enrollments->contains($enrollment));
        $this->assertTrue($kelas->academic_enrollments->contains($enrollment));
        $this->assertTrue($academicYear->academic_enrollments->contains($enrollment));
    }

    public function test_student_batch_relationship_with_santri(): void
    {
        $batch = StudentBatch::create([
            'name' => 'Angkatan 2026',
            'year' => '2026',
            'description' => 'Angkatan Masuk 2026',
        ]);

        $santri = $this->createSantri();
        $santri->student_batch_id = $batch->id;
        $santri->save();

        $this->assertEquals('Angkatan 2026', $santri->fresh()->student_batch->name);
        $this->assertTrue($batch->santris->contains($santri));
    }

    public function test_unique_enrollment_constraint_rejects_duplicate_santri_in_same_academic_year(): void
    {
        $santri = $this->createSantri();
        $kelas1 = Kelas::create([
            'kode' => 'KLS-7A',
            'tingkatan' => 'Tsanawiyah',
            'kelas' => '7-A',
        ]);
        $kelas2 = Kelas::create([
            'kode' => 'KLS-7B',
            'tingkatan' => 'Tsanawiyah',
            'kelas' => '7-B',
        ]);
        $academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        AcademicEnrollment::create([
            'academic_year_id' => $academicYear->id,
            'santri_id' => $santri->id,
            'kelas_id' => $kelas1->id,
            'status' => 'Aktif',
        ]);

        $this->expectException(QueryException::class);

        AcademicEnrollment::create([
            'academic_year_id' => $academicYear->id,
            'santri_id' => $santri->id,
            'kelas_id' => $kelas2->id,
            'status' => 'Aktif',
        ]);
    }
}
