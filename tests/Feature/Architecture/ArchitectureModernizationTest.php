<?php

namespace Tests\Feature\Architecture;

use App\Helpers\Helper;
use App\Helpers\Ping;
use App\Helpers\Sinkron;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\Santri\SantriController;
use App\Http\Controllers\Sinkron\SinkronController;
use App\Http\Controllers\Tabungan\SaldoDebitController;
use App\Http\Controllers\Transaksi\TransaksiController;
use App\Http\Controllers\TransferController;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArchitectureModernizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Modern',
            'email' => 'admin.modern@example.com',
            'password' => 'Password123!',
        ]);
        $this->admin->assignRole($adminRole);

        $this->regularUser = User::create([
            'name' => 'User Modern',
            'email' => 'user.modern@example.com',
            'password' => 'Password123!',
        ]);
        $this->regularUser->assignRole($santriRole);
    }

    /**
     * 1. Verify Base Controller is abstract and no longer imports deprecated traits.
     */
    public function test_base_controller_is_abstract_and_does_not_use_deprecated_traits(): void
    {
        $reflection = new ReflectionClass(Controller::class);

        $this->assertTrue($reflection->isAbstract(), 'Controller must be an abstract class in Laravel 12.');

        $traits = $reflection->getTraitNames();
        $this->assertNotContains(
            AuthorizesRequests::class,
            $traits,
            'Base Controller should not use deprecated AuthorizesRequests trait.'
        );
        $this->assertNotContains(
            ValidatesRequests::class,
            $traits,
            'Base Controller should not use deprecated ValidatesRequests trait.'
        );
    }

    /**
     * 2. Verify all application controllers resolve successfully from the container.
     */
    public function test_controllers_resolve_successfully_via_service_container(): void
    {
        $controllers = [
            SantriController::class,
            ProfilController::class,
            TransferController::class,
            SaldoDebitController::class,
            TransaksiController::class,
            SinkronController::class,
        ];

        foreach ($controllers as $controllerClass) {
            $instance = app()->make($controllerClass);
            $this->assertInstanceOf(
                Controller::class,
                $instance,
                "Failed to resolve {$controllerClass} as instance of Base Controller."
            );
        }
    }

    /**
     * 3. Verify ProfilController authorization works with Gate facade without Base Controller traits.
     */
    public function test_profil_controller_authorization_functions_via_gate(): void
    {
        // Own profile update allowed
        $response = $this->actingAs($this->regularUser)->post(route('profil.account', $this->regularUser->id), [
            'name' => 'Updated User Name',
            'email' => 'updated.user@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->regularUser->id, 'name' => 'Updated User Name']);

        // Updating another user's profile forbidden for regular user
        $forbiddenResponse = $this->actingAs($this->regularUser)->post(route('profil.account', $this->admin->id), [
            'name' => 'Malicious Update',
            'email' => 'malicious@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
        $forbiddenResponse->assertStatus(403);

        // Administrator can update any profile
        $adminUpdateResponse = $this->actingAs($this->admin)->post(route('profil.account', $this->regularUser->id), [
            'name' => 'Admin Updated Name',
            'email' => 'admin.updated@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
        $adminUpdateResponse->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->regularUser->id, 'name' => 'Admin Updated Name']);
    }

    /**
     * 4. Verify User model uses modern casts() method and hashes passwords.
     */
    public function test_user_model_casts_definition_is_modern_method(): void
    {
        $reflection = new ReflectionClass(User::class);
        $this->assertTrue($reflection->hasMethod('casts'), 'User model must implement casts() method.');

        $user = User::create([
            'name' => 'Cast Test',
            'email' => 'cast.test@example.com',
            'password' => 'PlainSecret123!',
        ]);

        $this->assertTrue(Hash::check('PlainSecret123!', $user->password), 'Password must be hashed via casts() definition.');
    }

    /**
     * 5. Verify Helpers are PSR-4 namespaced and backward-compatible aliases resolve.
     */
    public function test_helper_classes_autoload_via_psr4_and_aliases(): void
    {
        // PSR-4 classes
        $this->assertTrue(class_exists(Helper::class));
        $this->assertTrue(class_exists(Ping::class));
        $this->assertTrue(class_exists(Sinkron::class));

        // Aliases
        $this->assertTrue(class_exists('Helper'));
        $this->assertTrue(class_exists('Ping'));
        $this->assertTrue(class_exists('Sinkron'));

        // Test helper method execution
        $noInduk = Helper::make_noinduk([
            'tahun_masuk' => '2024-01-01',
            'gender' => 'Laki-Laki',
            'tahun_masuk_hijriyah' => '1445',
        ]);

        $this->assertIsString($noInduk);
        $this->assertSame(8, strlen($noInduk));
        $this->assertStringStartsWith('1445', $noInduk);

        // Test alias execution
        $noIndukViaAlias = \Helper::make_noinduk([
            'tahun_masuk' => '2024-01-01',
            'gender' => 'Perempuan',
            'tahun_masuk_hijriyah' => '1445',
        ]);
        $this->assertIsString($noIndukViaAlias);
        $this->assertSame(8, strlen($noIndukViaAlias));
    }

    /**
     * 6. Verify Ping Helper returns true on successful HTTP 200 response.
     */
    public function test_ping_helper_returns_true_on_success(): void
    {
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $this->assertTrue(Ping::to(), 'Ping::to() should return true on HTTP 200.');
        $this->assertTrue(\Ping::to(), 'Global Ping alias should behave identically.');
    }

    /**
     * 7. Verify Ping Helper returns false on HTTP server error.
     */
    public function test_ping_helper_returns_false_on_http_error(): void
    {
        Http::fake([
            '*' => Http::response('Internal Server Error', 500),
        ]);

        $this->assertFalse(Ping::to(), 'Ping::to() should return false on HTTP 500.');
    }

    /**
     * 8. Verify Ping Helper returns false on connection failure / exception.
     */
    public function test_ping_helper_returns_false_on_connection_exception(): void
    {
        Http::fake([
            '*' => function () {
                throw new ConnectionException('Network unreachable');
            },
        ]);

        $this->assertFalse(Ping::to(), 'Ping::to() should catch connection exceptions and return false.');
    }

    /**
     * 9. Verify Debugbar alias resolves via package auto-discovery without AliasLoader.
     */
    public function test_debugbar_alias_resolves_without_manual_alias_loader(): void
    {
        $this->assertTrue(class_exists('Debugbar'), 'Debugbar class alias must resolve via package auto-discovery.');
    }
}
