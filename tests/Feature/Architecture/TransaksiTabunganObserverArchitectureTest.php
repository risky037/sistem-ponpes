<?php

namespace Tests\Feature\Architecture;

use App\Models\ActivityLog;
use App\Models\Santri;
use App\Models\Setting;
use App\Models\TransaksiTabungan;
use App\Models\User;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransaksiTabunganObserverArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Santri $santri;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Transaksi Architecture',
            'email' => 'admin.transaksi.arch@example.com',
            'password' => 'Password123!',
        ]);
        $this->admin->assignRole($adminRole);

        Setting::firstOrCreate([
            'id' => 1,
        ], [
            'log_activity' => true,
            'whatsapp_feature' => true,
            'whatsapp_api_key' => 'fake-api-key',
            'sender' => 628123456789,
        ])->update([
            'log_activity' => true,
            'whatsapp_feature' => true,
            'whatsapp_api_key' => 'fake-api-key',
            'sender' => 628123456789,
        ]);

        WhatsappMessage::firstOrCreate([
            'id' => 1,
        ], [
            'pesan_setor_tunai' => 'Halo {nama}, setoran {nominal} pada {tanggal} berhasil.',
            'pesan_tarik_tunai' => 'Halo {nama}, penarikan {nominal} pada {tanggal} untuk {tujuan} berhasil.',
        ]);

        $santriUser = User::create([
            'name' => 'Santri Tabungan Arch',
            'email' => 'santri.tabungan.arch@example.com',
            'password' => 'Password123!',
        ]);

        $this->santri = Santri::create([
            'user_id' => $santriUser->id,
            'no_induk' => '14450888',
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
     * 1. Verify TransaksiTabunganActivityObserver and TransaksiTabunganObserver are registered.
     */
    public function test_transaksi_tabungan_observers_are_registered(): void
    {
        $this->assertTrue(
            Event::hasListeners('eloquent.creating: '.TransaksiTabungan::class),
            "TransaksiTabungan 'creating' listener must be registered."
        );
        $this->assertTrue(
            Event::hasListeners('eloquent.created: '.TransaksiTabungan::class),
            "TransaksiTabungan 'created' listener must be registered."
        );
        $this->assertTrue(
            Event::hasListeners('eloquent.updating: '.TransaksiTabungan::class),
            "TransaksiTabungan 'updating' listener must be registered."
        );
        $this->assertTrue(
            Event::hasListeners('eloquent.deleting: '.TransaksiTabungan::class),
            "TransaksiTabungan 'deleting' listener must be registered."
        );
    }

    /**
     * 2. Verify creating a transaction generates the expected activity log without duplicates.
     */
    public function test_creating_transaction_generates_activity_log(): void
    {
        $this->actingAs($this->admin);

        $lastLogId = ActivityLog::max('id') ?? 0;

        $transaksi = TransaksiTabungan::create([
            'santri_id' => $this->santri->id,
            'tanggal_transaksi' => '2026-09-20',
            'jenis_transaksi' => 'Setoran',
            'jumlah_transaksi' => 75000,
            'saldo_sebelumnya' => 0,
            'saldo_saatini' => 75000,
        ]);

        $logs = ActivityLog::where('id', '>', $lastLogId)
            ->where('activity', 'like', 'Creating TransaksiTabungan%')
            ->get();

        $this->assertCount(1, $logs, 'Exactly one activity log should be created on transaction creation.');
        $this->assertStringContainsString(
            'Creating TransaksiTabungan Santri Tabungan Arch Setoran 75000',
            $logs->first()->activity
        );
    }

    /**
     * 3. Verify updating a transaction generates the expected activity log.
     */
    public function test_updating_transaction_generates_activity_log(): void
    {
        $this->actingAs($this->admin);

        $transaksi = TransaksiTabungan::create([
            'santri_id' => $this->santri->id,
            'tanggal_transaksi' => '2026-09-20',
            'jenis_transaksi' => 'Setoran',
            'jumlah_transaksi' => 50000,
            'saldo_sebelumnya' => 0,
            'saldo_saatini' => 50000,
        ]);

        $lastLogId = ActivityLog::max('id');

        $transaksi->update([
            'jumlah_transaksi' => 100000,
            'saldo_saatini' => 100000,
        ]);

        $logs = ActivityLog::where('id', '>', $lastLogId)
            ->where('activity', 'like', 'Updating TransaksiTabungan%')
            ->get();

        $this->assertCount(1, $logs, 'Exactly one activity log should be created on transaction update.');
        $this->assertStringContainsString(
            'Updating TransaksiTabungan Santri Tabungan Arch Setoran 100000',
            $logs->first()->activity
        );
    }

    /**
     * 4. Verify deleting a transaction generates the expected activity log.
     */
    public function test_deleting_transaction_generates_activity_log(): void
    {
        $this->actingAs($this->admin);

        $transaksi = TransaksiTabungan::create([
            'santri_id' => $this->santri->id,
            'tanggal_transaksi' => '2026-09-20',
            'jenis_transaksi' => 'Penarikan',
            'jumlah_transaksi' => 25000,
            'saldo_sebelumnya' => 50000,
            'saldo_saatini' => 25000,
            'tujuan' => 'Beli Kitab',
        ]);

        $lastLogId = ActivityLog::max('id');

        $transaksi->delete();

        $logs = ActivityLog::where('id', '>', $lastLogId)
            ->where('activity', 'like', 'Deleting TransaksiTabungan%')
            ->get();

        $this->assertCount(1, $logs, 'Exactly one activity log should be created on transaction delete.');
        $this->assertStringContainsString(
            'Deleting TransaksiTabungan Santri Tabungan Arch Penarikan 25000',
            $logs->first()->activity
        );
    }

    /**
     * 5. Verify existing WhatsApp notification dispatch behavior remains unchanged.
     */
    public function test_existing_whatsapp_notification_behavior_remains_unchanged(): void
    {
        $this->actingAs($this->admin);

        Http::fake();

        TransaksiTabungan::create([
            'santri_id' => $this->santri->id,
            'tanggal_transaksi' => '2026-09-20',
            'jenis_transaksi' => 'Setoran',
            'jumlah_transaksi' => 50000,
            'saldo_sebelumnya' => 0,
            'saldo_saatini' => 50000,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'connect.labelin.co/send-message')
                && $request['api_key'] === 'fake-api-key'
                && str_contains($request['message'], 'Santri Tabungan Arch')
                && str_contains($request['message'], number_format(50000));
        });
    }

    /**
     * 6. Verify withdrawal transaction triggers WhatsApp notification with destination details.
     */
    public function test_withdrawal_transaction_triggers_whatsapp_notification_with_destination(): void
    {
        $this->actingAs($this->admin);

        Http::fake();

        TransaksiTabungan::create([
            'santri_id' => $this->santri->id,
            'tanggal_transaksi' => '2026-09-20',
            'jenis_transaksi' => 'Penarikan',
            'jumlah_transaksi' => 30000,
            'saldo_sebelumnya' => 50000,
            'saldo_saatini' => 20000,
            'tujuan' => 'Uang Saku',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'connect.labelin.co/send-message')
                && str_contains($request['message'], 'Uang Saku');
        });
    }

    /**
     * 7. Verify disabled WhatsApp feature skips notification.
     */
    public function test_disabled_whatsapp_feature_skips_notification(): void
    {
        $this->actingAs($this->admin);

        Setting::first()->update(['whatsapp_feature' => false]);
        Http::fake();

        TransaksiTabungan::create([
            'santri_id' => $this->santri->id,
            'tanggal_transaksi' => '2026-09-20',
            'jenis_transaksi' => 'Setoran',
            'jumlah_transaksi' => 20000,
            'saldo_sebelumnya' => 0,
            'saldo_saatini' => 20000,
        ]);

        Http::assertNothingSent();
    }
}
