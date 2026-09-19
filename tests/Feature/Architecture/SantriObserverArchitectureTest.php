<?php

namespace Tests\Feature\Architecture;

use App\Models\ActivityLog;
use App\Models\Kamar;
use App\Models\KamarSantri;
use App\Models\Santri;
use App\Models\Setting;
use App\Models\User;
use App\Services\SantriRoomCounterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SantriObserverArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Santri Architecture',
            'email' => 'admin.santri.arch@example.com',
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

    protected function createKamar(string $kode, string $nama, string $blok = 'A'): Kamar
    {
        return Kamar::create([
            'kode' => $kode,
            'nama' => $nama,
            'blok' => $blok,
            'jumlah_santri' => 0,
            'maksimal_santri' => 6,
        ]);
    }

    protected function createSantri(string $name, string $noInduk, ?int $kamarId = null): Santri
    {
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'password' => 'Password123!',
        ]);

        $payload = [
            'user_id' => $user->id,
            'no_induk' => $noInduk,
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Sumenep',
            'tahun_masuk' => '2024-01-01',
            'tahun_masuk_hijriyah' => '1445',
            'status' => 'Santri Aktif',
        ];

        if ($kamarId !== null) {
            $payload['kamar_id'] = $kamarId;
        }

        return Santri::create($payload);
    }

    /**
     * 1. Verify SantriObserver is properly registered in the event dispatcher.
     */
    public function test_santri_observer_is_registered_in_event_dispatcher(): void
    {
        $this->assertTrue(
            Event::hasListeners('eloquent.creating: '.Santri::class),
            "Santri 'creating' listener must be registered."
        );
        $this->assertTrue(
            Event::hasListeners('eloquent.created: '.Santri::class),
            "Santri 'created' listener must be registered."
        );
        $this->assertTrue(
            Event::hasListeners('eloquent.updating: '.Santri::class),
            "Santri 'updating' listener must be registered."
        );
        $this->assertTrue(
            Event::hasListeners('eloquent.deleting: '.Santri::class),
            "Santri 'deleting' listener must be registered."
        );
    }

    /**
     * 2. Verify creating a Santri with kamar_id increments room headcount and creates KamarSantri.
     */
    public function test_santri_created_updates_kamar_count(): void
    {
        $this->actingAs($this->admin);

        $kamar = $this->createKamar('KMR-ARCH-01', 'Kamar Al-Ikhlas');
        $this->assertSame(0, $kamar->jumlah_santri);

        $santri = $this->createSantri('Zaidan Haris', '14450301', $kamar->id);

        $this->assertSame(1, $kamar->fresh()->jumlah_santri);
        $this->assertSame($kamar->id, $santri->kamar_id);
        $this->assertTrue(
            KamarSantri::where('santri_id', $santri->id)->where('kamar_id', $kamar->id)->exists()
        );
    }

    /**
     * 3. Verify creating a Santri without kamar_id leaves kamar counts unchanged.
     */
    public function test_santri_created_without_kamar_does_not_affect_counts(): void
    {
        $this->actingAs($this->admin);

        $kamar = $this->createKamar('KMR-ARCH-02', 'Kamar Al-Fajr');
        $this->assertSame(0, $kamar->jumlah_santri);

        $santri = $this->createSantri('Fathir Rizqi', '14450302', null);

        $this->assertSame(0, $kamar->fresh()->jumlah_santri);
        $this->assertNull($santri->kamar_id);
    }

    /**
     * 4. Verify moving a Santri between rooms via assignKamar domain helper updates both counters.
     */
    public function test_santri_moved_between_kamar_via_assign_helper_updates_both_counters(): void
    {
        $this->actingAs($this->admin);

        $kamarA = $this->createKamar('KMR-ARCH-03A', 'Kamar Raudhah A');
        $kamarB = $this->createKamar('KMR-ARCH-03B', 'Kamar Raudhah B');

        $santri = $this->createSantri('Bilal Hasyim', '14450303', $kamarA->id);

        $this->assertSame(1, $kamarA->fresh()->jumlah_santri);
        $this->assertSame(0, $kamarB->fresh()->jumlah_santri);

        // Move to Kamar B
        $santri->assignKamar($kamarB);

        $this->assertSame(0, $kamarA->fresh()->jumlah_santri);
        $this->assertSame(1, $kamarB->fresh()->jumlah_santri);
        $this->assertSame($kamarB->id, $santri->fresh()->kamar_id);
    }

    /**
     * 5. Verify moving a Santri between rooms via direct property assignment updates both counters.
     */
    public function test_santri_moved_between_kamar_via_property_assignment_updates_both_counters(): void
    {
        $this->actingAs($this->admin);

        $kamarA = $this->createKamar('KMR-ARCH-04A', 'Kamar Madinah A');
        $kamarB = $this->createKamar('KMR-ARCH-04B', 'Kamar Madinah B');

        $santri = $this->createSantri('Lukman Hakim', '14450304', $kamarA->id);

        $this->assertSame(1, $kamarA->fresh()->jumlah_santri);
        $this->assertSame(0, $kamarB->fresh()->jumlah_santri);

        // Move via direct property update
        $santri->kamar_id = $kamarB->id;
        $santri->save();

        $this->assertSame(0, $kamarA->fresh()->jumlah_santri);
        $this->assertSame(1, $kamarB->fresh()->jumlah_santri);
    }

    /**
     * 6. Verify deleting a Santri decreases room headcount.
     */
    public function test_santri_deleted_decreases_kamar_count(): void
    {
        $this->actingAs($this->admin);

        $kamar = $this->createKamar('KMR-ARCH-05', 'Kamar Makkah');
        $santri = $this->createSantri('Salman Al-Farisi', '14450305', $kamar->id);

        $this->assertSame(1, $kamar->fresh()->jumlah_santri);

        $santri->delete();

        $this->assertSame(0, $kamar->fresh()->jumlah_santri);
    }

    /**
     * 7. Verify activity log is created on Santri creating, updating, and deleting without duplicates.
     */
    public function test_santri_observer_creates_activity_logs_for_all_lifecycle_stages(): void
    {
        $this->actingAs($this->admin);

        $kamar = $this->createKamar('KMR-ARCH-06', 'Kamar Quba');
        $lastLogId = ActivityLog::max('id') ?? 0;

        // Creating
        $santri = $this->createSantri('Umar Faruq', '14450306', $kamar->id);

        $createLogs = ActivityLog::where('id', '>', $lastLogId)
            ->where('activity', 'like', 'Creating Santri %Umar Faruq%')
            ->get();
        $this->assertCount(1, $createLogs);
        $this->assertStringContainsString('Creating Santri Umar Faruq', $createLogs->first()->activity);

        // Updating
        $lastLogId = ActivityLog::max('id');
        $santri->update(['status' => 'Santri Alumni']);

        $updateLogs = ActivityLog::where('id', '>', $lastLogId)
            ->where('activity', 'like', 'Updating Santri %Umar Faruq%')
            ->get();
        $this->assertCount(1, $updateLogs);
        $this->assertStringContainsString('Updating Santri Umar Faruq', $updateLogs->first()->activity);

        // Deleting
        $lastLogId = ActivityLog::max('id');
        $santri->delete();

        $deleteLogs = ActivityLog::where('id', '>', $lastLogId)
            ->where('activity', 'like', 'Deleting Santri %Umar Faruq%')
            ->get();
        $this->assertCount(1, $deleteLogs);
        $this->assertStringContainsString('Deleting Santri Umar Faruq', $deleteLogs->first()->activity);
    }

    /**
     * 8. Verify SantriRoomCounterService recalculates kamar head count accurately.
     */
    public function test_room_counter_service_recalculates_headcount(): void
    {
        $kamar = $this->createKamar('KMR-ARCH-07', 'Kamar Mina');
        $service = app(SantriRoomCounterService::class);

        $s1 = $this->createSantri('Santri Mina 1', '14450307', $kamar->id);
        $s2 = $this->createSantri('Santri Mina 2', '14450308', $kamar->id);

        $this->assertSame(2, $kamar->fresh()->jumlah_santri);

        // Simulate artificial desync
        $kamar->update(['jumlah_santri' => 99]);
        $this->assertSame(99, $kamar->fresh()->jumlah_santri);

        // Recalculate
        $service->recalculate($kamar->id);
        $this->assertSame(2, $kamar->fresh()->jumlah_santri);
    }
}
