<?php

namespace Tests\Feature\Image;

use App\Models\AlamatSantri;
use App\Models\Kabupaten;
use App\Models\Kamar;
use App\Models\Kecamatan;
use App\Models\Kelas;
use App\Models\Kelurahan;
use App\Models\Provinsi;
use App\Models\Santri;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImageProcessingSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Kelas $kelas;

    protected Kamar $kamar;

    protected Provinsi $provinsi;

    protected Kabupaten $kabupaten;

    protected Kecamatan $kecamatan;

    protected Kelurahan $kelurahan;

    protected Setting $setting;

    /**
     * Track files created during testing for clean teardown.
     *
     * @var array<string>
     */
    protected array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->setting = Setting::firstOrCreate([
            'log_activity' => false,
        ]);

        WhatsappMessage::create([
            'pesan_tarik_tunai' => 'Pesan tarik tunai',
            'pesan_setor_tunai' => 'Pesan setor tunai',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Image Test',
            'email' => 'admin_image@example.com',
            'password' => Hash::make('AdminSecret123!'),
        ]);
        $this->admin->assignRole('Administrator');

        $this->kelas = Kelas::create([
            'kode' => 'K1A',
            'kelas' => '1A',
            'tingkatan' => 'Ula',
        ]);

        $this->kamar = Kamar::create([
            'kode' => 'KMR-A',
            'nama' => 'Kamar Al-Fatihah',
            'blok' => 'A',
            'maksimal_santri' => 10,
            'jumlah_santri' => 0,
        ]);

        $this->provinsi = Provinsi::create(['name' => 'Jawa Timur']);
        $this->kabupaten = Kabupaten::create([
            'provinsi_id' => $this->provinsi->id,
            'name' => 'Surabaya',
        ]);
        $this->kecamatan = Kecamatan::create([
            'kabupaten_id' => $this->kabupaten->id,
            'name' => 'Wonokromo',
        ]);
        $this->kelurahan = Kelurahan::create([
            'kecamatan_id' => $this->kecamatan->id,
            'name' => 'Darmo',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $filePath) {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        parent::tearDown();
    }

    /**
     * Helper to track and register files for cleanup.
     */
    protected function registerForCleanup(string $filePath): void
    {
        $this->createdFiles[] = $filePath;
    }

    /**
     * 1. Verify upload succeeds, file exists, width <= 240, height <= 295.
     */
    public function test_santri_photo_upload_processes_image(): void
    {
        $photo = UploadedFile::fake()->image('large_santri.jpg', 600, 800);

        $response = $this->actingAs($this->admin)->post(route('santri.store'), [
            'kelas' => $this->kelas->id,
            'kamar' => $this->kamar->id,
            'nama_lengkap' => 'Ahmad Fulan',
            'provinsi_id' => $this->provinsi->id,
            'kabupaten_id' => $this->kabupaten->id,
            'kecamatan_id' => $this->kecamatan->id,
            'kelurahan_id' => $this->kelurahan->id,
            'dusun' => 'Dusun 1',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => '2005-01-01',
            'tempat_lahir' => 'Surabaya',
            'tahun_masuk' => '2023-07-01',
            'nama_ayah' => 'Ayah Fulan',
            'nama_ibu' => 'Ibu Fulan',
            'nik' => '3578012345670001',
            'kk' => '3578012345670002',
            'foto' => $photo,
        ]);

        $response->assertRedirect();

        $user = User::where('name', 'Ahmad Fulan')->first();
        $this->assertNotNull($user);

        $santri = $user->santri;
        $this->assertNotNull($santri);
        $this->assertNotEquals('santri.png', $santri->foto);

        $savedPath = storage_path('app/public/uploads/santri/'.$santri->foto);
        $this->registerForCleanup($savedPath);

        $this->assertFileExists($savedPath);

        [$width, $height] = getimagesize($savedPath);
        $this->assertLessThanOrEqual(240, $width);
        $this->assertLessThanOrEqual(295, $height);
    }

    /**
     * 2. Verify input 150x150 is not upscaled (scaleDown preserves dimensions).
     */
    public function test_small_image_is_not_upscaled(): void
    {
        $photo = UploadedFile::fake()->image('small_santri.jpg', 150, 150);

        $response = $this->actingAs($this->admin)->post(route('santri.store'), [
            'kelas' => $this->kelas->id,
            'kamar' => $this->kamar->id,
            'nama_lengkap' => 'Budi Santoso',
            'provinsi_id' => $this->provinsi->id,
            'kabupaten_id' => $this->kabupaten->id,
            'kecamatan_id' => $this->kecamatan->id,
            'kelurahan_id' => $this->kelurahan->id,
            'dusun' => 'Dusun 2',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567891',
            'tanggal_lahir' => '2005-02-02',
            'tempat_lahir' => 'Surabaya',
            'tahun_masuk' => '2023-07-01',
            'nama_ayah' => 'Ayah Budi',
            'nama_ibu' => 'Ibu Budi',
            'nik' => '3578012345670003',
            'kk' => '3578012345670004',
            'foto' => $photo,
        ]);

        $response->assertRedirect();

        $user = User::where('name', 'Budi Santoso')->first();
        $this->assertNotNull($user);

        $santri = $user->santri;
        $this->assertNotNull($santri);

        $savedPath = storage_path('app/public/uploads/santri/'.$santri->foto);
        $this->registerForCleanup($savedPath);

        $this->assertFileExists($savedPath);

        [$width, $height] = getimagesize($savedPath);
        $this->assertEquals(150, $width);
        $this->assertEquals(150, $height);
    }

    /**
     * 3. Verify updating santri photo processes new photo and saves it.
     */
    public function test_santri_photo_update_replaces_old_file(): void
    {
        $initialUser = User::factory()->create([
            'name' => 'Santri Update Target',
            'email' => 'santri_target@example.com',
            'password' => Hash::make('password'),
        ]);
        $initialUser->assignRole('Santri');

        $santri = Santri::create([
            'no_induk' => '20239999',
            'user_id' => $initialUser->id,
            'jenis_kelamin' => 'Laki-Laki',
            'nik' => '3578012345679999',
            'kk' => '3578012345679998',
            'whatsapp' => '081234567999',
            'tanggal_lahir' => '2005-03-03',
            'tempat_lahir' => 'Surabaya',
            'tahun_masuk' => '2023-07-01',
            'tahun_masuk_hijriyah' => '1444',
            'status' => 'Santri Aktif',
            'maksimal_perizinan' => 10,
            'foto' => 'santri.png',
        ]);

        $newPhoto = UploadedFile::fake()->image('updated_photo.png', 500, 600);

        $response = $this->actingAs($this->admin)->patch(route('santri.update', $santri->id), [
            'kelas' => $this->kelas->id,
            'kamar' => $this->kamar->id,
            'nama_lengkap' => 'Santri Update Target',
            'provinsi_id' => $this->provinsi->id,
            'kabupaten_id' => $this->kabupaten->id,
            'kecamatan_id' => $this->kecamatan->id,
            'kelurahan_id' => $this->kelurahan->id,
            'dusun' => 'Dusun Update',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567999',
            'tanggal_lahir' => '2005-03-03',
            'tempat_lahir' => 'Surabaya',
            'tahun_masuk' => '2023-07-01',
            'nama_ayah' => 'Ayah Target',
            'nama_ibu' => 'Ibu Target',
            'nik' => '3578012345679999',
            'kk' => '3578012345679998',
            'foto' => $newPhoto,
        ]);

        $response->assertRedirect();

        $santri->refresh();
        $this->assertNotEquals('santri.png', $santri->foto);

        $newPath = storage_path('app/public/uploads/santri/'.$santri->foto);
        $this->registerForCleanup($newPath);

        $this->assertFileExists($newPath);

        [$width, $height] = getimagesize($newPath);
        $this->assertLessThanOrEqual(240, $width);
        $this->assertLessThanOrEqual(295, $height);
    }

    /**
     * 4. Verify user can update biodata with a photo upload.
     */
    public function test_profile_photo_upload(): void
    {
        $santriUser = User::factory()->create([
            'name' => 'Profil Owner User',
            'email' => 'profil_owner@example.com',
            'password' => Hash::make('Password123!'),
        ]);
        $santriUser->assignRole('Santri');

        $santri = Santri::create([
            'no_induk' => '20238888',
            'user_id' => $santriUser->id,
            'jenis_kelamin' => 'Laki-Laki',
            'nik' => '3578012345678888',
            'kk' => '3578012345678887',
            'whatsapp' => '081234567888',
            'tanggal_lahir' => '2005-04-04',
            'tempat_lahir' => 'Surabaya',
            'tahun_masuk' => '2023-07-01',
            'tahun_masuk_hijriyah' => '1444',
            'status' => 'Santri Aktif',
            'maksimal_perizinan' => 10,
            'foto' => 'santri.png',
        ]);

        AlamatSantri::create([
            'santri_id' => $santri->id,
            'provinsi_id' => $this->provinsi->id,
            'kabupaten_id' => $this->kabupaten->id,
            'kecamatan_id' => $this->kecamatan->id,
            'kelurahan_id' => $this->kelurahan->id,
            'dusun' => 'Dusun Profil',
        ]);

        $profilePhoto = UploadedFile::fake()->image('profile.jpg', 300, 400);

        $response = $this->actingAs($santriUser)->post(route('profil.biodata', $santriUser->id), [
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '2005-04-04',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567888',
            'provinsi_id' => $this->provinsi->id,
            'kabupaten_id' => $this->kabupaten->id,
            'kecamatan_id' => $this->kecamatan->id,
            'kelurahan_id' => $this->kelurahan->id,
            'dusun' => 'Dusun Profil',
            'nik' => '3578012345678888',
            'kk' => '3578012345678887',
            'foto' => $profilePhoto,
        ]);

        $response->assertRedirect();

        $santri->refresh();
        $this->assertNotEquals('santri.png', $santri->foto);

        $savedPath = storage_path('app/public/uploads/santri/'.$santri->foto);
        $this->registerForCleanup($savedPath);

        $this->assertFileExists($savedPath);

        [$width, $height] = getimagesize($savedPath);
        $this->assertLessThanOrEqual(240, $width);
        $this->assertLessThanOrEqual(295, $height);
    }

    /**
     * 5. Verify setting image uploads (logo, favicon, kts master).
     */
    public function test_setting_image_uploads(): void
    {
        $logo = UploadedFile::fake()->image('logo.png', 400, 400);
        $favicon = UploadedFile::fake()->image('favicon.png', 64, 64);
        $ktsMaster = UploadedFile::fake()->image('kts_master.png', 500, 700);

        $response = $this->actingAs($this->admin)->patch(route('setting.update', $this->setting->id), [
            'whatsapp_api_key' => str_repeat('a', 32),
            'whatsapp_feature' => [1],
            'sender' => '081234567890',
            'log_activity' => '1',
            'logo' => $logo,
            'favicon' => $favicon,
            'kts_master' => $ktsMaster,
        ]);

        $response->assertRedirect();

        $this->setting->refresh();

        $logoPath = storage_path('app/public/uploads/setting/'.$this->setting->logo);
        $faviconPath = storage_path('app/public/uploads/setting/'.$this->setting->favicon);
        $ktsPath = storage_path('app/public/uploads/setting/'.$this->setting->kts_master);

        $this->registerForCleanup($logoPath);
        $this->registerForCleanup($faviconPath);
        $this->registerForCleanup($ktsPath);

        $this->assertFileExists($logoPath);
        $this->assertFileExists($faviconPath);
        $this->assertFileExists($ktsPath);

        [$logoW, $logoH] = getimagesize($logoPath);
        [$favW, $favH] = getimagesize($faviconPath);
        [$ktsW, $ktsH] = getimagesize($ktsPath);

        $this->assertLessThanOrEqual(240, $logoW);
        $this->assertLessThanOrEqual(295, $logoH);

        // Favicon 64x64 is smaller than 240x295 so scaleDown keeps it 64x64
        $this->assertEquals(64, $favW);
        $this->assertEquals(64, $favH);

        $this->assertLessThanOrEqual(240, $ktsW);
        $this->assertLessThanOrEqual(295, $ktsH);
    }

    /**
     * 6. Verify sync API photo upload works and resizes to max 400x400.
     */
    public function test_sync_api_photo_upload(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $photo = UploadedFile::fake()->image('sync_photo.jpg', 600, 600);

        $payload = [
            'kelas' => $this->kelas->id,
            'kamar' => $this->kamar->id,
            'nama_lengkap' => 'Sync Photo Santri',
            'dusun' => 'Dusun Sync',
            'desa' => 'Darmo',
            'kecamatan' => 'Wonokromo',
            'kabupaten' => 'Surabaya',
            'jenis_kelamin' => 'Laki-Laki',
            'nik' => '3578012345675555',
            'kk' => '3578012345675554',
            'whatsapp' => '081234567555',
            'tanggal_lahir' => 15,
            'bulan_lahir' => 6,
            'tahun_lahir' => 2005,
            'tempat_lahir' => 'Surabaya',
            'tahun_masuk' => '2023-07-01',
            'nama_ayah' => 'Ayah Sync',
            'nama_ibu' => 'Ibu Sync',
            'foto' => $photo,
        ];

        $response = $this->postJson('/v1/sync/santri', $payload);

        $response->assertStatus(201);

        $user = User::where('name', 'Sync Photo Santri')->first();
        $this->assertNotNull($user);

        $santri = $user->santri;
        $this->assertNotNull($santri);
        $this->assertNotEquals('santri.png', $santri->foto);

        $savedPath = storage_path('app/public/uploads/santri/'.$santri->foto);
        $this->registerForCleanup($savedPath);

        $this->assertFileExists($savedPath);

        [$width, $height] = getimagesize($savedPath);
        $this->assertLessThanOrEqual(400, $width);
        $this->assertLessThanOrEqual(400, $height);
    }

    /**
     * 7. Verify invalid image upload rejected with validation error (422 / session errors).
     */
    public function test_invalid_image_upload_rejected(): void
    {
        // 7a. Web form: rejected with validation error redirect
        $invalidWebFile = UploadedFile::fake()->create('fake_document.pdf', 500, 'application/pdf');

        $webResponse = $this->actingAs($this->admin)->post(route('santri.store'), [
            'kelas' => $this->kelas->id,
            'kamar' => $this->kamar->id,
            'nama_lengkap' => 'Invalid Image Santri',
            'provinsi_id' => $this->provinsi->id,
            'kabupaten_id' => $this->kabupaten->id,
            'kecamatan_id' => $this->kecamatan->id,
            'kelurahan_id' => $this->kelurahan->id,
            'dusun' => 'Dusun Invalid',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => '2005-01-01',
            'tempat_lahir' => 'Surabaya',
            'tahun_masuk' => '2023-07-01',
            'nama_ayah' => 'Ayah Invalid',
            'nama_ibu' => 'Ibu Invalid',
            'nik' => '3578012345670099',
            'kk' => '3578012345670098',
            'foto' => $invalidWebFile,
        ]);

        $webResponse->assertSessionHasErrors('foto');
        $this->assertDatabaseMissing('users', ['name' => 'Invalid Image Santri']);

        // 7b. API endpoint: rejected with 422 JSON validation error
        Sanctum::actingAs($this->admin, ['*']);

        $invalidApiFile = UploadedFile::fake()->create('script.txt', 100, 'text/plain');

        $apiResponse = $this->postJson('/v1/sync/santri', [
            'kelas' => $this->kelas->id,
            'kamar' => $this->kamar->id,
            'nama_lengkap' => 'Invalid API Santri',
            'dusun' => 'Dusun Invalid',
            'desa' => 'Darmo',
            'kecamatan' => 'Wonokromo',
            'kabupaten' => 'Surabaya',
            'jenis_kelamin' => 'Laki-Laki',
            'nik' => '3578012345677777',
            'kk' => '3578012345677776',
            'whatsapp' => '081234567777',
            'tanggal_lahir' => 10,
            'bulan_lahir' => 5,
            'tahun_lahir' => 2005,
            'tempat_lahir' => 'Surabaya',
            'tahun_masuk' => 2023,
            'nama_ayah' => 'Ayah',
            'nama_ibu' => 'Ibu',
            'foto' => $invalidApiFile,
        ]);

        $apiResponse->assertStatus(422);
        $apiResponse->assertJsonValidationErrors('foto');
    }
}
