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
use App\Services\Academic\AcademicKpiService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AcademicKpiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicKpiService $kpiService;

    protected AcademicYear $academicYear;

    protected Kelas $kelasA;

    protected Kelas $kelasB;

    protected User $teacher;

    protected Mapel $mapel;

    protected TeachingAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->kpiService = app(AcademicKpiService::class);

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-01',
            'end_date' => '2024-12-31',
            'is_active' => true,
        ]);

        $this->kelasA = Kelas::create(['kode' => '7A', 'kelas' => 'A', 'tingkatan' => '7']);
        $this->kelasB = Kelas::create(['kode' => '7B', 'kelas' => 'B', 'tingkatan' => '7']);

        $this->teacher = User::create([
            'name' => 'Ustadz Ahmad',
            'email' => 'ahmad@example.com',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->mapel = Mapel::create([
            'kelas_id' => $this->kelasA->id,
            'code' => 'NHW-01',
            'name' => 'Nahwu',
            'is_active' => true,
        ]);

        $this->assignment = TeachingAssignment::create([
            'kelas_id' => $this->kelasA->id,
            'mapel_id' => $this->mapel->id,
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => TeachingAssignment::STATUS_AKTIF,
        ]);
    }

    protected function createSantri(string $name, string $email, string $noInduk): Santri
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('Secret123!'),
        ]);

        return Santri::create([
            'user_id' => $user->id,
            'no_induk' => $noInduk,
            'nis' => $noInduk,
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => 628123456789,
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Kediri',
            'status' => 'Santri Aktif',
        ]);
    }

    public function test_get_institutional_kpis_handles_empty_academic_year(): void
    {
        $kpis = $this->kpiService->getInstitutionalKpis($this->academicYear);

        $this->assertSame(0, $kpis['active_enrollments']);
        $this->assertSame(0, $kpis['total_classes']);
        $this->assertSame(1, $kpis['total_teaching_assignments']);
        $this->assertSame(0, $kpis['completed_sessions_count']);
        $this->assertSame(0, $kpis['planned_sessions_count']);
        $this->assertNull($kpis['session_fulfillment_rate']);
        $this->assertNull($kpis['attendance_rate']);
        $this->assertNull($kpis['evaluation_completion_rate']);
        $this->assertNull($kpis['average_score']);
    }

    public function test_get_institutional_kpis_calculates_active_metrics_correctly(): void
    {
        $s1 = $this->createSantri('Santri One', 's1@example.com', '1001');
        $enr1 = AcademicEnrollment::create([
            'academic_year_id' => $this->academicYear->id,
            'santri_id' => $s1->id,
            'kelas_id' => $this->kelasA->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => now(),
        ]);

        $s2 = $this->createSantri('Santri Two', 's2@example.com', '1002');
        $enr2 = AcademicEnrollment::create([
            'academic_year_id' => $this->academicYear->id,
            'santri_id' => $s2->id,
            'kelas_id' => $this->kelasA->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => now(),
        ]);

        AcademicPerformanceSummary::create([
            'academic_enrollment_id' => $enr1->id,
            'total_sessions' => 10,
            'present_count' => 9,
            'attendance_rate' => 90.0,
            'average_score' => 85.0,
            'computation_status' => AcademicPerformanceSummary::STATUS_LENGKAP,
        ]);

        AcademicPerformanceSummary::create([
            'academic_enrollment_id' => $enr2->id,
            'total_sessions' => 10,
            'present_count' => 7,
            'attendance_rate' => 70.0,
            'average_score' => 75.0,
            'computation_status' => AcademicPerformanceSummary::STATUS_SEBAGIAN,
        ]);

        $sess1 = TeachingSession::create([
            'teaching_assignment_id' => $this->assignment->id,
            'session_date' => '2024-08-01',
            'status' => TeachingSession::STATUS_COMPLETED,
        ]);
        $sess2 = TeachingSession::create([
            'teaching_assignment_id' => $this->assignment->id,
            'session_date' => '2024-08-08',
            'status' => TeachingSession::STATUS_COMPLETED,
        ]);
        TeachingSession::create([
            'teaching_assignment_id' => $this->assignment->id,
            'session_date' => '2024-08-15',
            'status' => TeachingSession::STATUS_PLANNED,
        ]);

        AttendanceRecord::create([
            'teaching_session_id' => $sess1->id,
            'academic_enrollment_id' => $enr1->id,
            'status' => AttendanceRecord::STATUS_HADIR,
        ]);
        AttendanceRecord::create([
            'teaching_session_id' => $sess1->id,
            'academic_enrollment_id' => $enr2->id,
            'status' => AttendanceRecord::STATUS_HADIR,
        ]);
        AttendanceRecord::create([
            'teaching_session_id' => $sess2->id,
            'academic_enrollment_id' => $enr1->id,
            'status' => AttendanceRecord::STATUS_HADIR,
        ]);
        AttendanceRecord::create([
            'teaching_session_id' => $sess2->id,
            'academic_enrollment_id' => $enr2->id,
            'status' => AttendanceRecord::STATUS_SAKIT,
        ]);

        $kpis = $this->kpiService->getInstitutionalKpis($this->academicYear);

        $this->assertSame(2, $kpis['active_enrollments']);
        $this->assertSame(1, $kpis['total_classes']);
        $this->assertSame(2, $kpis['completed_sessions_count']);
        $this->assertSame(1, $kpis['planned_sessions_count']);
        $this->assertEquals(66.67, $kpis['session_fulfillment_rate']);
        $this->assertEquals(75.0, $kpis['attendance_rate']);
        $this->assertEquals(50.0, $kpis['evaluation_completion_rate']);
        $this->assertEquals(80.0, $kpis['average_score']);
    }

    public function test_get_attendance_analytics_computes_breakdowns_and_at_risk(): void
    {
        $s1 = $this->createSantri('Santri Three', 's3@example.com', '1003');
        $enr = AcademicEnrollment::create([
            'academic_year_id' => $this->academicYear->id,
            'santri_id' => $s1->id,
            'kelas_id' => $this->kelasA->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => now(),
        ]);

        AcademicPerformanceSummary::create([
            'academic_enrollment_id' => $enr->id,
            'total_sessions' => 10,
            'present_count' => 6,
            'attendance_rate' => 60.0,
            'computation_status' => AcademicPerformanceSummary::STATUS_SEBAGIAN,
        ]);

        $sess = TeachingSession::create([
            'teaching_assignment_id' => $this->assignment->id,
            'session_date' => '2024-09-10',
            'status' => TeachingSession::STATUS_COMPLETED,
        ]);

        AttendanceRecord::create([
            'teaching_session_id' => $sess->id,
            'academic_enrollment_id' => $enr->id,
            'status' => AttendanceRecord::STATUS_HADIR,
        ]);

        $analytics = $this->kpiService->getAttendanceAnalytics($this->academicYear);

        $this->assertSame(1, $analytics['total_records']);
        $this->assertSame(1, $analytics['status_breakdown'][AttendanceRecord::STATUS_HADIR]['count']);
        $this->assertEquals(100.0, $analytics['status_breakdown'][AttendanceRecord::STATUS_HADIR]['percentage']);
        $this->assertCount(1, $analytics['monthly_trend']);
        $this->assertSame('2024-09', $analytics['monthly_trend'][0]['month']);
        $this->assertSame(1, $analytics['at_risk_attendance_count']);
    }

    public function test_get_grade_distribution_groups_scores_into_flexible_bands(): void
    {
        $s1 = $this->createSantri('S1', 's11@example.com', '1011');
        $enr1 = AcademicEnrollment::create([
            'academic_year_id' => $this->academicYear->id,
            'santri_id' => $s1->id,
            'kelas_id' => $this->kelasA->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => now(),
        ]);

        $s2 = $this->createSantri('S2', 's12@example.com', '1012');
        $enr2 = AcademicEnrollment::create([
            'academic_year_id' => $this->academicYear->id,
            'santri_id' => $s2->id,
            'kelas_id' => $this->kelasA->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => now(),
        ]);

        AcademicPerformanceSummary::create([
            'academic_enrollment_id' => $enr1->id,
            'average_score' => 95.0,
            'computation_status' => AcademicPerformanceSummary::STATUS_LENGKAP,
        ]);

        AcademicPerformanceSummary::create([
            'academic_enrollment_id' => $enr2->id,
            'average_score' => 65.0,
            'computation_status' => AcademicPerformanceSummary::STATUS_SEBAGIAN,
        ]);

        $dist = $this->kpiService->getGradeDistribution($this->academicYear);

        $this->assertSame(2, $dist['total_scored_students']);
        $this->assertSame(1, $dist['bands']['band_a']['count']);
        $this->assertSame(1, $dist['bands']['band_d']['count']);
        $this->assertSame(0, $dist['bands']['band_b']['count']);
        $this->assertEquals(80.0, $dist['average_score']);
        $this->assertSame(1, $dist['completion_status_breakdown'][AcademicPerformanceSummary::STATUS_LENGKAP]['count']);
        $this->assertSame(1, $dist['completion_status_breakdown'][AcademicPerformanceSummary::STATUS_SEBAGIAN]['count']);
    }

    public function test_get_subject_indicators_calculates_metrics_per_mapel(): void
    {
        $def = AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Tugas 1',
            'type' => AssessmentDefinition::TYPE_TUGAS,
            'weight' => 20,
            'is_active' => true,
        ]);

        $comp = AssessmentComponent::create([
            'teaching_assignment_id' => $this->assignment->id,
            'assessment_definition_id' => $def->id,
            'weight' => 20,
        ]);

        $s1 = $this->createSantri('S1', 's21@example.com', '1021');
        $enr = AcademicEnrollment::create([
            'academic_year_id' => $this->academicYear->id,
            'santri_id' => $s1->id,
            'kelas_id' => $this->kelasA->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => now(),
        ]);

        StudentAssessmentScore::create([
            'assessment_component_id' => $comp->id,
            'academic_enrollment_id' => $enr->id,
            'score' => 88.0,
            'graded_by' => $this->teacher->id,
            'graded_at' => now(),
        ]);

        $indicators = $this->kpiService->getSubjectIndicators($this->academicYear);

        $this->assertCount(1, $indicators);
        $first = $indicators->first();
        $this->assertSame('Nahwu', $first['mapel_name']);
        $this->assertSame('Ustadz Ahmad', $first['teacher_name']);
        $this->assertSame(1, $first['component_count']);
        $this->assertSame(1, $first['scored_count']);
        $this->assertEquals(88.0, $first['average_score']);
    }
}
