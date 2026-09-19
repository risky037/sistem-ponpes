<?php

namespace Tests\Feature\Reliability;

use App\Models\Santri;
use App\Models\Setting;
use App\Models\User;
use App\Models\WaliSantri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DebugCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Keuangan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        Setting::firstOrCreate([
            'log_activity' => false,
        ]);

        $this->admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminSecret123!'),
        ]);
        $this->admin->assignRole($this->adminRole);
    }

    public function test_no_active_debug_statements_in_application_and_routes(): void
    {
        $scanDirectories = [
            app_path(),
            base_path('routes'),
            config_path(),
            database_path(),
        ];

        $debugPatterns = [
            '/\bdd\s*\(/',
            '/\bdump\s*\(/',
            '/\bvar_dump\s*\(/',
            '/\bprint_r\s*\(/',
            '/\bray\s*\(/',
        ];

        $violations = [];

        foreach ($scanDirectories as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isDir() || $file->getExtension() !== 'php') {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                $lines = explode("\n", $content);

                foreach ($lines as $index => $line) {
                    $trimmed = trim($line);
                    // Skip single-line comments
                    if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '*')) {
                        continue;
                    }

                    foreach ($debugPatterns as $pattern) {
                        if (preg_match($pattern, $trimmed)) {
                            $violations[] = sprintf(
                                '%s:%d - %s',
                                $file->getPathname(),
                                $index + 1,
                                $trimmed
                            );
                        }
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Found production debug statements:\n".implode("\n", $violations)
        );
    }

    public function test_santri_export_works_for_all_santri(): void
    {
        $this->actingAs($this->admin);

        $user = User::create([
            'name' => 'Ahmad Santri',
            'email' => 'ahmad@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('Santri');

        $santri = Santri::create([
            'user_id' => $user->id,
            'no_induk' => '14450001',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '628123456789',
            'tanggal_lahir' => '2005-01-01',
            'tempat_lahir' => 'Jombang',
            'status' => 'Santri Aktif',
            'tahun_masuk' => '2023-08-01',
            'tahun_masuk_hijriyah' => '1445',
        ]);

        WaliSantri::create([
            'santri_id' => $santri->id,
            'nama_ayah' => 'Bapak Ahmad',
            'nama_ibu' => 'Ibu Ahmad',
        ]);

        $response = $this->post(route('santri.export'), [
            'status' => ['Semua Santri'],
        ]);

        $response->assertOk();
        $this->assertStringContainsString(
            'attachment; filename=Export-data-santri.xlsx',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_santri_export_works_with_status_filter(): void
    {
        $this->actingAs($this->admin);

        $user = User::create([
            'name' => 'Fatimah Alumni',
            'email' => 'fatimah@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('Santri');

        $santri = Santri::create([
            'user_id' => $user->id,
            'no_induk' => '14450002',
            'jenis_kelamin' => 'Perempuan',
            'whatsapp' => '628987654321',
            'tanggal_lahir' => '2004-05-10',
            'tempat_lahir' => 'Surabaya',
            'status' => 'Santri Alumni',
            'tahun_masuk' => '2022-08-01',
            'tahun_masuk_hijriyah' => '1444',
        ]);

        WaliSantri::create([
            'santri_id' => $santri->id,
            'nama_ayah' => 'Bapak Fatimah',
            'nama_ibu' => 'Ibu Fatimah',
        ]);

        $response = $this->post(route('santri.export'), [
            'status' => ['Santri Alumni'],
        ]);

        $response->assertOk();
        $this->assertStringContainsString(
            'attachment; filename=Export-data-santri.xlsx',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_santri_export_works_when_no_records_exist(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('santri.export'), [
            'status' => ['Semua Santri'],
        ]);

        $response->assertOk();
        $this->assertStringContainsString(
            'attachment; filename=Export-data-santri.xlsx',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_transfer_validation_failure_does_not_halt_with_dd(): void
    {
        $this->actingAs($this->admin);

        $user = User::create([
            'name' => 'Santri Test',
            'email' => 'santri@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('Santri');

        $santri = Santri::create([
            'user_id' => $user->id,
            'no_induk' => '14450003',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '628111222333',
            'tanggal_lahir' => '2005-02-02',
            'tempat_lahir' => 'Kediri',
            'status' => 'Santri Aktif',
            'tahun_masuk' => '2023-08-01',
            'tahun_masuk_hijriyah' => '1445',
        ]);

        // Attempt transfer to same person
        $response = $this->post(route('transfer.store'), [
            'pengirim_id' => $santri->id,
            'penerima_id' => $santri->id,
            'nominal' => 50000,
            'keterangan' => 'Test transfer',
        ]);

        $response->assertStatus(302);
    }
}
