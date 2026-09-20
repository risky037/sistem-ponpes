<?php

namespace Tests\Feature\Security;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Role $adminRole;

    protected Role $santriRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $this->santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Keuangan', 'guard_name' => 'web']);

        Setting::firstOrCreate([
            'log_activity' => false,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Primary Admin',
            'email' => 'primary_admin@example.com',
            'password' => Hash::make('AdminSecure123!'),
        ]);
        $this->admin->assignRole('Administrator');
    }

    /**
     * 1. Login rate limiting triggers lockout after 5 consecutive failed attempts.
     */
    public function test_login_rate_limiting_locks_out_after_five_failed_attempts(): void
    {
        Event::fake([Lockout::class]);

        $targetEmail = 'victim@example.com';
        $throttleKey = Str::transliterate(Str::lower($targetEmail).'|127.0.0.1');
        RateLimiter::clear($throttleKey);

        // 5 consecutive failed attempts
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->from(route('login'))->post(route('login.auth'), [
                'email' => $targetEmail,
                'password' => 'wrong-password-'.$i,
            ]);

            $response->assertRedirect(route('login'));
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt must be locked out
        $lockoutResponse = $this->from(route('login'))->post(route('login.auth'), [
            'email' => $targetEmail,
            'password' => 'any-password',
        ]);

        $lockoutResponse->assertRedirect(route('login'));
        $lockoutResponse->assertSessionHasErrors('email');

        Event::assertDispatched(Lockout::class);

        RateLimiter::clear($throttleKey);
    }

    /**
     * 2. Successful login clears rate limiter attempts.
     */
    public function test_successful_login_clears_rate_limiter(): void
    {
        $user = User::factory()->create([
            'email' => 'user_auth@example.com',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $throttleKey = Str::transliterate(Str::lower($user->email).'|127.0.0.1');
        RateLimiter::clear($throttleKey);

        // 2 failed attempts
        for ($i = 1; $i <= 2; $i++) {
            $this->from(route('login'))->post(route('login.auth'), [
                'email' => $user->email,
                'password' => 'wrong-pass',
            ]);
        }

        $this->assertEquals(2, RateLimiter::attempts($throttleKey));

        // Successful attempt
        $successResponse = $this->post(route('login.auth'), [
            'email' => $user->email,
            'password' => 'CorrectPassword123!',
        ]);

        $successResponse->assertRedirect(route('dashboard'));
        $this->assertEquals(0, RateLimiter::attempts($throttleKey));
    }

    /**
     * 3. Administrator cannot delete their own user account.
     */
    public function test_administrator_cannot_delete_own_account(): void
    {
        $this->actingAs($this->admin);

        $response = $this->delete(route('users.destroy', $this->admin));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    /**
     * 4. Deleting the last Administrator account is prevented.
     */
    public function test_cannot_delete_the_last_administrator_account(): void
    {
        // Second admin who will attempt the deletion
        $adminTwo = User::factory()->create([
            'name' => 'Secondary Admin',
            'email' => 'admin_two@example.com',
            'password' => Hash::make('AdminTwoSecure123!'),
        ]);
        $adminTwo->assignRole('Administrator');

        $this->actingAs($adminTwo);

        // Delete primary admin - should succeed because adminTwo remains
        $response1 = $this->delete(route('users.destroy', $this->admin));
        $response1->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $this->admin->id]);

        // Now adminTwo is the sole administrator.
        $this->assertEquals(1, User::role('Administrator')->count());

        $response2 = $this->delete(route('users.destroy', $adminTwo));
        $response2->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $adminTwo->id]);
    }

    /**
     * 5. Administrator cannot self-demote their role away from Administrator.
     */
    public function test_administrator_cannot_demote_own_role_away_from_administrator(): void
    {
        $this->actingAs($this->admin);

        $response = $this->patch(route('users.update', $this->admin->id), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role_id' => $this->santriRole->id,
        ]);

        $response->assertRedirect();
        $this->admin->refresh();
        $this->assertTrue($this->admin->hasRole('Administrator'));
    }

    /**
     * 6. Default system roles cannot be deleted.
     */
    public function test_default_system_roles_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin);

        foreach (['Administrator', 'Keuangan', 'Santri'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $response = $this->delete(route('roles.destroy', $role));

            $response->assertRedirect();
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }

        // Custom deletable role can still be deleted
        $customRole = Role::create(['name' => 'CustomTemporaryRole', 'guard_name' => 'web']);
        $deleteCustomResponse = $this->delete(route('roles.destroy', $customRole));

        $deleteCustomResponse->assertRedirect();
        $this->assertDatabaseMissing('roles', ['name' => 'CustomTemporaryRole']);
    }

    /**
     * 7. User model serialization hides password and remember_token.
     */
    public function test_user_serialization_hides_password_and_remember_token(): void
    {
        $user = User::factory()->make([
            'remember_token' => 'super_secret_remember_token_string',
        ]);

        $arrayData = $user->toArray();
        $this->assertArrayNotHasKey('password', $arrayData);
        $this->assertArrayNotHasKey('remember_token', $arrayData);

        $jsonData = $user->toJson();
        $this->assertStringNotContainsString('super_secret_remember_token_string', $jsonData);
    }

    /**
     * 8. Setting upload validation rejects non-image files for logo, favicon, and kts_master.
     */
    public function test_setting_upload_validation_rejects_invalid_mime_and_kts_master(): void
    {
        $this->actingAs($this->admin);

        $fakeScript = UploadedFile::fake()->create('malicious.php', 100, 'text/x-php');

        $response = $this->post(route('setting.store'), [
            'log_activity' => '1',
            'logo' => $fakeScript,
            'favicon' => $fakeScript,
            'kts_master' => $fakeScript,
        ]);

        $response->assertSessionHasErrors(['logo', 'favicon', 'kts_master']);
    }
}
