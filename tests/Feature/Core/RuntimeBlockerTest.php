<?php

namespace Tests\Feature\Core;

use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RuntimeBlockerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Role $adminRole;

    protected Role $pengurusRole;

    protected Role $keuanganRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        $this->adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $this->pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        $this->keuanganRole = Role::firstOrCreate(['name' => 'Keuangan', 'guard_name' => 'web']);

        Setting::firstOrCreate([
            'log_activity' => false,
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPass123!'),
        ]);
        $this->admin->assignRole($this->adminRole);
    }

    public function test_room_creation_works_without_faker_dependency(): void
    {
        $this->actingAs($this->admin);

        $payload = [
            'nama' => 'Kamar Abu Bakar',
            'blok' => 'A',
            'jumlah_santri' => 0,
            'maksimal_santri' => 8,
        ];

        $response = $this->post(route('kamar.store'), $payload);

        $response->assertStatus(302);
        $this->assertDatabaseHas('kamars', [
            'nama' => 'Kamar Abu Bakar',
            'blok' => 'A',
        ]);

        $kamar = Kamar::where('nama', 'Kamar Abu Bakar')->first();
        $this->assertNotNull($kamar);
        $this->assertTrue(Str::startsWith($kamar->kode, 'KMR-'));
        $this->assertEquals(10, strlen($kamar->kode));
    }

    public function test_class_creation_works_without_faker_dependency(): void
    {
        $this->actingAs($this->admin);

        $payload = [
            'tingkatan' => 'Tsanawiyah',
            'kelas' => 'VII-A',
            'keterangan' => 'Kelas Baru',
        ];

        $response = $this->post(route('kelas.store'), $payload);

        $response->assertStatus(302);
        $this->assertDatabaseHas('kelas', [
            'tingkatan' => 'Tsanawiyah',
            'kelas' => 'VII-A',
        ]);

        $kelas = Kelas::where('kelas', 'VII-A')->first();
        $this->assertNotNull($kelas);
        $this->assertTrue(Str::startsWith($kelas->kode, 'KLS-'));
        $this->assertEquals(10, strlen($kelas->kode));
    }

    public function test_user_creation_with_role_assignment_works(): void
    {
        $this->actingAs($this->admin);

        $payload = [
            'name' => 'Ust. Ahmad Dahlan',
            'email' => 'ahmad.dahlan@example.com',
            'role_id' => $this->pengurusRole->id,
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
        ];

        $response = $this->post(route('users.store'), $payload);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'email' => 'ahmad.dahlan@example.com',
            'name' => 'Ust. Ahmad Dahlan',
        ]);

        $newUser = User::where('email', 'ahmad.dahlan@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole('Pengurus'));
        $this->assertFalse($newUser->hasRole('Administrator'));

        $response->assertSessionHas('laravel_flash_message', function ($flash) {
            return ($flash['class'] ?? null) === 'success';
        });
    }

    public function test_user_update_with_role_change_works(): void
    {
        $this->actingAs($this->admin);

        $user = User::create([
            'name' => 'Staf Pengurus',
            'email' => 'staf.pengurus@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $user->assignRole($this->pengurusRole);

        $this->assertTrue($user->hasRole('Pengurus'));

        $payload = [
            'name' => 'Staf Keuangan',
            'email' => 'staf.keuangan@example.com',
            'role_id' => $this->keuanganRole->id,
        ];

        $response = $this->patch(route('users.update', $user), $payload);

        $response->assertStatus(302);

        $updatedUser = $user->fresh();
        $this->assertEquals('Staf Keuangan', $updatedUser->name);
        $this->assertEquals('staf.keuangan@example.com', $updatedUser->email);
        $this->assertTrue($updatedUser->hasRole('Keuangan'));
        $this->assertFalse($updatedUser->hasRole('Pengurus'));

        $response->assertSessionHas('laravel_flash_message', function ($flash) {
            return ($flash['class'] ?? null) === 'success';
        });
    }

    public function test_users_table_does_not_rely_on_or_contain_role_id_column(): void
    {
        $this->assertFalse(
            Schema::hasColumn('users', 'role_id'),
            'The users table must not contain a role_id column; roles must be managed via Spatie permission tables.'
        );

        $user = User::create([
            'name' => 'Direct User Test',
            'email' => 'direct.user@example.com',
            'password' => Hash::make('Secret123!'),
        ]);

        $attributes = $user->toArray();
        $this->assertArrayNotHasKey('role_id', $attributes);

        $this->actingAs($this->admin);
        $this->post(route('users.store'), [
            'name' => 'Form Role User',
            'email' => 'form.role.user@example.com',
            'role_id' => $this->pengurusRole->id,
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $savedUser = User::where('email', 'form.role.user@example.com')->first();
        $this->assertNotNull($savedUser);
        $this->assertArrayNotHasKey('role_id', $savedUser->toArray());
        $this->assertTrue($savedUser->hasRole('Pengurus'));
    }

    public function test_invalid_or_cross_guard_role_fails_validation(): void
    {
        $this->actingAs($this->admin);

        // 1. Non-existent role ID fails validation
        $responseNonExistent = $this->post(route('users.store'), [
            'name' => 'Invalid Role User',
            'email' => 'invalid.role@example.com',
            'role_id' => 999999,
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $responseNonExistent->assertSessionHasErrors('role_id');

        // 2. Cross-guard role (e.g. guard_name = 'api') fails validation scoped to 'web'
        $apiRole = Role::firstOrCreate(['name' => 'ApiRole', 'guard_name' => 'api']);

        $responseCrossGuard = $this->post(route('users.store'), [
            'name' => 'Cross Guard User',
            'email' => 'cross.guard@example.com',
            'role_id' => $apiRole->id,
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $responseCrossGuard->assertSessionHasErrors('role_id');
    }

    public function test_failed_user_operation_shows_error_notification(): void
    {
        $this->actingAs($this->admin);

        Event::listen('eloquent.creating: App\Models\User', function () {
            throw new \Exception('Simulated database creation failure');
        });

        $response = $this->post(route('users.store'), [
            'name' => 'Simulated Error User',
            'email' => 'simulated.error@example.com',
            'role_id' => $this->pengurusRole->id,
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('laravel_flash_message', function ($flash) {
            return ($flash['class'] ?? null) === 'error'
                && ($flash['message'] ?? null) === 'Gagal menambah data';
        });
    }
}
