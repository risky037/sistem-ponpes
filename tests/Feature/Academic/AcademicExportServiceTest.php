<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicExportLog;
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
use App\Services\Academic\AcademicExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class AcademicExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicExportService $service;

    protected User $admin;

    protected Kelas $kelas;

    protected AcademicYear $academicYear;

    protected Mapel $mapel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AcademicExportService;

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Export',
            'email' => 'admin.export@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

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
            'name' => 'Nahwu Dasar',
            'code' => 'NHW-01',
            'kelas_id' => $this->kelas->id,
            'description' => 'Tata bahasa Arab dasar',
        ]);
    }

    protected function createSantriWithEnrollment(string $nis, string $name, string $status = 'Aktif'): AcademicEnrollment
    {
        $user = User::create([
            'name' => $name,
            'email' => "santri.{$nis}@example.com",
            'password' => Hash::make('Secret123!'),
        ]);

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
            'status' => $status,
            'enrolled_at' => now(),
        ]);
    }

    public function test_export_enrollment_print_format_and_filters_work(): void
    {
        $enr1 = $this->createSantriWithEnrollment('1001', 'Ahmad Zaki', 'Aktif');
        $enr2 = $this->createSantriWithEnrollment('1002', 'Budi Santoso', 'Nonaktif');

        $result = $this->service->exportEnrollment(
            filters: ['academic_year_id' => $this->academicYear->id, 'status' => 'Aktif'],
            format: 'print',
            user: $this->admin,
            ip: '127.0.0.1'
        );

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('Ahmad Zaki', $result['data']->first()->santri->nama_lengkap);

        $this->assertDatabaseHas('academic_export_logs', [
            'user_id' => $this->admin->id,
            'export_type' => AcademicExportLog::TYPE_ENROLLMENT,
            'format' => 'print',
            'academic_year_id' => $this->academicYear->id,
            'records_count' => 1,
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_export_enrollment_empty_dataset_handled(): void
    {
        $result = $this->service->exportEnrollment(
            filters: ['academic_year_id' => 999999],
            format: 'print',
            user: $this->admin,
            ip: '127.0.0.1'
        );

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total']);
        $this->assertCount(0, $result['data']);

        $this->assertDatabaseHas('academic_export_logs', [
            'user_id' => $this->admin->id,
            'export_type' => AcademicExportLog::TYPE_ENROLLMENT,
            'records_count' => 0,
        ]);
    }

    public function test_export_enrollment_excel_response_generated(): void
    {
        $this->createSantriWithEnrollment('1003', 'Citra Kirana', 'Aktif');

        $response = $this->service->exportEnrollment(
            filters: ['academic_year_id' => $this->academicYear->id],
            format: 'xlsx',
            user: $this->admin,
            ip: '127.0.0.1'
        );

        $this->assertInstanceOf(BinaryFileResponse::class, $response);

        $this->assertDatabaseHas('academic_export_logs', [
            'user_id' => $this->admin->id,
            'export_type' => AcademicExportLog::TYPE_ENROLLMENT,
            'format' => 'xlsx',
            'records_count' => 1,
        ]);
    }

    public function test_export_teaching_assignment_works(): void
    {
        $teacher = User::create([
            'name' => 'Ustadz Mansyur',
            'email' => 'mansyur@example.com',
            'password' => Hash::make('Secret123!'),
        ]);

        TeachingAssignment::create([
            'academic_year_id' => $this->academicYear->id,
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $this->mapel->id,
            'user_id' => $teacher->id,
            'status' => 'Aktif',
        ]);

        $result = $this->service->exportTeachingAssignment(
            filters: ['academic_year_id' => $this->academicYear->id, 'kelas_id' => $this->kelas->id],
            format: 'print',
            user: $this->admin,
            ip: '192.168.1.1'
        );

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('Nahwu Dasar', $result['data']->first()->mapel->name);

        $this->assertDatabaseHas('academic_export_logs', [
            'user_id' => $this->admin->id,
            'export_type' => AcademicExportLog::TYPE_TEACHING_ASSIGNMENT,
            'records_count' => 1,
        ]);
    }

    public function test_export_attendance_summary_aggregation_works(): void
    {
        $enrollment = $this->createSantriWithEnrollment('1004', 'Dedi Mizwar');

        $teacher = User::create([
            'name' => 'Guru Fiqih',
            'email' => 'fiqih@example.com',
            'password' => Hash::make('Secret123!'),
        ]);

        $assignment = TeachingAssignment::create([
            'academic_year_id' => $this->academicYear->id,
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $this->mapel->id,
            'user_id' => $teacher->id,
            'status' => 'Aktif',
        ]);

        $session1 = TeachingSession::create([
            'teaching_assignment_id' => $assignment->id,
            'session_date' => '2025-08-01',
            'start_time' => '07:30',
            'end_time' => '09:00',
            'status' => 'Completed',
        ]);

        $session2 = TeachingSession::create([
            'teaching_assignment_id' => $assignment->id,
            'session_date' => '2025-08-08',
            'start_time' => '07:30',
            'end_time' => '09:00',
            'status' => 'Completed',
        ]);

        AttendanceRecord::create([
            'teaching_session_id' => $session1->id,
            'academic_enrollment_id' => $enrollment->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'marked_by' => $this->admin->id,
            'marked_at' => now(),
        ]);

        AttendanceRecord::create([
            'teaching_session_id' => $session2->id,
            'academic_enrollment_id' => $enrollment->id,
            'status' => AttendanceRecord::STATUS_IZIN,
            'marked_by' => $this->admin->id,
            'marked_at' => now(),
        ]);

        $result = $this->service->exportAttendance(
            filters: ['academic_year_id' => $this->academicYear->id, 'kelas_id' => $this->kelas->id],
            format: 'print',
            user: $this->admin
        );

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total']);
        $row = $result['data']->first();
        $this->assertEquals(2, $row->total_sessions);
        $this->assertEquals(1, $row->present_count);
        $this->assertEquals(1, $row->excused_count);
        $this->assertEquals(50.0, $row->attendance_rate);
    }

    public function test_export_assessment_scores_works(): void
    {
        $enrollment = $this->createSantriWithEnrollment('1005', 'Erwin Prasetya');

        $teacher = User::create([
            'name' => 'Guru Nahwu',
            'email' => 'nahwu@example.com',
            'password' => Hash::make('Secret123!'),
        ]);

        $assignment = TeachingAssignment::create([
            'academic_year_id' => $this->academicYear->id,
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $this->mapel->id,
            'user_id' => $teacher->id,
            'status' => 'Aktif',
        ]);

        $definition = AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Tugas 1',
            'type' => 'Tugas',
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
            'score' => 88.50,
            'graded_by' => $this->admin->id,
            'graded_at' => now(),
        ]);

        $result = $this->service->exportAssessment(
            filters: ['academic_year_id' => $this->academicYear->id, 'kelas_id' => $this->kelas->id],
            format: 'print',
            user: $this->admin
        );

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total']);
        $row = $result['data']->first();
        $this->assertEquals('Erwin Prasetya', $row->nama);
        $this->assertEquals(88.50, $row->score);
    }

    public function test_export_performance_analytics_works(): void
    {
        $enrollment = $this->createSantriWithEnrollment('1006', 'Farhan Ali');

        AcademicPerformanceSummary::create([
            'academic_enrollment_id' => $enrollment->id,
            'total_sessions' => 10,
            'present_count' => 9,
            'excused_count' => 1,
            'sick_count' => 0,
            'absent_count' => 0,
            'attendance_rate' => 90.00,
            'scored_components' => 3,
            'total_components' => 3,
            'average_score' => 85.00,
            'computation_status' => 'Lengkap',
            'computed_at' => now(),
        ]);

        $result = $this->service->exportPerformance(
            filters: ['academic_year_id' => $this->academicYear->id, 'computation_status' => 'Lengkap'],
            format: 'print',
            user: $this->admin
        );

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total']);
        $row = $result['data']->first();
        $this->assertEquals('Farhan Ali', $row->nama);
        $this->assertEquals(90.00, $row->attendance_rate);
        $this->assertEquals(85.00, $row->average_score);
    }

    public function test_get_recent_logs_retrieves_audit_history(): void
    {
        AcademicExportLog::create([
            'user_id' => $this->admin->id,
            'export_type' => AcademicExportLog::TYPE_ENROLLMENT,
            'format' => 'xlsx',
            'records_count' => 10,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $logs = $this->service->getRecentLogs(5);

        $this->assertCount(1, $logs);
        $this->assertEquals(AcademicExportLog::TYPE_ENROLLMENT, $logs->first()->export_type);
        $this->assertEquals($this->admin->name, $logs->first()->user->name);
    }
}
