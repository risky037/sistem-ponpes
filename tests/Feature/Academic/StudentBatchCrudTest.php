<?php

namespace Tests\Feature\Academic;

use App\Models\Santri;
use App\Models\StudentBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentBatchCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $santriUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        $santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Akademik',
            'email' => 'admin.batch@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->pengurus = User::create([
            'name' => 'Pengurus Akademik',
            'email' => 'pengurus.batch@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->pengurus->assignRole($pengurusRole);

        $this->santriUser = User::create([
            'name' => 'Santri User',
            'email' => 'santri.user@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->santriUser->assignRole($santriRole);
    }

    public function test_student_batch_index_renders_for_authorized_users(): void
    {
        StudentBatch::create([
            'name' => 'Angkatan 2025',
            'year' => 2025,
            'description' => 'Santri masuk 2025',
        ]);

        $response = $this->actingAs($this->admin)->get(route('student-batch.index'));
        $response->assertStatus(200);
        $response->assertSee('Angkatan Santri');

        $ajaxResponse = $this->actingAs($this->admin)->getJson(route('student-batch.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJsonStructure(['data']);
        $ajaxResponse->assertJsonFragment(['name' => 'Angkatan 2025']);
    }

    public function test_unauthorized_user_cannot_access_student_batch(): void
    {
        $response = $this->actingAs($this->santriUser)->get(route('student-batch.index'));
        $response->assertStatus(403);
    }

    public function test_student_batch_can_be_stored(): void
    {
        $payload = [
            'name' => 'Angkatan 2026',
            'year' => 2026,
            'description' => 'Penerimaan santri baru 2026',
        ];

        $response = $this->actingAs($this->admin)->post(route('student-batch.store'), $payload);
        $response->assertRedirect(route('student-batch.index'));

        $this->assertDatabaseHas('student_batches', [
            'name' => 'Angkatan 2026',
            'year' => 2026,
        ]);
    }

    public function test_student_batch_store_validates_unique_year(): void
    {
        StudentBatch::create([
            'name' => 'Angkatan 2026 Lama',
            'year' => 2026,
        ]);

        $response = $this->actingAs($this->admin)->post(route('student-batch.store'), [
            'name' => 'Angkatan 2026 Baru',
            'year' => 2026,
        ]);

        $response->assertSessionHasErrors(['year']);
    }

    public function test_student_batch_can_be_updated(): void
    {
        $batch = StudentBatch::create([
            'name' => 'Angkatan 2025',
            'year' => 2025,
            'description' => 'Catatan awal',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('student-batch.update', $batch), [
            'name' => 'Angkatan 2025 Revisi',
            'year' => 2025,
            'description' => 'Catatan diperbarui',
        ]);

        $response->assertRedirect(route('student-batch.index'));
        $this->assertDatabaseHas('student_batches', [
            'id' => $batch->id,
            'name' => 'Angkatan 2025 Revisi',
        ]);
    }

    public function test_student_batch_deletion_blocked_when_santri_exists(): void
    {
        $batch = StudentBatch::create([
            'name' => 'Angkatan 2026',
            'year' => 2026,
        ]);

        $santriUser = User::create([
            'name' => 'Santri Angkatan',
            'email' => 'santri.angkatan@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        Santri::create([
            'no_induk' => '20269999',
            'user_id' => $santriUser->id,
            'student_batch_id' => $batch->id,
            'jenis_kelamin' => 'Laki-Laki',
            'nik' => '3578012345670099',
            'kk' => '3578012345670099',
            'whatsapp' => '081234567899',
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Kediri',
            'tahun_masuk' => '2026-07-01',
            'tahun_masuk_hijriyah' => '1447',
            'status' => 'Santri Aktif',
            'foto' => 'santri.png',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('student-batch.destroy', $batch));
        $response->assertRedirect(route('student-batch.index'));

        // Batch is still in database
        $this->assertDatabaseHas('student_batches', ['id' => $batch->id]);
    }

    public function test_student_batch_can_be_deleted_when_empty(): void
    {
        $batch = StudentBatch::create([
            'name' => 'Angkatan 2028',
            'year' => 2028,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('student-batch.destroy', $batch));
        $response->assertRedirect(route('student-batch.index'));

        $this->assertDatabaseMissing('student_batches', ['id' => $batch->id]);
    }
}
