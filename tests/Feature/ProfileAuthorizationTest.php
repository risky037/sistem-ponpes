<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);
    }

    /**
     * Test 1: User updates own account profile successfully.
     */
    public function test_user_can_update_own_account_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'user1@example.com',
        ]);

        $response = $this->actingAs($user)->post(route('profil.account', $user), [
            'name' => 'Updated Name',
            'email' => 'updated_user1@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated_user1@example.com',
        ]);
    }

    /**
     * Test 2: User cannot update another user's account profile (403 Forbidden).
     */
    public function test_user_cannot_update_another_users_account_profile(): void
    {
        $victim = User::factory()->create([
            'email' => 'victim@example.com',
        ]);

        $attacker = User::factory()->create([
            'email' => 'attacker@example.com',
        ]);

        $response = $this->actingAs($attacker)->post(route('profil.account', $victim), [
            'name' => 'Hacked Name',
            'email' => 'hacked@example.com',
            'password' => 'hackedpassword123',
            'password_confirmation' => 'hackedpassword123',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('users', [
            'id' => $victim->id,
            'email' => 'victim@example.com',
        ]);
    }

    /**
     * Test 3: Administrator can update another user's account profile.
     */
    public function test_administrator_can_update_another_users_account_profile(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('Administrator');

        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->actingAs($admin)->post(route('profil.account', $user), [
            'name' => 'Admin Updated Name',
            'email' => 'admin_updated@example.com',
            'password' => 'newadminpass123',
            'password_confirmation' => 'newadminpass123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Admin Updated Name',
            'email' => 'admin_updated@example.com',
        ]);
    }

    /**
     * Test 4: Guest access is rejected as unauthorized (redirected to login).
     */
    public function test_guest_cannot_update_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('profil.account', $user), [
            'name' => 'Guest Update',
            'email' => 'guest@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
    }

    /**
     * Test 5: User cannot update another user's biodata (403 Forbidden).
     */
    public function test_user_cannot_update_another_users_biodata(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();

        $response = $this->actingAs($attacker)->post(route('profil.biodata', $victim), [
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '2000-01-01',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567890',
            'alamat_lengkap' => 'Jl. Kebon Jeruk No. 1, Jakarta',
            'nik' => '1234567890123456',
            'kk' => '1234567890123456',
        ]);

        $response->assertForbidden();
    }
}
