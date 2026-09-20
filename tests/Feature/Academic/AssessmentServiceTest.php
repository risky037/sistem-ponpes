<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\AssessmentDefinition;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Santri;
use App\Models\StudentAssessmentScore;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Academic\AssessmentService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssessmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AssessmentService $service;

    protected User $admin;

    protected User $pengurus;

    protected User $santriUser;

    protected Kelas $kelas;

    protected Kelas $otherKelas;

    protected Mapel $mapel;

    protected AcademicYear $academicYear;

    protected AcademicYear $otherAcademicYear;

    protected TeachingAssignment $teachingAssignment;

    protected AssessmentDefinition $definition;

    protected Santri $santri;

    protected AcademicEnrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AssessmentService;

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        $santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Layanan Nilai',
            'email' => 'admin.srv@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->pengurus = User::create([
            'name' => 'Pengurus Layanan Nilai',
            'email' => 'pengurus.srv@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->pengurus->assignRole($pengurusRole);

        $this->santriUser = User::create([
            'name' => 'Santri Layanan Nilai',
            'email' => 'santri.srv@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->santriUser->assignRole($santriRole);

        $this->kelas = Kelas::create([
            'kode' => 'KLS-SRV01',
            'tingkatan' => 'ALFIYAH',
            'kelas' => 'Alfiyah 1',
        ]);

        $this->otherKelas = Kelas::create([
            'kode' => 'KLS-SRV02',
            'tingkatan' => 'ALFIYAH',
            'kelas' => 'Alfiyah 2',
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-15',
            'end_date' => '2024-12-20',
            'is_active' => true,
        ]);

        $this->otherAcademicYear = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'Ganjil',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => false,
        ]);

        $this->mapel = Mapel::create([
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-SRV-01',
            'name' => 'Fathul Qorib',
            'is_active' => true,
        ]);

        $this->teachingAssignment = TeachingAssignment::create([
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $this->mapel->id,
            'user_id' => $this->pengurus->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => 'Aktif',
        ]);

        $this->definition = AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Ujian Akhir Semester',
            'type' => 'UAS',
            'weight' => 40,
            'is_active' => true,
        ]);

        $this->santri = Santri::create([
            'user_id' => $this->santriUser->id,
            'no_induk' => '97001',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567892',
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Malang',
            'status' => 'Santri Aktif',
        ]);

        $this->enrollment = AcademicEnrollment::create([
            'santri_id' => $this->santri->id,
            'kelas_id' => $this->kelas->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => '2024-07-15',
        ]);
    }

    public function test_create_definition_success_and_validations(): void
    {
        $def = $this->service->createDefinition($this->academicYear, [
            'name' => 'Tugas Praktik Mandiri',
            'type' => 'Praktik',
            'weight' => 20,
        ]);

        $this->assertDatabaseHas('assessment_definitions', [
            'id' => $def->id,
            'name' => 'Tugas Praktik Mandiri',
            'weight' => 20,
        ]);

        // Duplicate name rejection
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('sudah ada pada tahun ajaran ini');
        $this->service->createDefinition($this->academicYear, [
            'name' => 'Tugas Praktik Mandiri',
            'type' => 'Praktik',
            'weight' => 20,
        ]);
    }

    public function test_create_definition_rejects_invalid_weight(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Bobot penilaian harus berada dalam rentang 0 hingga 100.');

        $this->service->createDefinition($this->academicYear, [
            'name' => 'UTS Ekstrem',
            'type' => 'UTS',
            'weight' => 150,
        ]);
    }

    public function test_create_component_rejects_mismatched_academic_year(): void
    {
        $mismatchedDefinition = AssessmentDefinition::create([
            'academic_year_id' => $this->otherAcademicYear->id,
            'name' => 'UAS Tahun Lain',
            'type' => 'UAS',
            'weight' => 30,
            'is_active' => true,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Definisi penilaian tidak sesuai dengan tahun ajaran penugasan mengajar.');

        $this->service->createComponent($this->teachingAssignment, $mismatchedDefinition);
    }

    public function test_create_component_rejects_inactive_definition(): void
    {
        $inactiveDef = AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'UTS Tidak Aktif',
            'type' => 'UTS',
            'weight' => 20,
            'is_active' => false,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Definisi penilaian sudah nonaktif.');

        $this->service->createComponent($this->teachingAssignment, $inactiveDef);
    }

    public function test_record_score_success(): void
    {
        $component = $this->service->createComponent($this->teachingAssignment, $this->definition);

        $score = $this->service->recordScore(
            component: $component,
            enrollment: $this->enrollment,
            score: 92.50,
            notes: 'Sangat Memuaskan',
            gradedBy: $this->pengurus
        );

        $this->assertInstanceOf(StudentAssessmentScore::class, $score);
        $this->assertEquals(92.50, $score->score);
        $this->assertEquals($this->pengurus->id, $score->graded_by);
        $this->assertNotNull($score->graded_at);
        $this->assertEquals('Sangat Memuaskan', $score->notes);
    }

    public function test_record_score_rejects_out_of_range(): void
    {
        $component = $this->service->createComponent($this->teachingAssignment, $this->definition);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nilai harus berada dalam rentang 0 hingga 100.');

        $this->service->recordScore($component, $this->enrollment, 105.00);
    }

    public function test_record_score_rejects_different_class_enrollment(): void
    {
        $component = $this->service->createComponent($this->teachingAssignment, $this->definition);

        $diffUser = User::create([
            'name' => 'Santri Lain Kelas',
            'email' => 'santri.diff@example.com',
            'password' => Hash::make('Secret123!'),
        ]);

        $diffSantri = Santri::create([
            'user_id' => $diffUser->id,
            'no_induk' => '97002',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567895',
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Malang',
            'status' => 'Santri Aktif',
        ]);

        $diffClassEnrollment = AcademicEnrollment::create([
            'santri_id' => $diffSantri->id,
            'kelas_id' => $this->otherKelas->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => '2024-07-15',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Santri tidak terdaftar di kelas penugasan mengajar ini.');

        $this->service->recordScore($component, $diffClassEnrollment, 85.00);
    }

    public function test_record_score_rejects_different_year_enrollment(): void
    {
        $component = $this->service->createComponent($this->teachingAssignment, $this->definition);

        $diffYearEnrollment = AcademicEnrollment::create([
            'santri_id' => $this->santri->id,
            'kelas_id' => $this->kelas->id,
            'academic_year_id' => $this->otherAcademicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => '2024-07-15',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Santri tidak terdaftar di tahun ajaran penugasan mengajar ini.');

        $this->service->recordScore($component, $diffYearEnrollment, 85.00);
    }

    public function test_record_score_rejects_inactive_enrollment(): void
    {
        $component = $this->service->createComponent($this->teachingAssignment, $this->definition);

        $this->enrollment->update(['status' => AcademicEnrollment::STATUS_NONAKTIF]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Santri tidak berstatus aktif dalam penempatan akademik ini.');

        $this->service->recordScore($component, $this->enrollment, 85.00);
    }

    public function test_record_score_prevents_duplicate_score(): void
    {
        $component = $this->service->createComponent($this->teachingAssignment, $this->definition);

        $this->service->recordScore($component, $this->enrollment, 80.00);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nilai untuk santri pada komponen penilaian ini sudah ada.');

        $this->service->recordScore($component, $this->enrollment, 85.00);
    }

    public function test_bulk_record_scores_creates_and_updates_in_transaction(): void
    {
        $component = $this->service->createComponent($this->teachingAssignment, $this->definition);

        // First bulk insert
        $records = $this->service->bulkRecordScores($component, [
            [
                'academic_enrollment_id' => $this->enrollment->id,
                'score' => 78.50,
                'notes' => 'Cukup baik',
            ],
        ], $this->pengurus);

        $this->assertCount(1, $records);
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_component_id' => $component->id,
            'academic_enrollment_id' => $this->enrollment->id,
            'score' => 78.50,
        ]);

        // Second bulk update
        $recordsUpdated = $this->service->bulkRecordScores($component, [
            [
                'academic_enrollment_id' => $this->enrollment->id,
                'score' => 85.00,
                'notes' => 'Ada peningkatan',
            ],
        ], $this->pengurus);

        $this->assertCount(1, $recordsUpdated);
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_component_id' => $component->id,
            'academic_enrollment_id' => $this->enrollment->id,
            'score' => 85.00,
            'notes' => 'Ada peningkatan',
        ]);
    }

    public function test_bulk_record_scores_rolls_back_when_any_score_invalid(): void
    {
        $component = $this->service->createComponent($this->teachingAssignment, $this->definition);

        try {
            $this->service->bulkRecordScores($component, [
                [
                    'academic_enrollment_id' => $this->enrollment->id,
                    'score' => 150.00, // Invalid score
                ],
            ]);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertEquals('Nilai harus berada dalam rentang 0 hingga 100.', $e->getMessage());
        }

        $this->assertDatabaseMissing('student_assessment_scores', [
            'assessment_component_id' => $component->id,
        ]);
    }

    public function test_update_score_success(): void
    {
        $component = $this->service->createComponent($this->teachingAssignment, $this->definition);
        $score = $this->service->recordScore($component, $this->enrollment, 70.00);

        $updated = $this->service->updateScore($score, 88.00, 'Revisi remidi', $this->admin);

        $this->assertEquals(88.00, $updated->score);
        $this->assertEquals('Revisi remidi', $updated->notes);
        $this->assertEquals($this->admin->id, $updated->graded_by);
    }
}
