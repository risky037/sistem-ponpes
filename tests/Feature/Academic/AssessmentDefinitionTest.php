<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\AssessmentDefinition;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssessmentDefinitionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $santriUser;

    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        $santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Nilai',
            'email' => 'admin.eval@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->pengurus = User::create([
            'name' => 'Pengurus Nilai',
            'email' => 'pengurus.eval@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->pengurus->assignRole($pengurusRole);

        $this->santriUser = User::create([
            'name' => 'Santri Nilai',
            'email' => 'santri.eval@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->santriUser->assignRole($santriRole);

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-15',
            'end_date' => '2024-12-20',
            'is_active' => true,
        ]);
    }

    public function test_assessment_definition_has_valid_attributes_and_relationships(): void
    {
        $definition = AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'UTS Ganjil',
            'type' => AssessmentDefinition::TYPE_UTS,
            'weight' => 25,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(AssessmentDefinition::class, $definition);
        $this->assertEquals($this->academicYear->id, $definition->academicYear->id);
        $this->assertEquals($this->academicYear->id, $definition->academic_year->id);
        $this->assertTrue($definition->is_active);
        $this->assertEquals(25, $definition->weight);
        $this->assertCount(1, $this->academicYear->assessmentDefinitions);
    }

    public function test_assessment_definition_enforces_unique_name_per_academic_year(): void
    {
        AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'UTS Ganjil',
            'type' => AssessmentDefinition::TYPE_UTS,
            'weight' => 25,
            'is_active' => true,
        ]);

        $this->expectException(QueryException::class);

        AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'UTS Ganjil',
            'type' => AssessmentDefinition::TYPE_TUGAS,
            'weight' => 15,
            'is_active' => true,
        ]);
    }

    public function test_restrict_on_delete_academic_year_with_assessment_definition(): void
    {
        AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'UTS Ganjil',
            'type' => AssessmentDefinition::TYPE_UTS,
            'weight' => 25,
            'is_active' => true,
        ]);

        $this->expectException(QueryException::class);
        $this->academicYear->delete();
    }

    public function test_admin_and_pengurus_can_view_definition_index(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(route('assessment.definition.index'));
        $responseAdmin->assertOk();

        $responsePengurus = $this->actingAs($this->pengurus)->get(route('assessment.definition.index'));
        $responsePengurus->assertOk();
    }

    public function test_santri_cannot_access_assessment_definition(): void
    {
        $response = $this->actingAs($this->santriUser)->get(route('assessment.definition.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_create_definition_via_post(): void
    {
        $payload = [
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Ujian Akhir Semester',
            'type' => 'UAS',
            'weight' => 35,
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->admin)->post(route('assessment.definition.store'), $payload);
        $response->assertRedirect(route('assessment.definition.index'));

        $this->assertDatabaseHas('assessment_definitions', [
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Ujian Akhir Semester',
            'type' => 'UAS',
            'weight' => 35,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_definition(): void
    {
        $definition = AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Tugas 1',
            'type' => 'Tugas',
            'weight' => 10,
            'is_active' => true,
        ]);

        $updatePayload = [
            'name' => 'Tugas Mingguan',
            'type' => 'Tugas',
            'weight' => 15,
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->admin)->patch(
            route('assessment.definition.update', $definition->id),
            $updatePayload
        );
        $response->assertRedirect(route('assessment.definition.index'));

        $this->assertDatabaseHas('assessment_definitions', [
            'id' => $definition->id,
            'name' => 'Tugas Mingguan',
            'weight' => 15,
        ]);
    }

    public function test_destroy_definition_hard_deletes_when_no_children(): void
    {
        $definition = AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Praktik Ibadah',
            'type' => 'Praktik',
            'weight' => 20,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('assessment.definition.destroy', $definition->id));
        $response->assertRedirect(route('assessment.definition.index'));

        $this->assertDatabaseMissing('assessment_definitions', [
            'id' => $definition->id,
        ]);
    }

    public function test_destroy_definition_deactivates_when_children_exist(): void
    {
        $definition = AssessmentDefinition::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'UTS Ganjil',
            'type' => 'UTS',
            'weight' => 30,
            'is_active' => true,
        ]);

        $kelas = Kelas::create([
            'kode' => 'KLS-DEF01',
            'tingkatan' => 'ALFIYAH',
            'kelas' => 'Alfiyah 1',
        ]);

        $mapel = Mapel::create([
            'kelas_id' => $kelas->id,
            'code' => 'MPL-DEF-01',
            'name' => 'Nahwu',
            'is_active' => true,
        ]);

        $assignment = TeachingAssignment::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'user_id' => $this->pengurus->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => 'Aktif',
        ]);

        AssessmentComponent::create([
            'teaching_assignment_id' => $assignment->id,
            'assessment_definition_id' => $definition->id,
            'weight' => 30,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('assessment.definition.destroy', $definition->id));
        $response->assertRedirect(route('assessment.definition.index'));

        $this->assertDatabaseHas('assessment_definitions', [
            'id' => $definition->id,
            'is_active' => false,
        ]);
    }
}
