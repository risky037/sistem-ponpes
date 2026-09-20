<?php

namespace Tests\Feature\Architecture;

use App\Models\ActivityLog;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Setting;
use App\Models\Tabungan;
use App\Models\Transfer;
use App\Models\User;
use App\Models\WaliSantri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ObserverArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Observer',
            'email' => 'admin.observer@example.com',
            'password' => 'Password123!',
        ]);
        $this->admin->assignRole($adminRole);

        Setting::firstOrCreate([
            'id' => 1,
        ], [
            'log_activity' => true,
        ])->update([
            'log_activity' => true,
        ]);
    }

    protected function createSantri(string $name, string $noInduk): Santri
    {
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'password' => 'Password123!',
        ]);

        return Santri::create([
            'user_id' => $user->id,
            'no_induk' => $noInduk,
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Sumenep',
            'tahun_masuk' => '2024-01-01',
            'tahun_masuk_hijriyah' => '1445',
            'status' => 'Santri Aktif',
        ]);
    }

    /**
     * 1. Verify all logging observers are registered in the event dispatcher.
     */
    public function test_all_logging_observers_are_registered(): void
    {
        $observedModels = [
            User::class,
            Setting::class,
            Kelas::class,
            Kamar::class,
            Tabungan::class,
            Transfer::class,
            WaliSantri::class,
        ];

        foreach ($observedModels as $model) {
            $this->assertTrue(
                Event::hasListeners("eloquent.creating: {$model}"),
                "Observer 'creating' listener must be registered for {$model}"
            );
            $this->assertTrue(
                Event::hasListeners("eloquent.updating: {$model}"),
                "Observer 'updating' listener must be registered for {$model}"
            );
            $this->assertTrue(
                Event::hasListeners("eloquent.deleting: {$model}"),
                "Observer 'deleting' listener must be registered for {$model}"
            );
        }
    }

    /**
     * 2. Verify UserObserver records single activity log without duplicate entries.
     */
    public function test_user_observer_creates_single_log_without_duplicates(): void
    {
        $this->actingAs($this->admin);

        $initialLogCount = ActivityLog::count();

        $user = User::create([
            'name' => 'Ahmad Dahlan',
            'email' => 'ahmad.dahlan@example.com',
            'password' => 'Secret123!',
        ]);

        // Assert exactly 1 log was created for user creation
        $newLogs = ActivityLog::where('id', '>', $initialLogCount)->get();
        $this->assertCount(1, $newLogs, 'Exactly one activity log should be created on User creation.');
        $this->assertStringContainsString('Creatting User Ahmad Dahlan', $newLogs->first()->activity);

        // Update user
        $user->update(['name' => 'Ahmad Dahlan Updated']);
        $updateLogs = ActivityLog::where('id', '>', $newLogs->first()->id)->get();
        $this->assertCount(1, $updateLogs, 'Exactly one activity log should be created on User update.');
        $this->assertStringContainsString('Updating User Ahmad Dahlan Updated', $updateLogs->first()->activity);

        // Delete user
        $lastId = $updateLogs->first()->id;
        $user->delete();
        $deleteLogs = ActivityLog::where('id', '>', $lastId)->get();
        $this->assertCount(1, $deleteLogs, 'Exactly one activity log should be created on User delete.');
        $this->assertStringContainsString('Deleting User Ahmad Dahlan Updated', $deleteLogs->first()->activity);
    }

    /**
     * 2. Verify KelasObserver records activity logs.
     */
    public function test_kelas_observer_creates_activity_log(): void
    {
        $this->actingAs($this->admin);

        $initialLogCount = ActivityLog::count();

        $kelas = Kelas::create([
            'kode' => 'KLS-01A',
            'tingkatan' => 'Ula',
            'kelas' => '1A',
        ]);

        $newLogs = ActivityLog::where('id', '>', $initialLogCount)->get();
        $this->assertCount(1, $newLogs);
        $this->assertStringContainsString('Creating Kelas Ula 1A', $newLogs->first()->activity);
    }

    /**
     * 3. Verify KamarObserver records activity logs.
     */
    public function test_kamar_observer_creates_activity_log(): void
    {
        $this->actingAs($this->admin);

        $initialLogCount = ActivityLog::count();

        $kamar = Kamar::create([
            'kode' => 'KMR-01',
            'nama' => 'Al-Fatih',
            'blok' => 'A',
            'jumlah_santri' => 0,
        ]);

        $newLogs = ActivityLog::where('id', '>', $initialLogCount)->get();
        $this->assertCount(1, $newLogs);
        $this->assertStringContainsString('Creating Kamar Al-Fatih', $newLogs->first()->activity);
    }

    /**
     * 4. Verify SettingObserver records activity logs.
     */
    public function test_setting_observer_creates_activity_log(): void
    {
        $this->actingAs($this->admin);

        $setting = Setting::first();
        $lastLogId = ActivityLog::max('id') ?? 0;

        $setting->update([
            'log_activity' => ! $setting->log_activity,
        ]);

        $newLogs = ActivityLog::where('id', '>', $lastLogId)->get();
        $this->assertCount(1, $newLogs);
        $this->assertStringContainsString('Updating Setting', $newLogs->first()->activity);
    }

    /**
     * 5. Verify TabunganObserver records activity logs.
     */
    public function test_tabungan_observer_creates_activity_log(): void
    {
        $this->actingAs($this->admin);

        $santri = $this->createSantri('Santri Tabungan', '14450099');

        $lastLogId = ActivityLog::max('id') ?? 0;

        $tabungan = Tabungan::create([
            'santri_id' => $santri->id,
            'saldo' => 50000,
        ]);

        $newLogs = ActivityLog::where('id', '>', $lastLogId)->get();
        $this->assertCount(1, $newLogs);
        $this->assertStringContainsString('Creating Tabungan Santri Tabungan', $newLogs->first()->activity);
    }

    /**
     * 6. Verify TransferObserver records activity logs with correct transfer attributes.
     */
    public function test_transfer_observer_creates_activity_log(): void
    {
        $this->actingAs($this->admin);

        $pengirim = $this->createSantri('Pengirim Transfer', '14450101');
        $penerima = $this->createSantri('Penerima Transfer', '14450102');

        $lastLogId = ActivityLog::max('id') ?? 0;

        Transfer::create([
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'jumlah_transfer' => 25000,
            'keterangan' => 'Uang Saku',
        ]);

        $newLogs = ActivityLog::where('id', '>', $lastLogId)->get();
        $this->assertCount(1, $newLogs);
        $this->assertStringContainsString('Creating Transfer 25000', $newLogs->first()->activity);
    }

    /**
     * 7. Verify WaliSantriObserver records activity logs.
     */
    public function test_wali_santri_observer_creates_activity_log(): void
    {
        $this->actingAs($this->admin);

        $santri = $this->createSantri('Santri Wali Test', '14450103');

        $lastLogId = ActivityLog::max('id') ?? 0;

        $wali = WaliSantri::create([
            'santri_id' => $santri->id,
            'nama_ayah' => 'Ayah Test',
            'nama_ibu' => 'Ibu Test',
        ]);

        $newLogs = ActivityLog::where('id', '>', $lastLogId)->get();
        $this->assertCount(1, $newLogs);
        $this->assertStringContainsString('Creating WaliSantri', $newLogs->first()->activity);
    }
}
