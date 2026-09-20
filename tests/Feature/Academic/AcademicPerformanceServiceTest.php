<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicPerformanceSummary;
use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\AssessmentDefinition;
use App\Models\AttendanceRecord;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Santri;
use App\Models\StudentAssessmentScore;
use App\Models\TeachingAssignment;
use App\Models\TeachingSession;
use App\Models\User;
use App\Services\Academic\AcademicPerformanceService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicPerformanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicPerformanceService $service;

    protected User $admin;

    protected User $pengurus;

    protected User $santriUser;

    protected Kelas $kelas;

    protected Mapel $mapel;

    protected AcademicYear $academicYear;

    protected TeachingAssignment $teachingAssignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AcademicPerformanceService;

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        $santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Performa',
            'email' => 'admin.perf@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->pengurus = User::create([
            'name' => 'Pengurus Performa',
            'email' => 'pengurus.perf@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->pengurus->assignRole($pengurusRole);

        $this->santriUser = User::create([
            'name' => 'Santri Performa',
            'email' => 'santri.perf@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->santriUser->assignRole($santriRole);

        $this->kelas = Kelas::create([
            'kode' => 'KLS-10A',
            'tingkatan' => '10',
            'kelas' => 'X-A',
        ]);

        $this->mapel = Mapel::create([
            'kelas_id' => $this->kelas->id,
            'name' => 'Fiqih Ibadah',
            'code' => 'FIQ-101',
            'is_active' => true,
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->teachingAssignment = TeachingAssignment::create([
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $this->mapel->id,
            'user_id' => $this->admin->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => TeachingAssignment::STATUS_AKTIF,
        ]);
    }

    protected function createSantriWithEnrollment(string $name, string $email, string $noInduk, string $status = AcademicEnrollment::STATUS_AKTIF): AcademicEnrollment
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('Secret123!'),
        ]);

        $santri = Santri::create([
            'user_id' => $user->id,
            'nama_lengkap' => $name,
            'no_induk' => $noInduk,
            'nis' => $noInduk,
            'nisn' => '00'.$noInduk,
            'jenis_kelamin' => 'Laki-laki',
            'whatsapp' => '0812'.substr(preg_replace('/\D/', '', $noInduk).'12345678', 0, 8),
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Kediri',
            'status' => 'Santri Aktif',
            'tanggal_masuk' => '2026-07-01',
        ]);

        return AcademicEnrollment::create([
            'academic_year_id' => $this->academicYear->id,
            'santri_id' => $santri->id,
            'kelas_id' => $this->kelas->id,
            'status' => $status,
            'enrolled_at' => '2026-07-01',
        ]);
    }

    public function test_empty_summary_generates_with_kosong_status(): void
    {
        $enrollment = $this->createSantriWithEnrollment('Santri Kosong', 'kosong@example.com', 'NIS001');

        $summary = $this->service->generateSummary($enrollment);

        $this->assertInstanceOf(AcademicPerformanceSummary::class, $summary);
        $this->assertEquals(AcademicPerformanceSummary::STATUS_KOSONG, $summary->computation_status);
        $this->assertEquals(0, $summary->total_sessions);
        $this->assertEquals(0, $summary->scored_components);
        $this->assertNull($summary->attendance_rate);
        $this->assertNull($summary->average_score);
        $this->assertEquals(AcademicPerformanceSummary::CURRENT_SOURCE_VERSION, $summary->source_version);
    }

    public function test_attendance_calculation(): void
    {
        $enrollment = $this->createSantriWithEnrollment('Santri Presensi', 'presensi@example.com', 'NIS002');

        // Create 4 sessions and attendance records: 2 Hadir, 1 Izin, 1 Sakit
        $session1 = TeachingSession::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'session_date' => '2026-08-01',
            'status' => 'Completed',
        ]);
        $session2 = TeachingSession::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'session_date' => '2026-08-08',
            'status' => 'Completed',
        ]);
        $session3 = TeachingSession::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'session_date' => '2026-08-15',
            'status' => 'Completed',
        ]);
        $session4 = TeachingSession::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'session_date' => '2026-08-22',
            'status' => 'Completed',
        ]);

        AttendanceRecord::create([
            'teaching_session_id' => $session1->id,
            'academic_enrollment_id' => $enrollment->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'marked_at' => now(),
        ]);
        AttendanceRecord::create([
            'teaching_session_id' => $session2->id,
            'academic_enrollment_id' => $enrollment->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'marked_at' => now(),
        ]);
        AttendanceRecord::create([
            'teaching_session_id' => $session3->id,
            'academic_enrollment_id' => $enrollment->id,
            'status' => AttendanceRecord::STATUS_IZIN,
            'marked_at' => now(),
        ]);
        AttendanceRecord::create([
            'teaching_session_id' => $session4->id,
            'academic_enrollment_id' => $enrollment->id,
            'status' => AttendanceRecord::STATUS_SAKIT,
            'marked_at' => now(),
        ]);

        $summary = $this->service->generateSummary($enrollment);

        $this->assertEquals(4, $summary->total_sessions);
        $this->assertEquals(2, $summary->present_count);
        $this->assertEquals(1, $summary->excused_count);
        $this->assertEquals(1, $summary->sick_count);
        $this->assertEquals(0, $summary->absent_count);
        $this->assertEquals('50.00', (string) $summary->attendance_rate);
    }

    public function test_weighted_score_calculation(): void
    {
        $enrollment = $this->createSantriWithEnrollment('Santri Nilai', 'nilai@example.com', 'NIS003');

        $def1 = AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Tugas 1',
            'type' => 'Tugas',
            'weight' => 40,
            'is_active' => true,
        ]);
        $def2 = AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Ujian Akhir',
            'type' => 'Ujian',
            'weight' => 60,
            'is_active' => true,
        ]);

        $comp1 = AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $def1->id,
            'weight' => 40,
        ]);
        $comp2 = AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $def2->id,
            'weight' => 60,
        ]);

        // Score 1: 80.00 (weight 40) => 3200
        // Score 2: 90.00 (weight 60) => 5400
        // Total sum = 8600, total weight = 100 => avg 86.00
        StudentAssessmentScore::create([
            'assessment_component_id' => $comp1->id,
            'academic_enrollment_id' => $enrollment->id,
            'score' => 80.00,
            'graded_by' => $this->admin->id,
            'graded_at' => now(),
        ]);
        StudentAssessmentScore::create([
            'assessment_component_id' => $comp2->id,
            'academic_enrollment_id' => $enrollment->id,
            'score' => 90.00,
            'graded_by' => $this->admin->id,
            'graded_at' => now(),
        ]);

        $summary = $this->service->generateSummary($enrollment);

        $this->assertEquals(2, $summary->scored_components);
        $this->assertEquals(2, $summary->total_components);
        $this->assertEquals('8600.00', (string) $summary->weighted_score_sum);
        $this->assertEquals(100, $summary->total_weight);
        $this->assertEquals('86.00', (string) $summary->average_score);
    }

    public function test_complete_status_when_all_components_scored_and_attendance_sufficient(): void
    {
        $enrollment = $this->createSantriWithEnrollment('Santri Lengkap', 'lengkap@example.com', 'NIS004');

        // 4 sessions, all Hadir => 100% attendance
        for ($i = 1; $i <= 4; $i++) {
            $session = TeachingSession::create([
                'teaching_assignment_id' => $this->teachingAssignment->id,
                'session_date' => '2026-08-0'.$i,
                'status' => 'Completed',
            ]);
            AttendanceRecord::create([
                'teaching_session_id' => $session->id,
                'academic_enrollment_id' => $enrollment->id,
                'status' => AttendanceRecord::STATUS_HADIR,
                'marked_at' => now(),
            ]);
        }

        // 1 component defined and scored
        $def = AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Tugas Mandiri',
            'type' => 'Tugas',
            'weight' => 100,
            'is_active' => true,
        ]);
        $comp = AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $def->id,
            'weight' => 100,
        ]);
        StudentAssessmentScore::create([
            'assessment_component_id' => $comp->id,
            'academic_enrollment_id' => $enrollment->id,
            'score' => 95.00,
            'graded_by' => $this->admin->id,
            'graded_at' => now(),
        ]);

        $summary = $this->service->generateSummary($enrollment);

        $this->assertEquals(AcademicPerformanceSummary::STATUS_LENGKAP, $summary->computation_status);
        $this->assertEquals('100.00', (string) $summary->attendance_rate);
        $this->assertEquals('95.00', (string) $summary->average_score);
    }

    public function test_partial_status_when_attendance_or_scores_incomplete(): void
    {
        $enrollment = $this->createSantriWithEnrollment('Santri Sebagian', 'sebagian@example.com', 'NIS005');

        // 4 sessions: 2 Hadir, 2 Alpha => 50% attendance (< 75%)
        for ($i = 1; $i <= 4; $i++) {
            $session = TeachingSession::create([
                'teaching_assignment_id' => $this->teachingAssignment->id,
                'session_date' => '2026-08-1'.$i,
                'status' => 'Completed',
            ]);
            AttendanceRecord::create([
                'teaching_session_id' => $session->id,
                'academic_enrollment_id' => $enrollment->id,
                'status' => $i <= 2 ? AttendanceRecord::STATUS_HADIR : AttendanceRecord::STATUS_ALPHA,
                'marked_at' => now(),
            ]);
        }

        $def = AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Tugas Harian',
            'type' => 'Tugas',
            'weight' => 100,
            'is_active' => true,
        ]);
        $comp = AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $def->id,
            'weight' => 100,
        ]);
        StudentAssessmentScore::create([
            'assessment_component_id' => $comp->id,
            'academic_enrollment_id' => $enrollment->id,
            'score' => 90.00,
            'graded_by' => $this->admin->id,
            'graded_at' => now(),
        ]);

        $summary = $this->service->generateSummary($enrollment);

        // Attendance is 50%, below 75% threshold => Sebagian
        $this->assertEquals(AcademicPerformanceSummary::STATUS_SEBAGIAN, $summary->computation_status);
    }

    public function test_zero_division_protection(): void
    {
        $enrollment = $this->createSantriWithEnrollment('Santri Zero Div', 'zerodiv@example.com', 'NIS006');

        $summary = $this->service->generateSummary($enrollment);

        $this->assertNull($summary->attendance_rate);
        $this->assertNull($summary->average_score);
        $this->assertNull($summary->weighted_score_sum);
        $this->assertEquals(0, $summary->total_sessions);
        $this->assertEquals(0, $summary->total_weight);
        $this->assertEquals(AcademicPerformanceSummary::STATUS_KOSONG, $summary->computation_status);
    }

    public function test_idempotent_regeneration(): void
    {
        $enrollment = $this->createSantriWithEnrollment('Santri Idempotent', 'idempotent@example.com', 'NIS007');

        $session = TeachingSession::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'session_date' => '2026-08-01',
            'status' => 'Completed',
        ]);
        AttendanceRecord::create([
            'teaching_session_id' => $session->id,
            'academic_enrollment_id' => $enrollment->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'marked_at' => now(),
        ]);

        // First generation
        $summary1 = $this->service->generateSummary($enrollment);
        $this->assertEquals(1, AcademicPerformanceSummary::where('academic_enrollment_id', $enrollment->id)->count());
        $this->assertEquals(1, $summary1->total_sessions);

        // Add another record
        $session2 = TeachingSession::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'session_date' => '2026-08-08',
            'status' => 'Completed',
        ]);
        AttendanceRecord::create([
            'teaching_session_id' => $session2->id,
            'academic_enrollment_id' => $enrollment->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'marked_at' => now(),
        ]);

        // Second generation (regenerate)
        $summary2 = $this->service->generateSummary($enrollment);

        $this->assertEquals(1, AcademicPerformanceSummary::where('academic_enrollment_id', $enrollment->id)->count());
        $this->assertEquals($summary1->id, $summary2->id);
        $this->assertEquals(2, $summary2->total_sessions);
    }

    public function test_bulk_generation_for_academic_year(): void
    {
        $enrollment1 = $this->createSantriWithEnrollment('Santri Bulk 1', 'bulk1@example.com', 'NIS008');
        $enrollment2 = $this->createSantriWithEnrollment('Santri Bulk 2', 'bulk2@example.com', 'NIS009');

        $summaries = $this->service->generateForYear($this->academicYear);

        $this->assertCount(2, $summaries);
        $this->assertEquals(2, AcademicPerformanceSummary::count());
    }

    public function test_inactive_enrollment_skipped_during_bulk_generation(): void
    {
        $activeEnrollment = $this->createSantriWithEnrollment('Santri Aktif', 'aktif@example.com', 'NIS010', AcademicEnrollment::STATUS_AKTIF);
        $inactiveEnrollment = $this->createSantriWithEnrollment('Santri Nonaktif', 'nonaktif@example.com', 'NIS011', AcademicEnrollment::STATUS_NONAKTIF);
        $graduatedEnrollment = $this->createSantriWithEnrollment('Santri Lulus', 'lulus@example.com', 'NIS012', AcademicEnrollment::STATUS_LULUS);

        $summaries = $this->service->generateForYear($this->academicYear);

        $this->assertCount(1, $summaries);
        $this->assertEquals($activeEnrollment->id, $summaries->first()->academic_enrollment_id);
        $this->assertFalse(AcademicPerformanceSummary::where('academic_enrollment_id', $inactiveEnrollment->id)->exists());
        $this->assertFalse(AcademicPerformanceSummary::where('academic_enrollment_id', $graduatedEnrollment->id)->exists());
    }

    public function test_enrollment_anchor_validation(): void
    {
        $invalidEnrollment = new AcademicEnrollment;

        $this->expectException(DomainException::class);
        $this->service->generateSummary($invalidEnrollment);
    }

    public function test_administrator_can_access_performance_endpoints(): void
    {
        $enrollment = $this->createSantriWithEnrollment('Santri Admin Test', 'admintest@example.com', 'NIS013');

        $response = $this->actingAs($this->admin)->get(route('academic.performance.index'));
        $response->assertStatus(200);

        $responseShow = $this->actingAs($this->admin)->get(route('academic.performance.show', $enrollment->id));
        $responseShow->assertStatus(200);

        $responseGenerate = $this->actingAs($this->admin)->post(route('academic.performance.generate', $enrollment->id));
        $responseGenerate->assertRedirect();
        $this->assertDatabaseHas('academic_performance_summaries', [
            'academic_enrollment_id' => $enrollment->id,
        ]);
    }

    public function test_pengurus_can_access_performance_endpoints(): void
    {
        $enrollment = $this->createSantriWithEnrollment('Santri Pengurus Test', 'pengurustest@example.com', 'NIS014');

        $response = $this->actingAs($this->pengurus)->get(route('academic.performance.index'));
        $response->assertStatus(200);

        $responseShow = $this->actingAs($this->pengurus)->get(route('academic.performance.show', $enrollment->id));
        $responseShow->assertStatus(200);
    }

    public function test_santri_cannot_access_performance_endpoints(): void
    {
        $enrollment = $this->createSantriWithEnrollment('Santri Forbidden Test', 'forbiddentest@example.com', 'NIS015');

        $response = $this->actingAs($this->santriUser)->get(route('academic.performance.index'));
        $response->assertStatus(403);

        $responseShow = $this->actingAs($this->santriUser)->get(route('academic.performance.show', $enrollment->id));
        $responseShow->assertStatus(403);

        $responseGenerate = $this->actingAs($this->santriUser)->post(route('academic.performance.generate', $enrollment->id));
        $responseGenerate->assertStatus(403);
    }
}
