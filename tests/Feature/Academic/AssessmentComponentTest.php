<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\AssessmentDefinition;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Santri;
use App\Models\StudentAssessmentScore;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssessmentComponentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $santriUser;

    protected Kelas $kelas;

    protected Mapel $mapel;

    protected AcademicYear $academicYear;

    protected TeachingAssignment $teachingAssignment;

    protected AssessmentDefinition $definition;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        $santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Komponen',
            'email' => 'admin.comp@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->pengurus = User::create([
            'name' => 'Pengurus Komponen',
            'email' => 'pengurus.comp@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->pengurus->assignRole($pengurusRole);

        $this->santriUser = User::create([
            'name' => 'Santri Komponen',
            'email' => 'santri.comp@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->santriUser->assignRole($santriRole);

        $this->kelas = Kelas::create([
            'kode' => 'KLS-CMP01',
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

        $this->mapel = Mapel::create([
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-CMP-01',
            'name' => 'Shorof',
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
            'name' => 'Ujian Tengah Semester',
            'type' => 'UTS',
            'weight' => 25,
            'is_active' => true,
        ]);
    }

    public function test_assessment_component_has_valid_attributes_and_relationships(): void
    {
        $component = AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => 30, // Custom override
        ]);

        $this->assertInstanceOf(AssessmentComponent::class, $component);
        $this->assertEquals($this->teachingAssignment->id, $component->teachingAssignment->id);
        $this->assertEquals($this->teachingAssignment->id, $component->teaching_assignment->id);
        $this->assertEquals($this->definition->id, $component->assessmentDefinition->id);
        $this->assertEquals($this->definition->id, $component->assessment_definition->id);
        $this->assertEquals(30, $component->weight);
        $this->assertCount(1, $this->teachingAssignment->assessmentComponents);
    }

    public function test_assessment_component_enforces_unique_per_assignment_and_definition(): void
    {
        AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => 25,
        ]);

        $this->expectException(QueryException::class);

        AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => 30,
        ]);
    }

    public function test_restrict_on_delete_teaching_assignment_with_component(): void
    {
        AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => 25,
        ]);

        $this->expectException(QueryException::class);
        $this->teachingAssignment->delete();
    }

    public function test_restrict_on_delete_definition_with_component(): void
    {
        AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => 25,
        ]);

        $this->expectException(QueryException::class);
        $this->definition->delete();
    }

    public function test_admin_and_pengurus_can_view_component_index(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(route('assessment.component.index'));
        $responseAdmin->assertOk();

        $responsePengurus = $this->actingAs($this->pengurus)->get(route('assessment.component.index'));
        $responsePengurus->assertOk();
    }

    public function test_santri_cannot_access_assessment_component(): void
    {
        $response = $this->actingAs($this->santriUser)->get(route('assessment.component.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_store_component_via_post(): void
    {
        $payload = [
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => 35,
        ];

        $response = $this->actingAs($this->admin)->post(route('assessment.component.store'), $payload);
        $response->assertRedirect(route('assessment.component.index'));

        $this->assertDatabaseHas('assessment_components', [
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => 35,
        ]);
    }

    public function test_component_store_defaults_to_definition_weight_when_empty(): void
    {
        $payload = [
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => null,
        ];

        $response = $this->actingAs($this->admin)->post(route('assessment.component.store'), $payload);
        $response->assertRedirect(route('assessment.component.index'));

        $this->assertDatabaseHas('assessment_components', [
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => $this->definition->weight,
        ]);
    }

    public function test_destroy_component_without_scores(): void
    {
        $component = AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => 25,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('assessment.component.destroy', $component->id));
        $response->assertRedirect(route('assessment.component.index'));

        $this->assertDatabaseMissing('assessment_components', [
            'id' => $component->id,
        ]);
    }

    public function test_destroy_component_fails_when_scores_exist(): void
    {
        $component = AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => 25,
        ]);

        $santri = Santri::create([
            'user_id' => $this->santriUser->id,
            'no_induk' => '98001',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567891',
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Jombang',
            'status' => 'Santri Aktif',
        ]);

        $enrollment = AcademicEnrollment::create([
            'santri_id' => $santri->id,
            'kelas_id' => $this->kelas->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => '2024-07-15',
        ]);

        StudentAssessmentScore::create([
            'assessment_component_id' => $component->id,
            'academic_enrollment_id' => $enrollment->id,
            'score' => 88.50,
            'notes' => 'Baik',
            'graded_by' => $this->pengurus->id,
            'graded_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('assessment.component.destroy', $component->id));
        $response->assertRedirect();

        $this->assertDatabaseHas('assessment_components', [
            'id' => $component->id,
        ]);
    }

    public function test_admin_and_pengurus_can_view_score_management(): void
    {
        $component = AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => 25,
        ]);

        $responseAdmin = $this->actingAs($this->admin)->get(route('assessment.score.manage', $component->id));
        $responseAdmin->assertOk();

        $responsePengurus = $this->actingAs($this->pengurus)->get(route('assessment.score.manage', $component->id));
        $responsePengurus->assertOk();
    }

    public function test_admin_can_store_scores_via_post(): void
    {
        $component = AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => 25,
        ]);

        $santri = Santri::create([
            'user_id' => $this->santriUser->id,
            'no_induk' => '98002',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567893',
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Jombang',
            'status' => 'Santri Aktif',
        ]);

        $enrollment = AcademicEnrollment::create([
            'santri_id' => $santri->id,
            'kelas_id' => $this->kelas->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => '2024-07-15',
        ]);

        $payload = [
            'scores' => [
                [
                    'academic_enrollment_id' => $enrollment->id,
                    'score' => 95.00,
                    'notes' => 'Istimewa',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('assessment.score.store', $component->id), $payload);
        $response->assertRedirect(route('assessment.score.manage', $component->id));

        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_component_id' => $component->id,
            'academic_enrollment_id' => $enrollment->id,
            'score' => 95.00,
            'notes' => 'Istimewa',
        ]);
    }

    public function test_admin_can_update_score_via_patch(): void
    {
        $component = AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => 25,
        ]);

        $santri = Santri::create([
            'user_id' => $this->santriUser->id,
            'no_induk' => '98003',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567894',
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Jombang',
            'status' => 'Santri Aktif',
        ]);

        $enrollment = AcademicEnrollment::create([
            'santri_id' => $santri->id,
            'kelas_id' => $this->kelas->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => '2024-07-15',
        ]);

        $score = StudentAssessmentScore::create([
            'assessment_component_id' => $component->id,
            'academic_enrollment_id' => $enrollment->id,
            'score' => 75.00,
            'notes' => 'Awal',
            'graded_by' => $this->admin->id,
            'graded_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->patch(
            route('assessment.score.update', $score->id),
            [
                'score' => 85.00,
                'notes' => 'Perbaikan remidi',
            ]
        );
        $response->assertRedirect();

        $this->assertDatabaseHas('student_assessment_scores', [
            'id' => $score->id,
            'score' => 85.00,
            'notes' => 'Perbaikan remidi',
        ]);
    }

    public function test_santri_cannot_access_score_manage_or_store(): void
    {
        $component = AssessmentComponent::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'assessment_definition_id' => $this->definition->id,
            'weight' => 25,
        ]);

        $response = $this->actingAs($this->santriUser)->get(route('assessment.score.manage', $component->id));
        $response->assertForbidden();

        $responsePost = $this->actingAs($this->santriUser)->post(route('assessment.score.store', $component->id), []);
        $responsePost->assertForbidden();
    }
}
