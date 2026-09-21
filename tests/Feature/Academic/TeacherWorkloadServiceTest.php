<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\AssessmentDefinition;
use App\Models\ClassSchedule;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Santri;
use App\Models\StudentAssessmentScore;
use App\Models\TeachingAssignment;
use App\Models\TeachingSession;
use App\Models\User;
use App\Services\Academic\TeacherWorkloadService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherWorkloadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TeacherWorkloadService $workloadService;

    protected AcademicYear $academicYear;

    protected User $teacher;

    protected Kelas $kelas;

    protected Mapel $mapel1;

    protected Mapel $mapel2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->workloadService = app(TeacherWorkloadService::class);

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-01',
            'end_date' => '2024-12-31',
            'is_active' => true,
        ]);

        $this->teacher = User::create([
            'name' => 'Ustadz Zaid',
            'email' => 'zaid@example.com',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->kelas = Kelas::create(['kode' => '7A', 'kelas' => 'A', 'tingkatan' => '7']);

        $this->mapel1 = Mapel::create(['kelas_id' => $this->kelas->id, 'code' => 'FQH-01', 'name' => 'Fiqih', 'is_active' => true]);
        $this->mapel2 = Mapel::create(['kelas_id' => $this->kelas->id, 'code' => 'THD-01', 'name' => 'Tauhid', 'is_active' => true]);
    }

    public function test_get_teacher_workload_overview_returns_empty_when_no_assignments(): void
    {
        $overview = $this->workloadService->getTeacherWorkloadOverview($this->academicYear);
        $this->assertCount(0, $overview);

        $stats = $this->workloadService->getWorkloadDistributionStats($this->academicYear);
        $this->assertSame(0, $stats['total_active_teachers']);
        $this->assertSame(0, $stats['total_assignments']);
        $this->assertSame(0, $stats['total_sessions_completed']);
        $this->assertNull($stats['overall_fulfillment_rate']);
    }

    public function test_get_teacher_workload_overview_calculates_all_faculty_metrics(): void
    {
        // Assignment 1: Fiqih
        $asg1 = TeachingAssignment::create([
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $this->mapel1->id,
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => TeachingAssignment::STATUS_AKTIF,
        ]);

        // Assignment 2: Tauhid
        $asg2 = TeachingAssignment::create([
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $this->mapel2->id,
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => TeachingAssignment::STATUS_AKTIF,
        ]);

        // Weekly schedule slot
        ClassSchedule::create([
            'kelas_id' => $this->kelas->id,
            'teaching_assignment_id' => $asg1->id,
            'academic_year_id' => $this->academicYear->id,
            'day_of_week' => 1,
            'start_time' => '07:30',
            'end_time' => '09:00',
        ]);

        // Sessions: 3 completed, 1 planned, 1 cancelled
        TeachingSession::create([
            'teaching_assignment_id' => $asg1->id,
            'session_date' => '2024-08-01',
            'status' => TeachingSession::STATUS_COMPLETED,
        ]);
        TeachingSession::create([
            'teaching_assignment_id' => $asg1->id,
            'session_date' => '2024-08-08',
            'status' => TeachingSession::STATUS_COMPLETED,
        ]);
        TeachingSession::create([
            'teaching_assignment_id' => $asg2->id,
            'session_date' => '2024-08-02',
            'status' => TeachingSession::STATUS_COMPLETED,
        ]);
        TeachingSession::create([
            'teaching_assignment_id' => $asg2->id,
            'session_date' => '2024-08-09',
            'status' => TeachingSession::STATUS_PLANNED,
        ]);
        TeachingSession::create([
            'teaching_assignment_id' => $asg2->id,
            'session_date' => '2024-08-16',
            'status' => TeachingSession::STATUS_CANCELLED,
        ]);

        // Assessment component and score
        $def = AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Tugas Fiqih',
            'type' => AssessmentDefinition::TYPE_TUGAS,
            'weight' => 20,
            'is_active' => true,
        ]);

        $comp = AssessmentComponent::create([
            'teaching_assignment_id' => $asg1->id,
            'assessment_definition_id' => $def->id,
            'weight' => 20,
        ]);

        $uSantri = User::create(['name' => 'Santri W', 'email' => 'sw@example.com', 'password' => Hash::make('pass')]);
        $santri = Santri::create([
            'user_id' => $uSantri->id,
            'no_induk' => '1099',
            'nis' => '1099',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => 628123456789,
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Kediri',
            'status' => 'Santri Aktif',
        ]);
        $enr = AcademicEnrollment::create([
            'academic_year_id' => $this->academicYear->id,
            'santri_id' => $santri->id,
            'kelas_id' => $this->kelas->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => now(),
        ]);

        StudentAssessmentScore::create([
            'assessment_component_id' => $comp->id,
            'academic_enrollment_id' => $enr->id,
            'score' => 90.0,
            'graded_by' => $this->teacher->id,
            'graded_at' => now(),
        ]);

        $overview = $this->workloadService->getTeacherWorkloadOverview($this->academicYear);

        $this->assertCount(1, $overview);
        $t = $overview->first();

        $this->assertSame($this->teacher->id, $t['user_id']);
        $this->assertSame('Ustadz Zaid', $t['name']);
        $this->assertSame(2, $t['assignments_count']);
        $this->assertSame(2, $t['subjects_count']);
        $this->assertSame(1, $t['classes_count']);
        $this->assertSame(1, $t['weekly_schedule_slots']);
        $this->assertSame(3, $t['completed_sessions']);
        $this->assertSame(1, $t['planned_sessions']);
        $this->assertSame(1, $t['cancelled_sessions']);
        $this->assertSame(4, $t['total_sessions']); // 3 completed + 1 planned
        $this->assertEquals(75.0, $t['fulfillment_rate']); // (3 / 4) * 100
        $this->assertSame(1, $t['components_count']);
        $this->assertSame(1, $t['scored_records_count']);

        $stats = $this->workloadService->getWorkloadDistributionStats($this->academicYear);
        $this->assertSame(1, $stats['total_active_teachers']);
        $this->assertSame(2, $stats['total_assignments']);
        $this->assertSame(3, $stats['total_sessions_completed']);
        $this->assertEquals(2.0, $stats['avg_assignments_per_teacher']);
        $this->assertEquals(3.0, $stats['avg_sessions_per_teacher']);
        $this->assertEquals(75.0, $stats['overall_fulfillment_rate']);
    }
}
