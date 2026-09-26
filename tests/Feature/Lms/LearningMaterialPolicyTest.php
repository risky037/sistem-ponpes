<?php

namespace Tests\Feature\Lms;

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\LearningMaterial;
use App\Models\Mapel;
use App\Models\Santri;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class LearningMaterialPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_administrator_bypasses_all_gates(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $material = LearningMaterial::factory()->draft()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', LearningMaterial::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $material));
        $this->assertTrue(Gate::forUser($admin)->allows('create', LearningMaterial::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $material));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $material));
        $this->assertTrue(Gate::forUser($admin)->allows('publish', $material));
        $this->assertTrue(Gate::forUser($admin)->allows('archive', $material));
        $this->assertTrue(Gate::forUser($admin)->allows('uploadAttachment', $material));
        $this->assertTrue(Gate::forUser($admin)->allows('downloadFile', $material));
    }

    public function test_pengurus_can_view_any_and_view_materials_for_academic_supervision(): void
    {
        $pengurus = User::factory()->create();
        $pengurus->assignRole('Pengurus');

        $material = LearningMaterial::factory()->published()->create();

        $this->assertTrue(Gate::forUser($pengurus)->allows('viewAny', LearningMaterial::class));
        $this->assertTrue(Gate::forUser($pengurus)->allows('view', $material));
        $this->assertFalse(Gate::forUser($pengurus)->allows('create', LearningMaterial::class));
        $this->assertFalse(Gate::forUser($pengurus)->allows('update', $material));
        $this->assertFalse(Gate::forUser($pengurus)->allows('delete', $material));
    }

    public function test_guru_authorization_rules_for_creation_ownership_and_peer_review(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $mapelA = Mapel::factory()->create(['name' => 'Fiqih']);
        $mapelB = Mapel::factory()->create(['name' => 'Nahwu']);
        $kelas1 = Kelas::factory()->create();
        $kelas2 = Kelas::factory()->create();

        $guruAuthor = User::factory()->create(['name' => 'Ustadz Pembuat']);
        $guruAuthor->assignRole('Guru');

        TeachingAssignment::create([
            'user_id' => $guruAuthor->id,
            'mapel_id' => $mapelA->id,
            'kelas_id' => $kelas1->id,
            'academic_year_id' => $academicYear->id,
            'status' => TeachingAssignment::STATUS_AKTIF,
        ]);

        $guruPeerSameMapel = User::factory()->create(['name' => 'Ustadz Rekan Fiqih']);
        $guruPeerSameMapel->assignRole('Guru');

        TeachingAssignment::create([
            'user_id' => $guruPeerSameMapel->id,
            'mapel_id' => $mapelA->id,
            'kelas_id' => $kelas2->id,
            'academic_year_id' => $academicYear->id,
            'status' => TeachingAssignment::STATUS_AKTIF,
        ]);

        $guruDifferentMapel = User::factory()->create(['name' => 'Ustadz Nahwu']);
        $guruDifferentMapel->assignRole('Guru');

        TeachingAssignment::create([
            'user_id' => $guruDifferentMapel->id,
            'mapel_id' => $mapelB->id,
            'kelas_id' => $kelas1->id,
            'academic_year_id' => $academicYear->id,
            'status' => TeachingAssignment::STATUS_AKTIF,
        ]);

        $guruWithoutAssignment = User::factory()->create(['name' => 'Ustadz Tanpa Kelas']);
        $guruWithoutAssignment->assignRole('Guru');

        // Create permission check
        $this->assertTrue(Gate::forUser($guruAuthor)->allows('create', LearningMaterial::class));
        $this->assertFalse(Gate::forUser($guruWithoutAssignment)->allows('create', LearningMaterial::class));

        // Material authored by $guruAuthor
        $materialPublished = LearningMaterial::factory()->published()->create([
            'teacher_id' => $guruAuthor->id,
            'mapel_id' => $mapelA->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $materialDraft = LearningMaterial::factory()->draft()->create([
            'teacher_id' => $guruAuthor->id,
            'mapel_id' => $mapelA->id,
            'academic_year_id' => $academicYear->id,
        ]);

        // Author can view, update, delete, publish, archive
        $this->assertTrue(Gate::forUser($guruAuthor)->allows('view', $materialDraft));
        $this->assertTrue(Gate::forUser($guruAuthor)->allows('update', $materialDraft));
        $this->assertTrue(Gate::forUser($guruAuthor)->allows('delete', $materialDraft));
        $this->assertTrue(Gate::forUser($guruAuthor)->allows('publish', $materialDraft));
        $this->assertTrue(Gate::forUser($guruAuthor)->allows('archive', $materialPublished));
        $this->assertTrue(Gate::forUser($guruAuthor)->allows('uploadAttachment', $materialDraft));

        // Peer teacher of same mapel can view published, but CANNOT update or delete or view draft
        $this->assertTrue(Gate::forUser($guruPeerSameMapel)->allows('view', $materialPublished));
        $this->assertFalse(Gate::forUser($guruPeerSameMapel)->allows('view', $materialDraft));
        $this->assertFalse(Gate::forUser($guruPeerSameMapel)->allows('update', $materialPublished));
        $this->assertFalse(Gate::forUser($guruPeerSameMapel)->allows('delete', $materialPublished));

        // Teacher of different mapel cannot view draft and cannot update/delete
        $this->assertFalse(Gate::forUser($guruDifferentMapel)->allows('view', $materialDraft));
        $this->assertFalse(Gate::forUser($guruDifferentMapel)->allows('update', $materialPublished));
        $this->assertFalse(Gate::forUser($guruDifferentMapel)->allows('delete', $materialPublished));
    }

    public function test_santri_authorization_rules(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $kelasSantri = Kelas::factory()->create(['kelas' => 'VII-A']);
        $kelasOther = Kelas::factory()->create(['kelas' => 'VII-B']);

        $userSantri = User::factory()->create(['name' => 'Santriwati Maryam']);
        $userSantri->assignRole('Santri');

        $santri = Santri::factory()->create([
            'user_id' => $userSantri->id,
        ]);

        // Enroll in VII-A
        AcademicEnrollment::create([
            'santri_id' => $santri->id,
            'kelas_id' => $kelasSantri->id,
            'academic_year_id' => $academicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
        ]);

        // 1. Published material targeted to VII-A
        $matPublishedTargeted = LearningMaterial::factory()->published()->create([
            'academic_year_id' => $academicYear->id,
        ]);
        $matPublishedTargeted->targets()->attach($kelasSantri->id);

        // 2. Draft material targeted to VII-A
        $matDraftTargeted = LearningMaterial::factory()->draft()->create([
            'academic_year_id' => $academicYear->id,
        ]);
        $matDraftTargeted->targets()->attach($kelasSantri->id);

        // 3. Published material targeted to VII-B (other class)
        $matPublishedOther = LearningMaterial::factory()->published()->create([
            'academic_year_id' => $academicYear->id,
        ]);
        $matPublishedOther->targets()->attach($kelasOther->id);

        // Santri can viewAny
        $this->assertTrue(Gate::forUser($userSantri)->allows('viewAny', LearningMaterial::class));

        // Santri CAN view published targeted material
        $this->assertTrue(Gate::forUser($userSantri)->allows('view', $matPublishedTargeted));
        $this->assertTrue(Gate::forUser($userSantri)->allows('downloadFile', $matPublishedTargeted));

        // Santri CANNOT view draft even if targeted to their class
        $this->assertFalse(Gate::forUser($userSantri)->allows('view', $matDraftTargeted));

        // Santri CANNOT view published material targeted to another class
        $this->assertFalse(Gate::forUser($userSantri)->allows('view', $matPublishedOther));

        // Santri CANNOT mutate
        $this->assertFalse(Gate::forUser($userSantri)->allows('create', LearningMaterial::class));
        $this->assertFalse(Gate::forUser($userSantri)->allows('update', $matPublishedTargeted));
        $this->assertFalse(Gate::forUser($userSantri)->allows('delete', $matPublishedTargeted));
        $this->assertFalse(Gate::forUser($userSantri)->allows('publish', $matDraftTargeted));
        $this->assertFalse(Gate::forUser($userSantri)->allows('archive', $matPublishedTargeted));
        $this->assertFalse(Gate::forUser($userSantri)->allows('uploadAttachment', $matPublishedTargeted));
    }
}
