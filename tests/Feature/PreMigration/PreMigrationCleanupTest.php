<?php

namespace Tests\Feature\PreMigration;

use App\Http\Middleware\Admin;
use App\Http\Middleware\Keuangan;
use App\Http\Middleware\Santri as SantriMiddleware;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PreMigrationCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;

    protected Role $pengurusRole;

    protected Role $keuanganRole;

    protected Role $santriRole;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $this->pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        $this->keuanganRole = Role::firstOrCreate(['name' => 'Keuangan', 'guard_name' => 'web']);
        $this->santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

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

    public function test_admin_middleware_permits_user_with_administrator_role(): void
    {
        $middleware = new Admin;
        $request = Request::create('/test-admin', 'GET');
        $request->setUserResolver(fn () => $this->admin);

        $response = $middleware->handle($request, fn ($req) => response('OK', 200));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_admin_middleware_permits_user_with_multiple_roles_when_admin_is_not_first(): void
    {
        $multiRoleUser = User::create([
            'name' => 'Multi Role User',
            'email' => 'multirole@example.com',
            'password' => Hash::make('Password123!'),
        ]);
        $multiRoleUser->assignRole([$this->pengurusRole, $this->adminRole]);

        $middleware = new Admin;
        $request = Request::create('/test-admin', 'GET');
        $request->setUserResolver(fn () => $multiRoleUser);

        $response = $middleware->handle($request, fn ($req) => response('OK', 200));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_admin_middleware_aborts_401_for_non_admin_user(): void
    {
        $pengurus = User::create([
            'name' => 'Pengurus User',
            'email' => 'pengurus@example.com',
            'password' => Hash::make('Password123!'),
        ]);
        $pengurus->assignRole($this->pengurusRole);

        $middleware = new Admin;
        $request = Request::create('/test-admin', 'GET');
        $request->setUserResolver(fn () => $pengurus);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('');

        try {
            $middleware->handle($request, fn ($req) => response('OK', 200));
        } catch (HttpException $e) {
            $this->assertEquals(401, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_admin_middleware_aborts_401_safely_for_unauthenticated_request(): void
    {
        $middleware = new Admin;
        $request = Request::create('/test-admin', 'GET');
        $request->setUserResolver(fn () => null);

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($request, fn ($req) => response('OK', 200));
        } catch (HttpException $e) {
            $this->assertEquals(401, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_keuangan_middleware_permits_user_with_keuangan_role(): void
    {
        $keuanganUser = User::create([
            'name' => 'Keuangan User',
            'email' => 'keuangan@example.com',
            'password' => Hash::make('Password123!'),
        ]);
        $keuanganUser->assignRole($this->keuanganRole);

        $middleware = new Keuangan;
        $request = Request::create('/test-keuangan', 'GET');
        $request->setUserResolver(fn () => $keuanganUser);

        $response = $middleware->handle($request, fn ($req) => response('OK', 200));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_keuangan_middleware_aborts_401_for_non_keuangan_user(): void
    {
        $middleware = new Keuangan;
        $request = Request::create('/test-keuangan', 'GET');
        $request->setUserResolver(fn () => $this->admin);

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($request, fn ($req) => response('OK', 200));
        } catch (HttpException $e) {
            $this->assertEquals(401, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_santri_middleware_permits_user_with_santri_role(): void
    {
        $santriUser = User::create([
            'name' => 'Santri User',
            'email' => 'santri@example.com',
            'password' => Hash::make('Password123!'),
        ]);
        $santriUser->assignRole($this->santriRole);

        $middleware = new SantriMiddleware;
        $request = Request::create('/test-santri', 'GET');
        $request->setUserResolver(fn () => $santriUser);

        $response = $middleware->handle($request, fn ($req) => response('OK', 200));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_santri_middleware_aborts_401_for_non_santri_user(): void
    {
        $middleware = new SantriMiddleware;
        $request = Request::create('/test-santri', 'GET');
        $request->setUserResolver(fn () => $this->admin);

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($request, fn ($req) => response('OK', 200));
        } catch (HttpException $e) {
            $this->assertEquals(401, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_sync_update_stores_timestamp_in_cache_without_mutating_config_file(): void
    {
        $this->actingAs($this->admin);

        $configFile = config_path('modules.php');
        $hashBefore = md5(file_get_contents($configFile));

        $testTimestamp = '2026-09-19 14:00:00';
        $response = $this->postJson(route('sync.update'), [
            'data' => ['data santri', $testTimestamp],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Berhasil mengubah data',
        ]);

        // Verify that config file was NOT modified on disk
        $hashAfter = md5(file_get_contents($configFile));
        $this->assertEquals($hashBefore, $hashAfter);

        // Verify that timestamp is stored in Cache
        $this->assertEquals($testTimestamp, Cache::get('modules.sync.santri'));
    }

    public function test_sync_index_overlays_cached_timestamp(): void
    {
        $this->actingAs($this->admin);

        $testTimestamp = '2026-09-19 15:30:00';
        Cache::forever('modules.sync.santri', $testTimestamp);

        $response = $this->get(route('sync.index'));

        $response->assertStatus(200);
        $response->assertSee($testTimestamp);
    }

    public function test_sinkron_helpers_handle_missing_spreadsheet_id_gracefully(): void
    {
        putenv('SPREADSHEET_ID=');
        putenv('SPREDSHEET_ID=');
        unset($_ENV['SPREADSHEET_ID'], $_ENV['SPREDSHEET_ID']);

        $alumniResponse = \Sinkron::alumni();
        $this->assertNotNull($alumniResponse);
        $data = json_decode($alumniResponse->getContent(), true);
        $this->assertFalse($data['success']);

        $santriResponse = \Sinkron::santri();
        $this->assertNotNull($santriResponse);
        $data = json_decode($santriResponse->getContent(), true);
        $this->assertFalse($data['success']);
    }
}
