<?php

namespace Tests\Feature\PreMigration;

use App\Http\Middleware\Admin;
use App\Http\Middleware\Keuangan;
use App\Http\Middleware\Santri as SantriMiddleware;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
}
