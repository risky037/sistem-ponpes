<?php

namespace Tests\Feature\Financial;

use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Setting;
use App\Models\Tabungan;
use App\Models\TransaksiTabungan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancialTransactionReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Role $adminRole;

    protected Role $keuanganRole;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        $this->adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $this->keuanganRole = Role::firstOrCreate(['name' => 'Keuangan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        Setting::firstOrCreate([
            'log_activity' => false,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Keuangan',
            'email' => 'keuangan@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($this->adminRole);
    }

    protected function createSantriWithTabungan(string $name, string $noInduk, int $initialSaldo = 0): array
    {
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)).'@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $user->assignRole('Santri');

        $kamar = Kamar::firstOrCreate([
            'nama' => 'Kamar Utama',
            'blok' => 'A',
            'jumlah_santri' => 0,
            'maksimal_santri' => 20,
            'kode' => 'KMR-MAIN',
        ]);

        $kelas = Kelas::firstOrCreate([
            'tingkatan' => 'Wustho',
            'kelas' => '1',
            'kode' => 'KLS-1',
        ]);

        $santri = Santri::create([
            'user_id' => $user->id,
            'kamar_id' => $kamar->id,
            'kelas_id' => $kelas->id,
            'no_induk' => $noInduk,
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => '2005-01-01',
            'tempat_lahir' => 'Sumenep',
            'tahun_masuk' => '2023-01-01',
            'tahun_masuk_hijriyah' => '1444',
            'status' => 'Santri Aktif',
        ]);

        $tabungan = Tabungan::create([
            'santri_id' => $santri->id,
            'saldo' => $initialSaldo,
        ]);

        return [$santri, $tabungan];
    }

    public function test_deposit_mutates_balance_and_creates_ledger_atomically(): void
    {
        $this->actingAs($this->admin);
        [$santri, $tabungan] = $this->createSantriWithTabungan('Santri Setor', '12345678', 50000);

        $response = $this->post(route('transaksi.store'), [
            'santri_noinduk' => '12345678',
            'debit' => 100000,
            'jenis_transaksi' => 'Setoran',
        ]);

        $response->assertStatus(302);
        $tabungan->refresh();

        $this->assertEquals(150000, $tabungan->saldo);

        $this->assertDatabaseHas('transaksi_tabungans', [
            'santri_id' => $santri->id,
            'jenis_transaksi' => 'Setoran',
            'jumlah_transaksi' => 100000,
            'saldo_sebelumnya' => 50000,
            'saldo_saatini' => 150000,
        ]);
    }

    public function test_deposit_rolls_back_ledger_if_balance_update_fails(): void
    {
        $this->actingAs($this->admin);
        [$santri, $tabungan] = $this->createSantriWithTabungan('Santri Rollback Setor', '22345678', 50000);

        Log::spy();

        Event::listen('eloquent.updating: App\Models\Tabungan', function () {
            throw new \Exception('Simulated database failure during deposit balance update');
        });

        $response = $this->post(route('transaksi.store'), [
            'santri_noinduk' => '22345678',
            'debit' => 100000,
            'jenis_transaksi' => 'Setoran',
        ]);

        $response->assertStatus(302);
        $tabungan->refresh();

        // Financial invariant: balance must NOT change
        $this->assertEquals(50000, $tabungan->saldo);

        // Transaction rollback invariant: no ledger entry must persist
        $this->assertDatabaseMissing('transaksi_tabungans', [
            'santri_id' => $santri->id,
            'jumlah_transaksi' => 100000,
        ]);

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return str_contains($message, 'TransaksiController store error')
                && array_key_exists('user_id', $context)
                && array_key_exists('request_uri', $context)
                && array_key_exists('method', $context)
                && array_key_exists('ip', $context)
                && array_key_exists('exception', $context);
        });
    }

    public function test_withdrawal_decrements_balance_and_creates_ledger_atomically(): void
    {
        $this->actingAs($this->admin);
        [$santri, $tabungan] = $this->createSantriWithTabungan('Santri Tarik', '32345678', 200000);

        $response = $this->patch(route('transaksi.update'), [
            'santri_noinduk' => '32345678',
            'kredit' => 50000,
            'jenis_transaksi' => 'Penarikan',
            'tujuan' => 'Beli Kitab',
        ]);

        $response->assertStatus(302);
        $tabungan->refresh();

        $this->assertEquals(150000, $tabungan->saldo);

        $this->assertDatabaseHas('transaksi_tabungans', [
            'santri_id' => $santri->id,
            'jenis_transaksi' => 'Penarikan',
            'jumlah_transaksi' => 50000,
            'saldo_sebelumnya' => 200000,
            'saldo_saatini' => 150000,
            'tujuan' => 'Beli Kitab',
        ]);
    }

    public function test_withdrawal_rejects_insufficient_balance_and_prevents_negative_balance(): void
    {
        $this->actingAs($this->admin);
        [$santri, $tabungan] = $this->createSantriWithTabungan('Santri Kurang Saldo', '42345678', 20000);

        $response = $this->patch(route('transaksi.update'), [
            'santri_noinduk' => '42345678',
            'kredit' => 50000,
            'jenis_transaksi' => 'Penarikan',
        ]);

        $response->assertStatus(302);
        $tabungan->refresh();

        // Financial invariant: balance must not become negative
        $this->assertEquals(20000, $tabungan->saldo);
        $this->assertGreaterThanOrEqual(0, $tabungan->saldo);

        // No ledger entry created
        $this->assertDatabaseMissing('transaksi_tabungans', [
            'santri_id' => $santri->id,
            'jenis_transaksi' => 'Penarikan',
        ]);
    }

    public function test_withdrawal_daily_limiter_is_scoped_to_individual_santri(): void
    {
        $this->actingAs($this->admin);
        [$santriA, $tabunganA] = $this->createSantriWithTabungan('Santri A', '52345678', 200000);
        [$santriB, $tabunganB] = $this->createSantriWithTabungan('Santri B', '62345678', 200000);

        // Santri A withdraws today
        $responseA = $this->patch(route('transaksi.update'), [
            'santri_noinduk' => '52345678',
            'kredit' => 20000,
            'jenis_transaksi' => 'Penarikan',
        ]);
        $responseA->assertStatus(302);
        $tabunganA->refresh();
        $this->assertEquals(180000, $tabunganA->saldo);

        // Santri B should NOT be blocked by Santri A's withdrawal
        $responseB = $this->patch(route('transaksi.update'), [
            'santri_noinduk' => '62345678',
            'kredit' => 30000,
            'jenis_transaksi' => 'Penarikan',
        ]);
        $responseB->assertStatus(302);
        $tabunganB->refresh();
        $this->assertEquals(170000, $tabunganB->saldo);

        // Santri A attempts a second withdrawal on the same day -> blocked by limiter
        $responseA2 = $this->patch(route('transaksi.update'), [
            'santri_noinduk' => '52345678',
            'kredit' => 20000,
            'jenis_transaksi' => 'Penarikan',
        ]);
        $responseA2->assertStatus(302);
        $tabunganA->refresh();
        // Balance remains 180,000 (not 160,000)
        $this->assertEquals(180000, $tabunganA->saldo);
    }

    public function test_withdrawal_rolls_back_if_ledger_or_balance_fails(): void
    {
        $this->actingAs($this->admin);
        [$santri, $tabungan] = $this->createSantriWithTabungan('Santri Rollback Tarik', '72345678', 200000);

        Log::spy();

        Event::listen('eloquent.updating: App\Models\Tabungan', function () {
            throw new \Exception('Simulated database error during withdrawal update');
        });

        $response = $this->patch(route('transaksi.update'), [
            'santri_noinduk' => '72345678',
            'kredit' => 50000,
            'jenis_transaksi' => 'Penarikan',
        ]);

        $response->assertStatus(302);
        $tabungan->refresh();

        // Financial invariant: balance remains 200,000
        $this->assertEquals(200000, $tabungan->saldo);

        // Ledger entry rolled back
        $this->assertDatabaseMissing('transaksi_tabungans', [
            'santri_id' => $santri->id,
            'jumlah_transaksi' => 50000,
        ]);

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return str_contains($message, 'TransaksiController update error')
                && array_key_exists('user_id', $context)
                && array_key_exists('request_uri', $context)
                && array_key_exists('method', $context)
                && array_key_exists('ip', $context)
                && array_key_exists('exception', $context);
        });
    }

    public function test_transfer_executes_atomically_between_sender_and_recipient(): void
    {
        $this->actingAs($this->admin);
        [$pengirim, $tabunganPengirim] = $this->createSantriWithTabungan('Pengirim Sukses', '82345678', 300000);
        [$penerima, $tabunganPenerima] = $this->createSantriWithTabungan('Penerima Sukses', '82345679', 100000);

        $response = $this->post(route('transfer.store'), [
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'nominal' => 75000,
            'keterangan' => 'Transfer Uang Buku',
        ]);

        $response->assertStatus(302);
        $tabunganPengirim->refresh();
        $tabunganPenerima->refresh();

        $this->assertEquals(225000, $tabunganPengirim->saldo);
        $this->assertEquals(175000, $tabunganPenerima->saldo);

        $this->assertDatabaseHas('transfers', [
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'jumlah_transfer' => 75000,
            'keterangan' => 'Transfer Uang Buku',
        ]);

        $this->assertDatabaseHas('transaksi_tabungans', [
            'santri_id' => $pengirim->id,
            'jenis_transaksi' => 'Penarikan',
            'jumlah_transaksi' => 75000,
            'saldo_sebelumnya' => 300000,
            'saldo_saatini' => 225000,
        ]);

        $this->assertDatabaseHas('transaksi_tabungans', [
            'santri_id' => $penerima->id,
            'jenis_transaksi' => 'Setoran',
            'jumlah_transaksi' => 75000,
            'saldo_sebelumnya' => 100000,
            'saldo_saatini' => 175000,
        ]);
    }

    public function test_transfer_fails_and_rolls_back_if_sender_has_insufficient_balance(): void
    {
        $this->actingAs($this->admin);
        [$pengirim, $tabunganPengirim] = $this->createSantriWithTabungan('Pengirim Miskin', '92345678', 30000);
        [$penerima, $tabunganPenerima] = $this->createSantriWithTabungan('Penerima Cukup', '92345679', 100000);

        $response = $this->post(route('transfer.store'), [
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'nominal' => 50000,
            'keterangan' => 'Transfer Gagal Saldo Kurang',
        ]);

        $response->assertStatus(302);
        $tabunganPengirim->refresh();
        $tabunganPenerima->refresh();

        // Financial invariant: neither balance changed
        $this->assertEquals(30000, $tabunganPengirim->saldo);
        $this->assertEquals(100000, $tabunganPenerima->saldo);

        $this->assertDatabaseMissing('transfers', [
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
        ]);
    }

    public function test_transfer_rolls_back_all_mutations_if_recipient_credit_fails(): void
    {
        $this->actingAs($this->admin);
        [$pengirim, $tabunganPengirim] = $this->createSantriWithTabungan('Pengirim Rollback', '11112222', 200000);
        [$penerima, $tabunganPenerima] = $this->createSantriWithTabungan('Penerima Rollback', '33334444', 50000);

        Log::spy();

        // Fail when saving recipient's tabungan (second tabungan update)
        $targetPenerimaTabunganId = $tabunganPenerima->id;
        Event::listen('eloquent.updating: App\Models\Tabungan', function ($model) use ($targetPenerimaTabunganId) {
            if ($model->id === $targetPenerimaTabunganId) {
                throw new \Exception('Simulated recipient tabungan update failure');
            }
        });

        $response = $this->post(route('transfer.store'), [
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'nominal' => 50000,
        ]);

        $response->assertStatus(302);
        $tabunganPengirim->refresh();
        $tabunganPenerima->refresh();

        // Atomicity invariant: Sender was NOT debited because recipient update failed
        $this->assertEquals(200000, $tabunganPengirim->saldo);
        $this->assertEquals(50000, $tabunganPenerima->saldo);

        $this->assertDatabaseMissing('transfers', [
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
        ]);

        $this->assertDatabaseMissing('transaksi_tabungans', [
            'santri_id' => $pengirim->id,
            'jumlah_transaksi' => 50000,
        ]);

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return str_contains($message, 'TransferController store error')
                && array_key_exists('user_id', $context)
                && array_key_exists('request_uri', $context)
                && array_key_exists('method', $context)
                && array_key_exists('ip', $context)
                && array_key_exists('exception', $context);
        });
    }

    public function test_transfer_request_rejects_negative_or_zero_nominal(): void
    {
        $this->actingAs($this->admin);
        [$pengirim] = $this->createSantriWithTabungan('Pengirim Validasi', '55556666', 100000);
        [$penerima] = $this->createSantriWithTabungan('Penerima Validasi', '77778888', 100000);

        $responseZero = $this->post(route('transfer.store'), [
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'nominal' => 0,
        ]);
        $responseZero->assertSessionHasErrors('nominal');

        $responseNegative = $this->post(route('transfer.store'), [
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'nominal' => -50000,
        ]);
        $responseNegative->assertSessionHasErrors('nominal');
    }

    public function test_single_tabungan_store_creates_account_and_initial_ledger_atomically(): void
    {
        $this->actingAs($this->admin);

        $user = User::create([
            'name' => 'Santri Baru Tabungan',
            'email' => 'santribaru@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $user->assignRole('Santri');

        $kamar = Kamar::firstOrCreate([
            'nama' => 'Kamar Baru',
            'blok' => 'B',
            'jumlah_santri' => 0,
            'maksimal_santri' => 20,
            'kode' => 'KMR-BARU',
        ]);

        $kelas = Kelas::firstOrCreate([
            'tingkatan' => 'Wustho',
            'kelas' => '2',
            'kode' => 'KLS-2',
        ]);

        $santri = Santri::create([
            'user_id' => $user->id,
            'kamar_id' => $kamar->id,
            'kelas_id' => $kelas->id,
            'no_induk' => '99998888',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => '2005-01-01',
            'tempat_lahir' => 'Sumenep',
            'tahun_masuk' => '2023-01-01',
            'tahun_masuk_hijriyah' => '1444',
            'status' => 'Santri Aktif',
        ]);

        $response = $this->post(route('saldo_debit.store'), [
            'santri_id' => $santri->id,
            'saldo' => 50000,
            'keterangan' => 'Tabungan Pembuka',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('tabungans', [
            'santri_id' => $santri->id,
            'saldo' => 50000,
        ]);

        $this->assertDatabaseHas('transaksi_tabungans', [
            'santri_id' => $santri->id,
            'jenis_transaksi' => 'Setoran',
            'jumlah_transaksi' => 50000,
            'saldo_saatini' => 50000,
        ]);
    }

    public function test_tabungan_destroy_deletes_account_and_records_within_transaction(): void
    {
        $this->actingAs($this->admin);
        [$santri, $tabungan] = $this->createSantriWithTabungan('Santri Dihapus', '12123434', 100000);

        TransaksiTabungan::create([
            'santri_id' => $santri->id,
            'tanggal_transaksi' => date('Y-m-d'),
            'jenis_transaksi' => 'Setoran',
            'jumlah_transaksi' => 100000,
            'saldo_saatini' => 100000,
        ]);

        $response = $this->delete(route('saldo_debit.destroy', $tabungan));
        $response->assertStatus(302);

        $this->assertDatabaseMissing('tabungans', [
            'id' => $tabungan->id,
        ]);

        $this->assertDatabaseMissing('transaksi_tabungans', [
            'santri_id' => $santri->id,
        ]);
    }
}
