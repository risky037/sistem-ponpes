<?php

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SynchronizationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $santriUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        Setting::firstOrCreate([
            'log_activity' => false,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin_sync@example.com',
            'password' => Hash::make('AdminSecret123!'),
        ]);
        $this->admin->assignRole('Administrator');

        $this->pengurus = User::factory()->create([
            'name' => 'Pengurus User',
            'email' => 'pengurus_sync@example.com',
            'password' => Hash::make('PengurusSecret123!'),
        ]);
        $this->pengurus->assignRole('Pengurus');

        $this->santriUser = User::factory()->create([
            'name' => 'Regular Santri User',
            'email' => 'santri_sync@example.com',
            'password' => Hash::make('SantriSecret123!'),
        ]);
        $this->santriUser->assignRole('Santri');
    }

    /**
     * Scenario 1: Guest (unauthenticated) cannot access sync endpoints (401 Unauthorized).
     */
    public function test_guest_cannot_access_sync_endpoints(): void
    {
        $endpoints = [
            ['GET', '/v1/sync/kelas'],
            ['POST', '/v1/sync/kelas'],
            ['GET', '/v1/sync/santri'],
            ['POST', '/v1/sync/santri'],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $response = $this->json($method, $uri);

            $response->assertStatus(401);
        }
    }

    /**
     * Scenario 2: Unauthorized role (e.g. Santri) cannot access sync endpoints (403 Forbidden).
     */
    public function test_unauthorized_role_cannot_access_sync_endpoints(): void
    {
        Sanctum::actingAs($this->santriUser, ['*']);

        $endpoints = [
            ['GET', '/v1/sync/kelas', []],
            ['POST', '/v1/sync/kelas', ['tingkatan' => 'Ula', 'kelas' => '1A']],
            ['GET', '/v1/sync/santri', []],
            ['POST', '/v1/sync/santri', ['nama_lengkap' => 'Test']],
        ];

        foreach ($endpoints as [$method, $uri, $payload]) {
            $response = $this->json($method, $uri, $payload ?? []);

            $response->assertStatus(403);
        }
    }

    /**
     * Scenario 3: Authenticated authorized Administrator can access and read sync data.
     */
    public function test_authorized_administrator_can_read_sync_data(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        Kelas::create([
            'kode' => 'K1A',
            'kelas' => '1A',
            'tingkatan' => 'Ula',
        ]);

        $response = $this->json('GET', '/v1/sync/kelas');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'get all data kelas',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ]);
    }

    /**
     * Scenario 4: Authenticated authorized Pengurus can create sync data and triggers audit log.
     */
    public function test_authorized_pengurus_can_create_kelas_with_audit_log(): void
    {
        Sanctum::actingAs($this->pengurus, ['*']);

        $payload = [
            'tingkatan' => 'Wustho',
            'kelas' => '2B',
            'keterangan' => 'Kelas Baru',
        ];

        $response = $this->json('POST', '/v1/sync/kelas', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
                'message' => 'successfully created data',
            ])
            ->assertJsonPath('data.tingkatan', 'Wustho')
            ->assertJsonPath('data.kelas', '2B');

        $this->assertDatabaseHas('kelas', [
            'tingkatan' => 'Wustho',
            'kelas' => '2B',
        ]);

        // Verify audit logging
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->pengurus->id,
        ]);
        $log = ActivityLog::where('user_id', $this->pengurus->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('POST /api/v1/sync/kelas', $log->activity);
        $this->assertStringContainsString('action:create', $log->activity);
    }

    /**
     * Scenario 5: Invalid payload to sync endpoint returns structured 422 Unprocessable Entity.
     */
    public function test_invalid_sync_payload_returns_structured_422_error(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        // Missing required 'tingkatan' and 'kelas'
        $response = $this->json('POST', '/v1/sync/kelas', [
            'keterangan' => 'Only Keterangan',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'Validation error',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => [
                    'tingkatan',
                    'kelas',
                ],
            ]);
    }

    /**
     * Scenario 6: Dedicated sync-api rate limiter returns 429 when limit exceeded.
     */
    public function test_rate_limiter_protects_sync_api(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        // Send 60 requests (allowed)
        for ($i = 0; $i < 60; $i++) {
            $res = $this->json('GET', '/v1/sync/kelas');
            $this->assertEquals(200, $res->status());
        }

        // 61st request should trigger 429 Too Many Requests
        $exceededResponse = $this->json('GET', '/v1/sync/kelas');
        $exceededResponse->assertStatus(429)
            ->assertJson([
                'status' => false,
                'message' => 'Too many requests. Rate limit exceeded.',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => [
                    'rate_limit',
                ],
            ]);
    }
}
