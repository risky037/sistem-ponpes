<?php

namespace Tests\Feature\Database;

use App\Models\Santri;
use App\Models\Setting;
use App\Models\Tabungan;
use App\Models\TransaksiTabungan;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseIntegrityConstraintsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        Setting::firstOrCreate([
            'log_activity' => false,
            'whatsapp_feature' => false,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Schema Test',
            'email' => 'admin.schema@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole('Administrator');
    }

    protected function createSantri(string $name, string $noInduk): Santri
    {
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)).'@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $user->assignRole('Santri');

        return Santri::create([
            'user_id' => $user->id,
            'no_induk' => $noInduk,
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => '2005-01-01',
            'tempat_lahir' => 'Kediri',
            'tahun_masuk' => '2023-01-01',
            'tahun_masuk_hijriyah' => '1444',
            'status' => 'Santri Aktif',
        ]);
    }

    public function test_tabungan_santri_id_unique_constraint_rejects_duplicate_account(): void
    {
        $santri = $this->createSantri('Santri Unique Test', '10101010');

        Tabungan::create([
            'santri_id' => $santri->id,
            'saldo' => 50000,
        ]);

        $this->expectException(QueryException::class);

        // Attempt duplicate insertion
        Tabungan::create([
            'santri_id' => $santri->id,
            'saldo' => 100000,
        ]);
    }

    public function test_tabungan_saldo_check_constraint_rejects_negative_balance(): void
    {
        $santri = $this->createSantri('Santri Negative Check', '20202020');

        $driver = DB::getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'])) {
            $this->markTestSkipped('Check constraints only asserted on MySQL / MariaDB drivers');
        }

        $this->expectException(QueryException::class);

        DB::table('tabungans')->insert([
            'santri_id' => $santri->id,
            'saldo' => -5000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_transfer_jumlah_check_constraint_rejects_zero_or_negative_amount(): void
    {
        $pengirim = $this->createSantri('Pengirim Check', '30303030');
        $penerima = $this->createSantri('Penerima Check', '40404040');

        $driver = DB::getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'])) {
            $this->markTestSkipped('Check constraints only asserted on MySQL / MariaDB drivers');
        }

        $this->expectException(QueryException::class);

        DB::table('transfers')->insert([
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'jumlah_transfer' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_transfer_parties_check_constraint_rejects_self_transfer(): void
    {
        $santri = $this->createSantri('Santri Self Transfer', '50505050');

        $driver = DB::getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'])) {
            $this->markTestSkipped('Check constraints only asserted on MySQL / MariaDB drivers');
        }

        $this->expectException(QueryException::class);

        DB::table('transfers')->insert([
            'pengirim_id' => $santri->id,
            'penerima_id' => $santri->id,
            'jumlah_transfer' => 50000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_on_delete_restrict_prevents_cascading_deletion_of_santri_with_active_tabungan(): void
    {
        $santri = $this->createSantri('Santri Tabungan Restrict', '60606060');

        Tabungan::create([
            'santri_id' => $santri->id,
            'saldo' => 100000,
        ]);

        $this->expectException(QueryException::class);

        // Attempting to delete the santri parent row must fail with foreign key violation
        $santri->delete();
    }

    public function test_on_delete_restrict_prevents_cascading_deletion_of_santri_with_transfers(): void
    {
        $pengirim = $this->createSantri('Pengirim Restrict', '70707070');
        $penerima = $this->createSantri('Penerima Restrict', '80808080');

        Transfer::create([
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'jumlah_transfer' => 50000,
        ]);

        $this->expectException(QueryException::class);

        // Attempting to delete sender must fail with foreign key constraint violation
        $pengirim->delete();
    }

    public function test_on_delete_restrict_prevents_cascading_deletion_of_santri_with_transaction_ledger(): void
    {
        $santri = $this->createSantri('Santri Ledger Restrict', '90909090');

        TransaksiTabungan::create([
            'santri_id' => $santri->id,
            'tanggal_transaksi' => now()->toDateString(),
            'jenis_transaksi' => 'Setoran',
            'jumlah_transaksi' => 50000,
            'saldo_sebelumnya' => 0,
            'saldo_saatini' => 50000,
        ]);

        $this->expectException(QueryException::class);

        // Attempting to delete santri must fail with foreign key constraint violation
        $santri->delete();
    }

    public function test_migration_rollback_and_reapply_integrity(): void
    {
        // 1. Rollback the constraints migration
        $rollbackExitCode = Artisan::call('migrate:rollback', ['--step' => 1]);
        $this->assertEquals(0, $rollbackExitCode);

        // Assert that unique constraint was dropped and duplicate is permitted under old schema
        $santri = $this->createSantri('Santri Rollback Reapply', '12121212');

        try {
            $tabungan1 = Tabungan::create([
                'santri_id' => $santri->id,
                'saldo' => 10000,
            ]);
            $tabungan2 = Tabungan::create([
                'santri_id' => $santri->id,
                'saldo' => 20000,
            ]);
            $this->assertNotNull($tabungan2);

            $tabungan1->delete();
            $tabungan2->delete();

            // 2. Re-apply the constraints migration
            $migrateExitCode = Artisan::call('migrate');
            $this->assertEquals(0, $migrateExitCode);

            // Assert that unique constraint is re-enforced
            Tabungan::create([
                'santri_id' => $santri->id,
                'saldo' => 30000,
            ]);

            try {
                Tabungan::create([
                    'santri_id' => $santri->id,
                    'saldo' => 40000,
                ]);
                $this->fail('Expected QueryException was not thrown');
            } catch (QueryException $e) {
                // Expected unique constraint failure
                $this->assertTrue(true);
            }
        } finally {
            // Clean up completely to avoid leaving committed records after DDL implicit commits
            Tabungan::where('santri_id', $santri->id)->delete();
            $user = $santri->user;
            $santri->delete();
            if ($user) {
                $user->delete();
            }
            if ($this->admin) {
                $this->admin->delete();
            }
        }
    }
}
