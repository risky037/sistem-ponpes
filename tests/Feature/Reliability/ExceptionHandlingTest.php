<?php

namespace Tests\Feature\Reliability;

use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExceptionHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Role $adminRole;

    protected Role $pengurusRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $this->pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        Setting::firstOrCreate([
            'log_activity' => false,
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminSecret123!'),
        ]);
        $this->admin->assignRole($this->adminRole);
    }

    public function test_role_store_failure_flashes_error_notification_and_logs_structured_context(): void
    {
        $this->actingAs($this->admin);

        Log::spy();

        Event::listen('eloquent.creating: Spatie\Permission\Models\Role', function () {
            throw new \Exception('Simulated role store database failure');
        });

        $response = $this->post(route('roles.store'), [
            'name' => 'NewTestRole',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('laravel_flash_message', function ($flash) {
            return ($flash['class'] ?? null) === 'error'
                && ($flash['message'] ?? null) === 'Gagal menambah data';
        });

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return str_contains($message, 'RoleController store error')
                && array_key_exists('user_id', $context)
                && array_key_exists('request_uri', $context)
                && array_key_exists('method', $context)
                && array_key_exists('ip', $context)
                && array_key_exists('exception', $context);
        });
    }

    public function test_role_update_failure_flashes_error_notification_and_logs_structured_context(): void
    {
        $this->actingAs($this->admin);

        $testRole = Role::create(['name' => 'EditableRole', 'guard_name' => 'web']);

        Log::spy();

        Event::listen('eloquent.updating: Spatie\Permission\Models\Role', function () {
            throw new \Exception('Simulated role update database failure');
        });

        $response = $this->patch(route('roles.update', $testRole), [
            'name' => 'UpdatedRoleName',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('laravel_flash_message', function ($flash) {
            return ($flash['class'] ?? null) === 'error'
                && ($flash['message'] ?? null) === 'Gagal memperbarui data';
        });

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return str_contains($message, 'RoleController update error')
                && array_key_exists('user_id', $context)
                && array_key_exists('request_uri', $context)
                && array_key_exists('method', $context)
                && array_key_exists('ip', $context)
                && array_key_exists('exception', $context);
        });
    }

    public function test_role_destroy_failure_flashes_error_notification_and_logs_structured_context(): void
    {
        $this->actingAs($this->admin);

        $testRole = Role::create(['name' => 'DeletableRole', 'guard_name' => 'web']);

        Log::spy();

        Event::listen('eloquent.deleting: Spatie\Permission\Models\Role', function () {
            throw new \Exception('Simulated role delete database failure');
        });

        $response = $this->delete(route('roles.destroy', $testRole));

        $response->assertStatus(302);
        $response->assertSessionHas('laravel_flash_message', function ($flash) {
            return ($flash['class'] ?? null) === 'error'
                && ($flash['message'] ?? null) === 'Gagal menghapus data';
        });

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return str_contains($message, 'RoleController destroy error')
                && array_key_exists('user_id', $context)
                && array_key_exists('request_uri', $context)
                && array_key_exists('method', $context)
                && array_key_exists('ip', $context)
                && array_key_exists('exception', $context);
        });
    }

    public function test_users_destroy_failure_flashes_error_notification_and_logs_structured_context(): void
    {
        $this->actingAs($this->admin);

        $targetUser = User::create([
            'name' => 'Target User',
            'email' => 'target@example.com',
            'password' => Hash::make('password123'),
        ]);

        Log::spy();

        Event::listen('eloquent.deleting: App\Models\User', function () {
            throw new \Exception('Simulated user delete database failure');
        });

        $response = $this->delete(route('users.destroy', $targetUser));

        $response->assertStatus(302);
        $response->assertSessionHas('laravel_flash_message', function ($flash) {
            return ($flash['class'] ?? null) === 'error'
                && ($flash['message'] ?? null) === 'Gagal menghapus data';
        });

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return str_contains($message, 'UsersController destroy error')
                && array_key_exists('user_id', $context)
                && array_key_exists('request_uri', $context)
                && array_key_exists('method', $context)
                && array_key_exists('ip', $context)
                && array_key_exists('exception', $context);
        });
    }

    public function test_sync_api_store_kelas_does_not_leak_exception_message(): void
    {
        $pengurusUser = User::create([
            'name' => 'Pengurus Sync User',
            'email' => 'pengurus.sync@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $pengurusUser->assignRole($this->pengurusRole);

        Sanctum::actingAs($pengurusUser, ['*']);

        Event::listen('eloquent.creating: App\Models\Kelas', function () {
            throw new \Exception('SENSITIVE_SQLSTATE_ERROR_TABLE_FAIL');
        });

        $response = $this->postJson('/v1/sync/kelas', [
            'tingkatan' => 'Aliyah',
            'kelas' => 'X-IPA',
            'kode' => 'KLS-998877',
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'status' => false,
            'message' => 'Internal server error',
            'errors' => [
                'server' => ['Internal server error'],
            ],
        ]);

        // Verify that internal exception detail was never leaked to client
        $response->assertDontSee('SENSITIVE_SQLSTATE_ERROR_TABLE_FAIL');
    }

    public function test_sync_api_store_santri_does_not_leak_exception_message(): void
    {
        $pengurusUser = User::create([
            'name' => 'Pengurus Santri Sync',
            'email' => 'pengurus.santri@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $pengurusUser->assignRole($this->pengurusRole);

        $kamar = Kamar::create([
            'nama' => 'Kamar Sync Test',
            'blok' => 'A',
            'jumlah_santri' => 0,
            'maksimal_santri' => 10,
            'kode' => 'KMR-TEST01',
        ]);

        $kelas = Kelas::create([
            'tingkatan' => 'Wustho',
            'kelas' => '1A',
            'kode' => 'KLS-TEST01',
        ]);

        Sanctum::actingAs($pengurusUser, ['*']);

        Event::listen('eloquent.creating: App\Models\Santri', function () {
            throw new \Exception('SENSITIVE_SANTRI_SCHEMA_FAILURE');
        });

        $response = $this->postJson('/v1/sync/santri', [
            'nama_lengkap' => 'Santri API Test',
            'kamar' => $kamar->id,
            'kelas' => $kelas->id,
            'tahun_masuk' => '2023-01-01',
            'jenis_kelamin' => 'Laki-Laki',
            'nik' => '1234567890123456',
            'kk' => '1234567890123456',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => 15,
            'bulan_lahir' => 8,
            'tahun_lahir' => 2005,
            'tempat_lahir' => 'Kediri',
            'dusun' => 'Dusun 1',
            'desa' => 'Desa 1',
            'kecamatan' => 'Kecamatan 1',
            'kabupaten' => 'Kabupaten 1',
            'nama_ayah' => 'Ayah Test',
            'nama_ibu' => 'Ibu Test',
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'status' => false,
            'message' => 'Internal server error',
            'errors' => [
                'server' => ['Internal server error'],
            ],
        ]);

        // Verify that internal exception detail was never leaked to client
        $response->assertDontSee('SENSITIVE_SANTRI_SCHEMA_FAILURE');
    }

    public function test_kelas_update_failure_logs_structured_context_and_redirects(): void
    {
        $this->actingAs($this->admin);

        $kelas = Kelas::create([
            'tingkatan' => 'Tsanawiyah',
            'kelas' => 'VII-B',
            'kode' => 'KLS-VIIB01',
        ]);

        Log::spy();

        Event::listen('eloquent.updating: App\Models\Kelas', function () {
            throw new \Exception('Simulated kelas update failure');
        });

        $response = $this->patch(route('kelas.update', $kelas), [
            'tingkatan' => 'Tsanawiyah',
            'kelas' => 'VII-B Updated',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('laravel_flash_message', function ($flash) {
            return ($flash['class'] ?? null) === 'error'
                && ($flash['message'] ?? null) === 'Gagal merubah data';
        });

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return str_contains($message, 'KelasController update error')
                && array_key_exists('user_id', $context)
                && array_key_exists('request_uri', $context)
                && array_key_exists('method', $context)
                && array_key_exists('ip', $context)
                && array_key_exists('exception', $context);
        });
    }

    public function test_kamar_update_failure_logs_structured_context_and_redirects(): void
    {
        $this->actingAs($this->admin);

        $kamar = Kamar::create([
            'nama' => 'Kamar Umar',
            'blok' => 'B',
            'jumlah_santri' => 0,
            'maksimal_santri' => 6,
            'kode' => 'KMR-UMAR01',
        ]);

        Log::spy();

        Event::listen('eloquent.updating: App\Models\Kamar', function () {
            throw new \Exception('Simulated kamar update failure');
        });

        $response = $this->patch(route('kamar.update', $kamar), [
            'nama' => 'Kamar Umar Updated',
            'blok' => 'B',
            'jumlah_santri' => 0,
            'maksimal_santri' => 8,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('laravel_flash_message', function ($flash) {
            return ($flash['class'] ?? null) === 'error'
                && ($flash['message'] ?? null) === 'Gagal merubah data';
        });

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return str_contains($message, 'KamarController update error')
                && array_key_exists('user_id', $context)
                && array_key_exists('request_uri', $context)
                && array_key_exists('method', $context)
                && array_key_exists('ip', $context)
                && array_key_exists('exception', $context);
        });
    }

    public function test_profil_account_update_failure_logs_structured_context_and_redirects(): void
    {
        $this->actingAs($this->admin);

        Log::spy();

        Event::listen('eloquent.updating: App\Models\User', function () {
            throw new \Exception('Simulated profil account failure');
        });

        $response = $this->post(route('profil.account', $this->admin), [
            'name' => 'Admin Updated Name',
            'email' => $this->admin->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('laravel_flash_message', function ($flash) {
            return ($flash['class'] ?? null) === 'error'
                && ($flash['message'] ?? null) === 'Gagal merubah data';
        });

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return str_contains($message, 'ProfilController account error')
                && array_key_exists('user_id', $context)
                && array_key_exists('request_uri', $context)
                && array_key_exists('method', $context)
                && array_key_exists('ip', $context)
                && array_key_exists('exception', $context);
        });
    }
}
