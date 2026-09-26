<?php

namespace Tests\Feature\Lms;

use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialFile;
use App\Models\Mapel;
use App\Models\User;
use Database\Seeders\LearningMaterialSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningMaterialDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_material_can_belong_to_teacher(): void
    {
        $teacher = User::factory()->create(['name' => 'Ustadz Ahmad']);
        $teacher->assignRole('Guru');

        $material = LearningMaterial::factory()->create([
            'teacher_id' => $teacher->id,
            'created_by' => $teacher->id,
        ]);

        $this->assertInstanceOf(User::class, $material->teacher);
        $this->assertEquals($teacher->id, $material->teacher->id);
        $this->assertEquals('Ustadz Ahmad', $material->teacher->name);

        // Reverse relationship on User
        $this->assertTrue($teacher->learningMaterials->contains($material));
    }

    public function test_material_belongs_to_mapel(): void
    {
        $mapel = Mapel::factory()->create(['name' => 'Kajian Fathul Qorib']);

        $material = LearningMaterial::factory()->create([
            'mapel_id' => $mapel->id,
        ]);

        $this->assertInstanceOf(Mapel::class, $material->mapel);
        $this->assertEquals($mapel->id, $material->mapel->id);
        $this->assertEquals('Kajian Fathul Qorib', $material->mapel->name);

        // Reverse relationship on Mapel
        $this->assertTrue($mapel->learningMaterials->contains($material));
    }

    public function test_material_can_target_multiple_classes(): void
    {
        $material = LearningMaterial::factory()->create(['title' => 'Materi Fiqih Multi Kelas']);
        $kelasA = Kelas::factory()->create(['kelas' => 'VII-A']);
        $kelasB = Kelas::factory()->create(['kelas' => 'VII-B']);

        $material->targets()->attach([$kelasA->id, $kelasB->id]);

        $this->assertCount(2, $material->fresh()->targets);
        $this->assertTrue($material->targets->contains($kelasA));
        $this->assertTrue($material->targets->contains($kelasB));

        // Reverse relationship on Kelas
        $this->assertTrue($kelasA->targetedLearningMaterials->contains($material));
        $this->assertTrue($kelasB->targetedLearningMaterials->contains($material));
    }

    public function test_target_pivot_enforces_unique_constraint(): void
    {
        $material = LearningMaterial::factory()->create();
        $kelas = Kelas::factory()->create();

        $material->targets()->attach($kelas->id);

        $this->expectException(QueryException::class);
        $material->targets()->attach($kelas->id);
    }

    public function test_material_can_have_multiple_files_with_human_file_size(): void
    {
        $material = LearningMaterial::factory()->create();

        $file1 = LearningMaterialFile::create([
            'learning_material_id' => $material->id,
            'file_name' => 'Bab_Thaharah.pdf',
            'file_path' => 'materials/test/bab1.pdf',
            'file_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'file_size' => 2097152, // 2 MB
            'download_count' => 5,
        ]);

        $file2 = LearningMaterialFile::create([
            'learning_material_id' => $material->id,
            'file_name' => 'Ringkasan_Nadhom.docx',
            'file_path' => 'materials/test/nadhom.docx',
            'file_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_extension' => 'docx',
            'file_size' => 512000, // 500 KB
            'download_count' => 0,
        ]);

        $materialFresh = $material->fresh();
        $this->assertTrue($materialFresh->hasFiles());
        $this->assertCount(2, $materialFresh->files);

        // Helper and accessor tests
        $this->assertEquals('2.00 MB', $file1->getHumanFileSize());
        $this->assertEquals('2.00 MB', $file1->human_file_size);
        $this->assertEquals('500.00 KB', $file2->getHumanFileSize());
        $this->assertEquals('500.00 KB', $file2->human_file_size);

        // Parent relationship on file
        $this->assertEquals($material->id, $file1->learningMaterial->id);
    }

    public function test_soft_deletes_preserve_record_and_allow_force_delete(): void
    {
        $material = LearningMaterial::factory()->create(['title' => 'Materi Dihapus']);

        $material->delete();

        // Standard query does NOT find soft deleted material
        $this->assertNull(LearningMaterial::find($material->id));

        // withTrashed finds the soft deleted material
        $softDeleted = LearningMaterial::withTrashed()->find($material->id);
        $this->assertNotNull($softDeleted);
        $this->assertNotNull($softDeleted->deleted_at);

        // Restore works
        $softDeleted->restore();
        $this->assertNotNull(LearningMaterial::find($material->id));

        // Force delete removes record permanently
        $material->forceDelete();
        $this->assertNull(LearningMaterial::withTrashed()->find($material->id));
    }

    public function test_scopes_filter_by_status_and_class(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $kelasA = Kelas::factory()->create(['kelas' => 'VII-A']);
        $kelasB = Kelas::factory()->create(['kelas' => 'VII-B']);

        $pubA = LearningMaterial::factory()->published()->create([
            'title' => 'Materi VII-A Terbit',
            'academic_year_id' => $academicYear->id,
        ]);
        $pubA->targets()->attach($kelasA->id);

        $draftA = LearningMaterial::factory()->draft()->create([
            'title' => 'Materi VII-A Draf',
            'academic_year_id' => $academicYear->id,
        ]);
        $draftA->targets()->attach($kelasA->id);

        $pubB = LearningMaterial::factory()->published()->create([
            'title' => 'Materi VII-B Terbit',
            'academic_year_id' => $academicYear->id,
        ]);
        $pubB->targets()->attach($kelasB->id);

        // Published scope
        $published = LearningMaterial::published()->get();
        $this->assertTrue($published->contains($pubA));
        $this->assertTrue($published->contains($pubB));
        $this->assertFalse($published->contains($draftA));

        // Draft scope
        $drafts = LearningMaterial::draft()->get();
        $this->assertTrue($drafts->contains($draftA));
        $this->assertFalse($drafts->contains($pubA));

        // Scope for class VII-A
        $forKelasA = LearningMaterial::forClass($kelasA)->get();
        $this->assertTrue($forKelasA->contains($pubA));
        $this->assertTrue($forKelasA->contains($draftA));
        $this->assertFalse($forKelasA->contains($pubB));

        // Combined scope: published for class VII-A
        $pubKelasA = LearningMaterial::published()->forClass($kelasA)->get();
        $this->assertTrue($pubKelasA->contains($pubA));
        $this->assertFalse($pubKelasA->contains($draftA));
        $this->assertFalse($pubKelasA->contains($pubB));
    }

    public function test_learning_material_seeder_populates_demo_records(): void
    {
        $this->seed(LearningMaterialSeeder::class);

        $materials = LearningMaterial::all();
        $this->assertGreaterThanOrEqual(4, $materials->count());

        $fiqihMat = LearningMaterial::where('slug', 'bab-thaharah-wudhu-mandi-wajib-dan-tayammum')->first();
        $this->assertNotNull($fiqihMat);
        $this->assertTrue($fiqihMat->isPublished());
        $this->assertTrue($fiqihMat->hasFiles());
        $this->assertGreaterThanOrEqual(1, $fiqihMat->targets->count());

        $draftMat = LearningMaterial::where('slug', 'bab-sholat-berjamaah-dan-ketentuan-makmum-masbuq')->first();
        $this->assertNotNull($draftMat);
        $this->assertTrue($draftMat->isDraft());
        $this->assertFalse($draftMat->isPublished());
    }
}
