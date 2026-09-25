<?php

namespace Tests\Feature;

use App\Models\AlamatSantri;
use App\Models\Kamar;
use App\Models\KamarSantri;
use App\Models\Kelas;
use App\Models\KelasSantri;
use App\Models\Santri;
use App\Models\StudentBatch;
use App\Models\User;
use App\Models\WaliSantri;
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

    /**
     * Test 8: Santri KTS print renders successfully when santri has complete relations.
     */
    public function test_santri_kts_print_renders_with_complete_relations(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $user = User::factory()->create([
            'name' => 'Ahmad Zaki',
        ]);
        $user->assignRole('Santri');

        $batch = StudentBatch::create([
            'name' => 'Angkatan 2024',
            'year' => 2024,
        ]);

        $santri = Santri::factory()->create([
            'user_id' => $user->id,
            'no_induk' => '14450001',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '2008-05-15',
            'student_batch_id' => $batch->id,
        ]);

        WaliSantri::create([
            'santri_id' => $santri->id,
            'nama_ayah' => 'Bapak Mahmud',
            'nama_ibu' => 'Ibu Siti',
        ]);

        AlamatSantri::create([
            'santri_id' => $santri->id,
            'alamat_lengkap' => 'Jl. Pesantren No. 45, Sumenep',
        ]);

        $kelas = Kelas::create([
            'kode' => 'K1A',
            'kelas' => '1A',
            'tingkatan' => 'Ula',
        ]);
        KelasSantri::create([
            'santri_id' => $santri->id,
            'kelas_id' => $kelas->id,
        ]);

        $kamar = Kamar::create([
            'kode' => 'KMR-A',
            'nama' => 'Kamar Abu Bakar',
            'blok' => 'A',
            'maksimal_santri' => 10,
            'jumlah_santri' => 1,
        ]);
        KamarSantri::create([
            'santri_id' => $santri->id,
            'kamar_id' => $kamar->id,
        ]);

        $response = $this->actingAs($admin)->get(route('santri.print.kts', '14450001'));

        $response->assertOk();
        $response->assertSee('PRINT KTS - AHMAD ZAKI');
        $response->assertSee('Ahmad Zaki');
        $response->assertSee('14450001');
        $response->assertSee('Surabaya');
        $response->assertSee('Bapak Mahmud');
        $response->assertSee('Ibu Siti');
        $response->assertSee('Jl. Pesantren No. 45, Sumenep');
        $response->assertSee('data-class="Ula 1A"', false);
        $response->assertSee('data-room="Kamar Abu Bakar"', false);
        $response->assertSee('data-batch="Angkatan 2024"', false);
    }

    /**
     * Test 9: Santri KTS print renders safely when santri is missing optional relations.
     */
    public function test_santri_kts_print_renders_safely_when_optional_relations_are_missing(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $user = User::factory()->create([
            'name' => 'Santri Mandiri',
        ]);
        $user->assignRole('Santri');

        // Santri without optional relations: wali, alamat, kelas, kamar, student_batch
        $santri = Santri::factory()->create([
            'user_id' => $user->id,
            'no_induk' => '14450002',
            'tempat_lahir' => '',
            'tanggal_lahir' => '2009-01-01',
            'student_batch_id' => null,
            'foto' => 'santri.png',
        ]);

        $response = $this->actingAs($admin)->get('/print/kts/14450002');

        $response->assertOk();
        $response->assertDontSee('Attempt to read property');
        $response->assertSee('PRINT KTS - SANTRI MANDIRI');
        $response->assertSee('Santri Mandiri');
        $response->assertSee('14450002');
        $response->assertSee('data-class="-"', false);
        $response->assertSee('data-room="-"', false);
    }
}
