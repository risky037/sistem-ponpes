<?php

namespace Tests\Feature\Lms;

use App\Http\Requests\Lms\StoreLearningMaterialRequest;
use App\Http\Requests\Lms\UpdateLearningMaterialRequest;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LearningMaterialRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_store_request_validates_required_fields_and_formats(): void
    {
        $mapel = Mapel::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $kelas = Kelas::factory()->create();

        $rules = (new StoreLearningMaterialRequest)->rules();

        // 1. Empty data fails
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('mapel_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('academic_year_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('content_type', $validator->errors()->toArray());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());

        // 2. Valid data passes
        $validData = [
            'title' => 'Pengenalan Bahasa Arab',
            'mapel_id' => $mapel->id,
            'academic_year_id' => $academicYear->id,
            'kelas_id' => $kelas->id,
            'content_type' => 'pdf',
            'status' => 'draft',
            'files' => [
                UploadedFile::fake()->create('modul.pdf', 500, 'application/pdf'),
                UploadedFile::fake()->create('rekap.xlsx', 300, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ],
        ];

        $validatorPass = Validator::make($validData, $rules);
        $this->assertFalse($validatorPass->fails());
    }

    public function test_store_request_rejects_unsupported_file_types_and_oversized_files(): void
    {
        $rules = (new StoreLearningMaterialRequest)->rules();

        // Unsupported extension (.exe)
        $invalidFileType = [
            'title' => 'Materi Uji',
            'mapel_id' => 1,
            'academic_year_id' => 1,
            'content_type' => 'text',
            'status' => 'draft',
            'files' => [
                UploadedFile::fake()->create('malicious.exe', 100),
            ],
        ];

        $validator = Validator::make($invalidFileType, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('files.0', $validator->errors()->toArray());

        // Oversized file (> 25MB = 25600KB)
        $oversized = [
            'title' => 'Materi Besar',
            'mapel_id' => 1,
            'academic_year_id' => 1,
            'content_type' => 'pdf',
            'status' => 'draft',
            'files' => [
                UploadedFile::fake()->create('too_large.pdf', 30000, 'application/pdf'),
            ],
        ];

        $validatorSize = Validator::make($oversized, $rules);
        $this->assertTrue($validatorSize->fails());
        $this->assertArrayHasKey('files.0', $validatorSize->errors()->toArray());
    }

    public function test_update_request_permits_partial_payload(): void
    {
        $rules = (new UpdateLearningMaterialRequest)->rules();

        // Partial update with only description
        $partial = [
            'description' => 'Deskripsi materi diperbarui secara parsial.',
        ];

        $validator = Validator::make($partial, $rules);
        $this->assertFalse($validator->fails());

        // Invalid content_type in update
        $invalidType = [
            'content_type' => 'invalid_type_here',
        ];

        $validatorInvalid = Validator::make($invalidType, $rules);
        $this->assertTrue($validatorInvalid->fails());
        $this->assertArrayHasKey('content_type', $validatorInvalid->errors()->toArray());
    }

    public function test_store_request_authorization_integration(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Guru');

        $mapel = Mapel::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $kelas = Kelas::factory()->create();

        // Without SK assignment, cannot authorize
        $request = new StoreLearningMaterialRequest;
        $request->setUserResolver(fn () => $teacher);
        $this->assertFalse($request->authorize());

        // With SK assignment, authorized
        TeachingAssignment::create([
            'user_id' => $teacher->id,
            'mapel_id' => $mapel->id,
            'kelas_id' => $kelas->id,
            'academic_year_id' => $academicYear->id,
            'status' => TeachingAssignment::STATUS_AKTIF,
        ]);

        $this->assertTrue($request->authorize());
    }
}
