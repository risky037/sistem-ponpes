<?php

namespace Tests\Feature;

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\AssessmentDefinition;
use App\Models\AttendanceRecord;
use App\Models\Kelas;
use App\Models\KelasSantri;
use App\Models\Mapel;
use App\Models\Santri;
use App\Models\StudentAssessmentScore;
use App\Models\Tabungan;
use App\Models\TeachingAssignment;
use App\Models\TeachingSession;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolePortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_santri_portal_renders_personal_read_only_data_correctly(): void
    {
        $santriUser = User::factory()->create([
            'name' => 'Fatimah Az-Zahra',
            'email' => 'fatimah@example.com',
        ]);
        $santriUser->assignRole('Santri');

        $kelas = Kelas::factory()->create(['kelas' => '1A Wustho', 'kode' => 'KLS-1A']);
        $santri = Santri::factory()->create([
            'user_id' => $santriUser->id,
            'status' => 'Santri Aktif',
        ]);
        KelasSantri::create(['santri_id' => $santri->id, 'kelas_id' => $kelas->id]);

        $year = AcademicYear::factory()->create([
            'name' => '2024/2025',
            'semester' => 'Genap',
            'is_active' => true,
        ]);

        $enrollment = AcademicEnrollment::create([
            'academic_year_id' => $year->id,
            'santri_id' => $santri->id,
            'kelas_id' => $kelas->id,
            'status' => 'Aktif',
            'enrolled_at' => now(),
        ]);

        // Secret tabungan data to verify boundary
        Tabungan::create([
            'santri_id' => $santri->id,
            'saldo' => 9999999,
        ]);

        $mapel = Mapel::create([
            'kelas_id' => $kelas->id,
            'code' => 'MPL-FIQ',
            'name' => 'Fiqih Dasar',
            'is_active' => true,
        ]);

        $guruUser = User::factory()->create(['name' => 'Ust. Fauzi']);
        $guruUser->assignRole('Guru');

        $assignment = TeachingAssignment::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'user_id' => $guruUser->id,
            'academic_year_id' => $year->id,
            'status' => 'Aktif',
        ]);

        $session = TeachingSession::create([
            'teaching_assignment_id' => $assignment->id,
            'session_date' => now()->subDay()->format('Y-m-d'),
            'status' => 'Completed',
        ]);

        AttendanceRecord::create([
            'teaching_session_id' => $session->id,
            'academic_enrollment_id' => $enrollment->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'marked_at' => now(),
        ]);

        $definition = AssessmentDefinition::create([
            'academic_year_id' => $year->id,
            'name' => 'Tugas Harian 1',
            'type' => AssessmentDefinition::TYPE_TUGAS,
            'weight' => 20,
            'is_active' => true,
        ]);

        $component = AssessmentComponent::create([
            'teaching_assignment_id' => $assignment->id,
            'assessment_definition_id' => $definition->id,
            'weight' => 20,
        ]);

        StudentAssessmentScore::create([
            'assessment_component_id' => $component->id,
            'academic_enrollment_id' => $enrollment->id,
            'score' => 92.50,
            'notes' => 'Sangat baik',
        ]);

        $response = $this->actingAs($santriUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('pages.portal.santri');
        $response->assertSee('Portal Santri');
        $response->assertSee('Fatimah Az-Zahra');
        $response->assertSee('1A Wustho');
        $response->assertSee('Fiqih Dasar');
        $response->assertSee('92.50');
        $response->assertSee('Hadir');

        // Boundary assertions:
        // Must NOT see financial savings data
        $response->assertDontSee('9999999');
        $response->assertDontSee('Saldo Tabungan');
        // Must NOT have score editing forms
        $response->assertDontSee('<input type="number"', false);
        $response->assertDontSee('Simpan Nilai');
    }

    public function test_guru_portal_renders_teacher_schedule_and_assignments(): void
    {
        $guruUser = User::factory()->create([
            'name' => 'Ust. Ahmad Fauzi',
            'email' => 'guru.ahmad@example.com',
        ]);
        $guruUser->assignRole('Guru');

        $kelas = Kelas::factory()->create(['kelas' => '1A Wustho', 'kode' => 'KLS-1A']);
        $year = AcademicYear::factory()->create([
            'name' => '2024/2025',
            'semester' => 'Genap',
            'is_active' => true,
        ]);

        $mapel = Mapel::create([
            'kelas_id' => $kelas->id,
            'code' => 'MPL-FIQ',
            'name' => 'Fiqih Fathul Qorib',
            'is_active' => true,
        ]);

        TeachingAssignment::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'user_id' => $guruUser->id,
            'academic_year_id' => $year->id,
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($guruUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('pages.portal.guru');
        $response->assertSee('Portal Guru');
        $response->assertSee('Ust. Ahmad Fauzi');
        $response->assertSee('1A Wustho');
        $response->assertSee('Fiqih Fathul Qorib');
        $response->assertSee('Input Presensi');
        $response->assertSee('Input Nilai');
    }

    public function test_administrator_and_pengurus_receive_main_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertViewIs('pages.dashboard');

        $pengurus = User::factory()->create();
        $pengurus->assignRole('Pengurus');

        $response = $this->actingAs($pengurus)->get(route('dashboard'));
        $response->assertOk();
        $response->assertViewIs('pages.dashboard');
    }

    public function test_user_profile_update_allows_empty_password(): void
    {
        $originalHash = Hash::make('original_password');
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'password' => $originalHash,
        ]);

        $response = $this->actingAs($user)->post(route('profil.account', $user), [
            'name' => 'Modified Name',
            'email' => 'modified@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect();
        $user->refresh();

        $this->assertEquals('Modified Name', $user->name);
        $this->assertEquals('modified@example.com', $user->email);
        $this->assertTrue(Hash::check('original_password', $user->password));
    }

    public function test_admin_can_reset_any_user_password(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $targetUser = User::factory()->create([
            'password' => Hash::make('old_target_password'),
        ]);

        $response = $this->actingAs($admin)->patch(route('users.reset_password', $targetUser), [
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect();
        $targetUser->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $targetUser->password));
    }

    public function test_non_admin_cannot_reset_user_password(): void
    {
        $guru = User::factory()->create();
        $guru->assignRole('Guru');

        $targetUser = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $response = $this->actingAs($guru)->patch(route('users.reset_password', $targetUser), [
            'password' => 'HackedPassword123!',
            'password_confirmation' => 'HackedPassword123!',
        ]);

        $response->assertForbidden();
        $targetUser->refresh();
        $this->assertTrue(Hash::check('old_password', $targetUser->password));
    }

    public function test_demo_install_command_runs_successfully(): void
    {
        $exitCode = $this->artisan('demo:install', ['--santri' => 6])->run();

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseHas('users', ['email' => 'admin@pesantren.test']);
        $this->assertDatabaseHas('users', ['email' => 'guru.ahmad@pesantren.test']);
        $this->assertDatabaseHas('users', ['email' => 'santri.1@pesantren.test']);
        $this->assertGreaterThanOrEqual(6, Santri::count());
    }
}
