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
use App\Services\Lms\LearningMaterialService;
use Database\Seeders\RolePermissionSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearningMaterialServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LearningMaterialService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->service = app(LearningMaterialService::class);
        Storage::fake('local');
    }

    public function test_create_material_successfully_with_targets_and_files(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $kelas1 = Kelas::factory()->create(['kelas' => 'VII-A']);
        $kelas2 = Kelas::factory()->create(['kelas' => 'VII-B']);
        $mapel = Mapel::factory()->create(['name' => 'Fiqih']);

        $teacher = User::factory()->create(['name' => 'Ustadz Fiqih']);
        $teacher->assignRole('Guru');

        TeachingAssignment::create([
            'user_id' => $teacher->id,
            'mapel_id' => $mapel->id,
            'kelas_id' => $kelas1->id,
            'academic_year_id' => $academicYear->id,
            'status' => TeachingAssignment::STATUS_AKTIF,
        ]);

        $file = UploadedFile::fake()->create('Kitab_Fiqih.pdf', 1500, 'application/pdf');

        $data = [
            'mapel_id' => $mapel->id,
            'academic_year_id' => $academicYear->id,
            'kelas_id' => $kelas1->id,
            'title' => 'Bab Thaharah dan Wudhu',
            'description' => 'Ringkasan fiqih thaharah.',
            'content_type' => LearningMaterial::CONTENT_TYPE_PDF,
            'content' => 'Penjelasan lengkap bab wudhu.',
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ];

        $material = $this->service->createMaterial(
            $teacher,
            $data,
            [$kelas1->id, $kelas2->id],
            [$file]
        );

        $this->assertDatabaseHas('learning_materials', [
            'id' => $material->id,
            'title' => 'Bab Thaharah dan Wudhu',
            'teacher_id' => $teacher->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);

        $this->assertNotNull($material->published_at);
        $this->assertCount(2, $material->targets);
        $this->assertCount(1, $material->files);

        $storedFile = $material->files->first();
        $this->assertEquals('Kitab_Fiqih.pdf', $storedFile->file_name);
        Storage::disk('local')->assertExists($storedFile->file_path);
    }

    public function test_create_material_throws_exception_if_teacher_has_no_assignment(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $kelas = Kelas::factory()->create();
        $mapel = Mapel::factory()->create();

        $teacher = User::factory()->create();
        $teacher->assignRole('Guru');

        $data = [
            'mapel_id' => $mapel->id,
            'academic_year_id' => $academicYear->id,
            'kelas_id' => $kelas->id,
            'title' => 'Materi Tanpa SK',
        ];

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Guru tidak memiliki SK Mengajar aktif');

        $this->service->createMaterial($teacher, $data, [$kelas->id]);
    }

    public function test_admin_can_create_material_without_direct_teaching_assignment(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $kelas = Kelas::factory()->create();
        $mapel = Mapel::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $data = [
            'mapel_id' => $mapel->id,
            'academic_year_id' => $academicYear->id,
            'kelas_id' => $kelas->id,
            'title' => 'Materi Kurikulum Pondok oleh Admin',
            'status' => LearningMaterial::STATUS_DRAFT,
        ];

        $material = $this->service->createMaterial($admin, $data, [$kelas->id]);

        $this->assertDatabaseHas('learning_materials', [
            'id' => $material->id,
            'title' => 'Materi Kurikulum Pondok oleh Admin',
        ]);
    }

    public function test_update_material_updates_attributes_and_replaces_slug_if_title_changes(): void
    {
        $material = LearningMaterial::factory()->draft()->create([
            'title' => 'Judul Lama Materi',
            'slug' => 'judul-lama-materi',
        ]);

        $updated = $this->service->updateMaterial($material, [
            'title' => 'Judul Baru Materi Terupdate',
            'description' => 'Deskripsi baru yang diperbarui.',
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);

        $this->assertEquals('Judul Baru Materi Terupdate', $updated->title);
        $this->assertStringStartsWith('judul-baru-materi-terupdate', $updated->slug);
        $this->assertEquals(LearningMaterial::STATUS_PUBLISHED, $updated->status);
        $this->assertNotNull($updated->published_at);
    }

    public function test_publish_and_archive_material(): void
    {
        $material = LearningMaterial::factory()->draft()->create();

        $published = $this->service->publishMaterial($material);
        $this->assertTrue($published->isPublished());
        $this->assertNotNull($published->published_at);

        $archived = $this->service->archiveMaterial($published);
        $this->assertTrue($archived->isArchived());
    }

    public function test_delete_attachment_removes_physical_file_and_db_record(): void
    {
        $material = LearningMaterial::factory()->create();
        $file = UploadedFile::fake()->create('modul.pdf', 500);

        $savedCollection = $this->service->storeAttachments($material, [$file]);
        $attachment = $savedCollection->first();

        Storage::disk('local')->assertExists($attachment->file_path);
        $this->assertDatabaseHas('learning_material_files', ['id' => $attachment->id]);

        $deleted = $this->service->deleteAttachment($attachment);
        $this->assertTrue($deleted);

        Storage::disk('local')->assertMissing($attachment->file_path);
        $this->assertDatabaseMissing('learning_material_files', ['id' => $attachment->id]);
    }

    public function test_increment_download_count_is_atomic(): void
    {
        $material = LearningMaterial::factory()->create();
        $file = UploadedFile::fake()->create('nadhom.pdf', 300);

        $savedCollection = $this->service->storeAttachments($material, [$file]);
        $attachment = $savedCollection->first();

        $this->assertEquals(0, $attachment->download_count);

        $count1 = $this->service->incrementDownloadCount($attachment);
        $this->assertEquals(1, $count1);

        $count2 = $this->service->incrementDownloadCount($attachment);
        $this->assertEquals(2, $count2);
    }

    public function test_get_materials_for_santri_returns_only_published_materials_for_enrolled_class(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $kelasVIIA = Kelas::factory()->create(['kelas' => 'VII-A']);
        $kelasVIIB = Kelas::factory()->create(['kelas' => 'VII-B']);

        $userSantri = User::factory()->create(['name' => 'Ahmad Santri']);
        $userSantri->assignRole('Santri');

        $santri = Santri::factory()->create([
            'user_id' => $userSantri->id,
        ]);

        // Enroll santri in VII-A
        AcademicEnrollment::create([
            'santri_id' => $santri->id,
            'kelas_id' => $kelasVIIA->id,
            'academic_year_id' => $academicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
        ]);

        $mapelFiqih = Mapel::factory()->create(['name' => 'Fiqih']);
        $mapelNahwu = Mapel::factory()->create(['name' => 'Nahwu']);

        // 1. Published for VII-A (Should see)
        $mat1 = LearningMaterial::factory()->published()->create([
            'academic_year_id' => $academicYear->id,
            'mapel_id' => $mapelFiqih->id,
            'title' => 'Materi Fiqih VII-A',
        ]);
        $mat1->targets()->attach($kelasVIIA->id);

        // 2. Draft for VII-A (Should NOT see)
        $mat2 = LearningMaterial::factory()->draft()->create([
            'academic_year_id' => $academicYear->id,
            'mapel_id' => $mapelFiqih->id,
            'title' => 'Materi Draf VII-A',
        ]);
        $mat2->targets()->attach($kelasVIIA->id);

        // 3. Published for VII-B only (Should NOT see)
        $mat3 = LearningMaterial::factory()->published()->create([
            'academic_year_id' => $academicYear->id,
            'mapel_id' => $mapelNahwu->id,
            'title' => 'Materi Nahwu VII-B',
        ]);
        $mat3->targets()->attach($kelasVIIB->id);

        // Fetch materials for Santri
        $paginator = $this->service->getMaterialsForSantri($userSantri, $academicYear);

        $this->assertEquals(1, $paginator->total());
        $this->assertTrue($paginator->items()[0]->is($mat1));

        // Test filter by mapel
        $emptyPaginator = $this->service->getMaterialsForSantri($userSantri, $academicYear, [
            'mapel_id' => $mapelNahwu->id,
        ]);
        $this->assertEquals(0, $emptyPaginator->total());

        // Test search filter
        $searchPaginator = $this->service->getMaterialsForSantri($userSantri, $academicYear, [
            'search' => 'Thaharah',
        ]);
        $this->assertEquals(0, $searchPaginator->total());

        $foundPaginator = $this->service->getMaterialsForSantri($userSantri, $academicYear, [
            'search' => 'Fiqih VII-A',
        ]);
        $this->assertEquals(1, $foundPaginator->total());
    }

    public function test_get_materials_for_teacher_returns_materials_by_teacher(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $matA1 = LearningMaterial::factory()->create([
            'teacher_id' => $teacherA->id,
            'academic_year_id' => $academicYear->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);

        $matA2 = LearningMaterial::factory()->create([
            'teacher_id' => $teacherA->id,
            'academic_year_id' => $academicYear->id,
            'status' => LearningMaterial::STATUS_DRAFT,
        ]);

        $matB = LearningMaterial::factory()->create([
            'teacher_id' => $teacherB->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $materials = $this->service->getMaterialsForTeacher($teacherA, $academicYear->id);

        $this->assertCount(2, $materials);
        $this->assertTrue($materials->contains($matA1));
        $this->assertTrue($materials->contains($matA2));
        $this->assertFalse($materials->contains($matB));

        // Filter by draft status
        $drafts = $this->service->getMaterialsForTeacher($teacherA, $academicYear->id, [
            'status' => LearningMaterial::STATUS_DRAFT,
        ]);
        $this->assertCount(1, $drafts);
        $this->assertTrue($drafts->first()->is($matA2));
    }

    public function test_delete_and_restore_material_lifecycle(): void
    {
        $material = LearningMaterial::factory()->create();

        // 1. Soft delete
        $deleted = $this->service->deleteMaterial($material);
        $this->assertTrue($deleted);
        $this->assertSoftDeleted('learning_materials', ['id' => $material->id]);

        // 2. Restore
        $restored = $this->service->restoreMaterial($material->id);
        $this->assertFalse($restored->trashed());
        $this->assertDatabaseHas('learning_materials', [
            'id' => $material->id,
            'deleted_at' => null,
        ]);
    }

    public function test_force_delete_cleans_up_files_and_targets(): void
    {
        $material = LearningMaterial::factory()->create();
        $kelas = Kelas::factory()->create();
        $material->targets()->attach($kelas->id);

        $file = UploadedFile::fake()->create('temporary.pdf', 200);
        $this->service->storeAttachments($material, [$file]);

        $attachment = $material->fresh()->files->first();
        Storage::disk('local')->assertExists($attachment->file_path);

        // Force delete
        $result = $this->service->deleteMaterial($material, force: true);
        $this->assertTrue($result);

        $this->assertDatabaseMissing('learning_materials', ['id' => $material->id]);
        $this->assertDatabaseMissing('learning_material_targets', ['learning_material_id' => $material->id]);
        $this->assertDatabaseMissing('learning_material_files', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($attachment->file_path);
    }

    public function test_update_material_removes_specified_obsolete_files(): void
    {
        $material = LearningMaterial::factory()->create();
        $file1 = UploadedFile::fake()->create('old_notes.pdf', 300);
        $file2 = UploadedFile::fake()->create('keep_notes.pdf', 400);

        $attachments = $this->service->storeAttachments($material, [$file1, $file2]);
        $oldFile = $attachments->firstWhere('file_name', 'old_notes.pdf');
        $keepFile = $attachments->firstWhere('file_name', 'keep_notes.pdf');

        Storage::disk('local')->assertExists($oldFile->file_path);
        Storage::disk('local')->assertExists($keepFile->file_path);

        $this->service->updateMaterial($material, [
            'remove_file_ids' => [$oldFile->id],
        ]);

        Storage::disk('local')->assertMissing($oldFile->file_path);
        $this->assertDatabaseMissing('learning_material_files', ['id' => $oldFile->id]);

        Storage::disk('local')->assertExists($keepFile->file_path);
        $this->assertDatabaseHas('learning_material_files', ['id' => $keepFile->id]);
    }
}
