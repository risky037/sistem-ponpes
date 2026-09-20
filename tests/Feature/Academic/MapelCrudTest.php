<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MapelCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $santriUser;

    protected Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        $santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Mapel',
            'email' => 'admin.mapel@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->pengurus = User::create([
            'name' => 'Pengurus Mapel',
            'email' => 'pengurus.mapel@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->pengurus->assignRole($pengurusRole);

        $this->santriUser = User::create([
            'name' => 'Santri Biasa',
            'email' => 'santri.mapel@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->santriUser->assignRole($santriRole);

        $this->kelas = Kelas::create([
            'kode' => 'KLS-ALF01',
            'tingkatan' => 'ALFIYAH',
            'kelas' => 'Alfiyah Ula',
        ]);
    }

    public function test_mapel_index_renders_for_authorized_users(): void
    {
        Mapel::create([
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-01',
            'name' => 'Nahwu Jurumiyah',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('mapel.index'));
        $response->assertStatus(200);
        $response->assertSee('Mata Pelajaran');

        $response = $this->actingAs($this->pengurus)->get(route('mapel.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->santriUser)->get(route('mapel.index'));
        $response->assertStatus(403);
    }

    public function test_mapel_datatable_ajax_returns_json(): void
    {
        Mapel::create([
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-02',
            'name' => 'Shorof Kailani',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('mapel.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'recordsTotal']);
        $this->assertStringContainsString('Shorof Kailani', $response->getContent());
    }

    public function test_mapel_store_creates_new_subject(): void
    {
        $response = $this->actingAs($this->admin)->post(route('mapel.store'), [
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-03',
            'name' => 'Fathul Qorib',
            'description' => 'Fiqih Syafi\'i',
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('mapels', [
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-03',
            'name' => 'Fathul Qorib',
            'is_active' => true,
        ]);
    }

    public function test_mapel_duplicate_code_is_rejected(): void
    {
        Mapel::create([
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-DUP',
            'name' => 'Mata Pelajaran A',
        ]);

        $response = $this->actingAs($this->admin)->post(route('mapel.store'), [
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-DUP',
            'name' => 'Mata Pelajaran B',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_mapel_duplicate_name_in_same_class_is_rejected(): void
    {
        Mapel::create([
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-SAME1',
            'name' => 'Tafsir Jalalain',
        ]);

        $response = $this->actingAs($this->admin)->post(route('mapel.store'), [
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-SAME2',
            'name' => 'Tafsir Jalalain',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_mapel_update_works(): void
    {
        $mapel = Mapel::create([
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-UPD',
            'name' => 'Nama Lama',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('mapel.update', $mapel->id), [
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-UPD',
            'name' => 'Nama Baru',
            'description' => 'Deskripsi baru',
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('mapels', [
            'id' => $mapel->id,
            'name' => 'Nama Baru',
        ]);
    }

    public function test_mapel_destroy_blocked_when_teaching_assignment_exists(): void
    {
        $year = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-15',
            'end_date' => '2024-12-20',
            'is_active' => true,
        ]);

        $mapel = Mapel::create([
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-GUARD',
            'name' => 'Mapel Terikat Pengajar',
            'is_active' => true,
        ]);

        TeachingAssignment::create([
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $mapel->id,
            'user_id' => $this->pengurus->id,
            'academic_year_id' => $year->id,
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('mapel.destroy', $mapel->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('mapels', ['id' => $mapel->id]);
    }

    public function test_kelas_cannot_be_deleted_when_mapel_exists(): void
    {
        Mapel::create([
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-KLSDEL',
            'name' => 'Mapel Pengikat Kelas',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('kelas.destroy', $this->kelas->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('kelas', ['id' => $this->kelas->id]);
    }
}
