<?php

namespace Tests\Feature;

use App\Models\AlamatSantri;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionStabilizationSprintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Keuangan', 'guard_name' => 'web']);
    }

    /**
     * Test 1: Guru without santri relation can open profile page safely without 500 error.
     */
    public function test_guru_without_santri_relation_can_open_profile_page_safely(): void
    {
        $guru = User::factory()->create([
            'name' => 'Ustadz Ahmad',
            'email' => 'guru@example.com',
        ]);
        $guru->assignRole('Guru');

        $response = $this->actingAs($guru)->get(route('profil.show', $guru));

        $response->assertOk();
        $response->assertSee('Ustadz Ahmad');
        $response->assertSee('Pengaturan Akun');
        $response->assertSee('Informasi Profil Pegawai');
        $response->assertDontSee('Attempt to read property');
    }

    /**
     * Test 2: Santri with complete profile renders correctly.
     */
    public function test_santri_with_complete_profile_renders_correctly(): void
    {
        $user = User::factory()->create([
            'name' => 'Santri Budi',
            'email' => 'budi@example.com',
        ]);
        $user->assignRole('Santri');

        $santri = Santri::factory()->create([
            'user_id' => $user->id,
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '2008-05-10',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => 6281234567890,
            'nik' => '3529012345678901',
            'kk' => '3529012345678902',
        ]);

        AlamatSantri::create([
            'santri_id' => $santri->id,
            'alamat_lengkap' => 'Jl. Pesantren No. 10',
        ]);

        $response = $this->actingAs($user)->get(route('profil.show', $user));

        $response->assertOk();
        $response->assertSee('Santri Budi');
        $response->assertSee('Surabaya');
        $response->assertSee('Pengaturan Biodata');
    }

    /**
     * Test 3: Santri can update biodata with valid webp and jpg images.
     */
    public function test_santri_can_update_biodata_with_valid_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('Santri');

        $santri = Santri::factory()->create([
            'user_id' => $user->id,
            'jenis_kelamin' => 'Laki-Laki',
        ]);

        $photo = UploadedFile::fake()->image('profile.webp', 300, 300);

        $response = $this->actingAs($user)->post(route('profil.biodata', $user), [
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '2008-01-01',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567890',
            'alamat_lengkap' => 'Jl. Bunga Melati No. 5',
            'nik' => '3529012345678901',
            'kk' => '3529012345678902',
            'foto' => $photo,
        ]);

        $response->assertRedirect();
        $santri->refresh();
        $this->assertEquals('Malang', $santri->tempat_lahir);
        $this->assertNotEquals('santri.png', $santri->foto);
        $this->assertFileExists(storage_path('app/public/uploads/santri/'.$santri->foto));

        // Clean up file generated in test
        if (file_exists(storage_path('app/public/uploads/santri/'.$santri->foto))) {
            @unlink(storage_path('app/public/uploads/santri/'.$santri->foto));
        }
        if (file_exists(public_path('uploads/santri/'.$santri->foto))) {
            @unlink(public_path('uploads/santri/'.$santri->foto));
        }
    }

    /**
     * Test 4: Profile photo upload rejects non-image files.
     */
    public function test_profile_photo_upload_rejects_non_image_files(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Santri');

        Santri::factory()->create([
            'user_id' => $user->id,
        ]);

        $fakePdf = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->post(route('profil.biodata', $user), [
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '2008-01-01',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567890',
            'alamat_lengkap' => 'Jl. Bunga Melati No. 5',
            'nik' => '3529012345678901',
            'kk' => '3529012345678902',
            'foto' => $fakePdf,
        ]);

        $response->assertSessionHasErrors(['foto']);
    }

    /**
     * Test 5: Santri DataTables server-side gender filtering.
     */
    public function test_santri_datatables_server_side_gender_filtering(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $userMale = User::factory()->create(['name' => 'Santri Putra']);
        $santriMale = Santri::factory()->create([
            'user_id' => $userMale->id,
            'jenis_kelamin' => 'Laki-Laki',
        ]);

        $userFemale = User::factory()->create(['name' => 'Santri Putri']);
        $santriFemale = Santri::factory()->create([
            'user_id' => $userFemale->id,
            'jenis_kelamin' => 'Perempuan',
        ]);

        // Filter Laki-Laki
        $responseMale = $this->actingAs($admin)->getJson(route('santri.index', [
            'jenis_kelamin' => 'Laki-Laki',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $responseMale->assertOk();
        $dataMale = $responseMale->json('data');
        $this->assertNotEmpty($dataMale);
        foreach ($dataMale as $item) {
            $this->assertEquals('Laki-Laki', $item['jenis_kelamin']);
        }

        // Filter Perempuan
        $responseFemale = $this->actingAs($admin)->getJson(route('santri.index', [
            'jenis_kelamin' => 'Perempuan',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $responseFemale->assertOk();
        $dataFemale = $responseFemale->json('data');
        $this->assertNotEmpty($dataFemale);
        foreach ($dataFemale as $item) {
            $this->assertEquals('Perempuan', $item['jenis_kelamin']);
        }
    }

    /**
     * Test 6: Resilient photo URL helper with fallbacks.
     */
    public function test_resilient_photo_url_helper_with_fallbacks(): void
    {
        $santri = new Santri([
            'foto' => 'santri.png',
        ]);
        $this->assertStringContainsString('img/santri.png', $santri->fotoUrl());

        $santriMissing = new Santri([
            'foto' => 'non_existent_file_12345.png',
        ]);
        $this->assertStringContainsString('img/santri.png', $santriMissing->fotoUrl());

        $this->assertStringContainsString('img/santri.png', Santri::getFotoUrl(null));
    }

    /**
     * Test 7: Footer branding renders original and current developer attribution.
     */
    public function test_footer_renders_original_and_current_maintainer_attribution(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Ust Dev');
        $response->assertSee('Ridhsuki');
        $response->assertSee('https://github.com/Ridhsuki');
    }
}
