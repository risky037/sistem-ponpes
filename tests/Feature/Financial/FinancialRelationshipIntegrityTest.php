<?php

namespace Tests\Feature\Financial;

use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Setting;
use App\Models\Tabungan;
use App\Models\TransaksiTabungan;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancialRelationshipIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        $this->adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Keuangan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        Setting::firstOrCreate([
            'log_activity' => false,
            'whatsapp_feature' => false,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Keuangan',
            'email' => 'admin.keuangan@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($this->adminRole);
    }

    protected function createSantri(string $name, string $noInduk): Santri
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
            'kode' => 'KMR-'.substr($noInduk, -4),
        ]);

        $kelas = Kelas::firstOrCreate([
            'tingkatan' => 'Wustho',
            'kelas' => '1',
            'kode' => 'KLS-'.substr($noInduk, -4),
        ]);

        return Santri::create([
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
    }

    protected function createSantriWithTabungan(string $name, string $noInduk, int $initialSaldo = 0): array
    {
        $santri = $this->createSantri($name, $noInduk);

        $tabungan = Tabungan::create([
            'santri_id' => $santri->id,
            'saldo' => $initialSaldo,
        ]);

        return [$santri, $tabungan];
    }

    /**
     * 1. Verify $santri->tabungan returns a single Tabungan model instance, not a Collection.
     */
    public function test_santri_has_single_tabungan_relationship(): void
    {
        [$santri, $tabungan] = $this->createSantriWithTabungan('Ahmad Single', '10101010', 150000);

        $santri->refresh();

        $this->assertNotNull($santri->tabungan);
        $this->assertInstanceOf(Tabungan::class, $santri->tabungan);
        $this->assertNotInstanceOf(Collection::class, $santri->tabungan);
        $this->assertEquals($tabungan->id, $santri->tabungan->id);
        $this->assertEquals(150000, $santri->tabungan->saldo);

        // Verify inverse belongsTo relationship
        $this->assertInstanceOf(Santri::class, $tabungan->santri);
        $this->assertEquals($santri->id, $tabungan->santri->id);
    }

    /**
     * 2. Verify $santri->tabungan returns null when no tabungan account exists.
     */
    public function test_santri_without_tabungan_returns_null(): void
    {
        $santri = $this->createSantri('Santri Tanpa Tabungan', '20202020');

        $this->assertNull($santri->tabungan);
    }

    /**
     * 3. Verify $tabungan->transaksi returns transactions and inverse $transaksi->tabungan works.
     */
    public function test_tabungan_has_transactions_relationship(): void
    {
        [$santri, $tabungan] = $this->createSantriWithTabungan('Santri Transaksi', '30303030', 200000);

        $tr1 = TransaksiTabungan::create([
            'santri_id' => $santri->id,
            'tanggal_transaksi' => now()->toDateString(),
            'jenis_transaksi' => 'Setoran',
            'jumlah_transaksi' => 100000,
            'saldo_sebelumnya' => 100000,
            'saldo_saatini' => 200000,
        ]);

        $tr2 = TransaksiTabungan::create([
            'santri_id' => $santri->id,
            'tanggal_transaksi' => now()->toDateString(),
            'jenis_transaksi' => 'Penarikan',
            'jumlah_transaksi' => 50000,
            'saldo_sebelumnya' => 200000,
            'saldo_saatini' => 150000,
        ]);

        $tabungan->refresh();

        $this->assertInstanceOf(Collection::class, $tabungan->transaksi);
        $this->assertCount(2, $tabungan->transaksi);
        $this->assertTrue($tabungan->transaksi->contains('id', $tr1->id));
        $this->assertTrue($tabungan->transaksi->contains('id', $tr2->id));

        // Inverse test from transaction to tabungan
        $tr1->refresh();
        $this->assertInstanceOf(Tabungan::class, $tr1->tabungan);
        $this->assertEquals($tabungan->id, $tr1->tabungan->id);
    }

    /**
     * 4. Verify transfer flow still functions properly after relationship changes.
     */
    public function test_transfer_flow_still_works_after_relationship_change(): void
    {
        $this->actingAs($this->admin);

        [$pengirim, $tabunganPengirim] = $this->createSantriWithTabungan('Pengirim Rel', '40404040', 300000);
        [$penerima, $tabunganPenerima] = $this->createSantriWithTabungan('Penerima Rel', '50505050', 50000);

        $response = $this->post(route('transfer.store'), [
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'nominal' => 100000,
            'keterangan' => 'Uang jajan bulanan',
        ]);

        $response->assertStatus(302);

        $tabunganPengirim->refresh();
        $tabunganPenerima->refresh();

        $this->assertEquals(200000, $tabunganPengirim->saldo);
        $this->assertEquals(150000, $tabunganPenerima->saldo);

        // Verify transfer model relationships
        $transfer = Transfer::where('pengirim_id', $pengirim->id)
            ->where('penerima_id', $penerima->id)
            ->first();

        $this->assertNotNull($transfer);
        $this->assertInstanceOf(Santri::class, $transfer->pengirim);
        $this->assertInstanceOf(Santri::class, $transfer->penerima);
        $this->assertEquals($pengirim->id, $transfer->pengirim->id);
        $this->assertEquals($penerima->id, $transfer->penerima->id);

        // Verify Santri pengiriman / penerimaan relationships
        $this->assertTrue($pengirim->pengiriman->contains('id', $transfer->id));
        $this->assertTrue($penerima->penerimaan->contains('id', $transfer->id));
    }

    /**
     * 5. Verify transaction deposit and withdrawal flow still works after relationship refactoring.
     */
    public function test_transaction_flow_still_works_after_relationship_change(): void
    {
        $this->actingAs($this->admin);

        [$santri, $tabungan] = $this->createSantriWithTabungan('Santri Alir', '60606060', 100000);

        // Test deposit
        $depositResponse = $this->post(route('transaksi.store'), [
            'santri_noinduk' => '60606060',
            'debit' => 50000,
            'jenis_transaksi' => 'Setoran',
        ]);

        $depositResponse->assertStatus(302);
        $tabungan->refresh();
        $this->assertEquals(150000, $tabungan->saldo);

        // Test AJAX query lookup
        $ajaxResponse = $this->getJson(route('transaksi.index', [
            'no_induk' => '60606060',
            'jenis' => 'Penarikan',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJsonStructure([
            'data' => [
                'santri_id',
                'no_induk',
                'name',
                'saldo',
                'foto',
            ],
        ]);
        $ajaxResponse->assertJsonPath('data.santri_id', $santri->id);
        $ajaxResponse->assertJsonPath('data.no_induk', '60606060');

        // Test withdrawal
        $withdrawResponse = $this->patch(route('transaksi.update'), [
            'santri_noinduk' => '60606060',
            'kredit' => 20000,
            'jenis_transaksi' => 'Penarikan',
            'tujuan' => 'Beli Alat Tulis',
        ]);

        $withdrawResponse->assertStatus(302);
        $tabungan->refresh();
        $this->assertEquals(130000, $tabungan->saldo);

        // Verify daily withdrawal limit check blocks second withdrawal via AJAX
        $ajaxResponse2 = $this->getJson(route('transaksi.index', [
            'no_induk' => '60606060',
            'jenis' => 'Penarikan',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $ajaxResponse2->assertStatus(200);
        $this->assertStringContainsString('telah melakukan penarikan', $ajaxResponse2->json('message'));
    }

    /**
     * 6. Verify regression baseline: no regression in financial transaction flow.
     */
    public function test_no_regression_existing_financial_tests(): void
    {
        [$santri, $tabungan] = $this->createSantriWithTabungan('Santri Regresi', '70707070', 100000);

        $this->assertEquals(100000, $santri->tabungan->saldo);
        $this->assertEquals($santri->user->id, $tabungan->santri->user->id);
    }

    /**
     * 7. Lightweight query regression test: SaldoDebitController index does not execute N+1 user queries.
     */
    public function test_saldo_debit_index_avoids_n_plus_one_queries(): void
    {
        $this->actingAs($this->admin);

        for ($i = 1; $i <= 5; $i++) {
            $this->createSantriWithTabungan("Santri NPlus {$i}", "8080808{$i}", 10000 * $i);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->getJson(route('saldo_debit.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(5, count($queries), 'SaldoDebitController@index executed too many queries, indicating an N+1 problem.');
    }

    /**
     * 8. Lightweight query regression test: TransaksiController index avoids redundant sum('saldo') query.
     */
    public function test_transaksi_index_ajax_avoids_redundant_sum_query(): void
    {
        $this->actingAs($this->admin);

        [$santri, $tabungan] = $this->createSantriWithTabungan('Santri NoSum', '90909090', 250000);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->getJson(route('transaksi.index', [
            'no_induk' => '90909090',
            'jenis' => 'Setoran',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);

        $queries = collect(DB::getQueryLog())->pluck('query')->map(fn ($q) => strtolower($q));
        DB::disableQueryLog();

        // Ensure redundant aggregate query `sum(saldo)` is NOT executed
        $hasSumQuery = $queries->contains(function ($query) {
            return str_contains($query, 'sum(`saldo`)')
                || str_contains($query, 'sum("saldo")')
                || str_contains($query, 'sum(saldo)');
        });

        $this->assertFalse($hasSumQuery, 'TransaksiController@index should read saldo directly from tabungan relation instead of executing an extra sum query.');
    }
}
