<?php

namespace Tests\Feature\DataTables;

use App\Models\ActivityLog;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Tabungan;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DataTablesAjaxResponseTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Keuangan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin_dt@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole('Administrator');
    }

    protected function createSantri(string $name, string $noInduk): Santri
    {
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('Santri');

        $kamar = Kamar::firstOrCreate([
            'kode' => 'KMR-'.substr($noInduk, -4),
            'nama' => 'Kamar '.substr($noInduk, -4),
            'blok' => 'A',
        ]);

        $kelas = Kelas::firstOrCreate([
            'kode' => 'KLS-'.substr($noInduk, -4),
            'tingkatan' => 'Wustho',
            'kelas' => '1',
        ]);

        return Santri::create([
            'user_id' => $user->id,
            'kamar_id' => $kamar->id,
            'kelas_id' => $kelas->id,
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
     * 1. Verify Users DataTable AJAX returns HTTP 200 and standard DataTables JSON structure.
     */
    public function test_users_datatable_ajax_response_returns_200_and_valid_json(): void
    {
        $this->actingAs($this->admin);

        $testUser = User::create([
            'name' => 'Staff User',
            'email' => 'staff_dt@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $testUser->assignRole('Pengurus');

        $response = $this->getJson(route('users.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals('Staff User', $data[0]['name']);
    }

    /**
     * 2. Verify Kelas DataTable AJAX returns HTTP 200 and standard DataTables JSON structure.
     */
    public function test_kelas_datatable_ajax_response_returns_200_and_valid_json(): void
    {
        $this->actingAs($this->admin);

        Kelas::create([
            'kode' => 'KLS-TEST01',
            'tingkatan' => 'Wustho',
            'kelas' => '1B',
            'keterangan' => 'Kelas Uji Coba',
        ]);

        $response = $this->getJson(route('kelas.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals('1B', $data[0]['kelas']);
    }

    /**
     * 3. Verify Santri DataTable AJAX returns HTTP 200 and standard DataTables JSON structure.
     */
    public function test_santri_datatable_ajax_response_returns_200_and_valid_json(): void
    {
        $this->actingAs($this->admin);

        $this->createSantri('Santri DT Test', '99887766');

        $response = $this->getJson(route('santri.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals('99887766', $data[0]['no_induk']);
    }

    /**
     * 4. Verify Kamar DataTable AJAX returns HTTP 200 and standard DataTables JSON structure.
     */
    public function test_kamar_datatable_ajax_response_returns_200_and_valid_json(): void
    {
        $this->actingAs($this->admin);

        Kamar::create([
            'kode' => 'KMR-TEST01',
            'nama' => 'Kamar Al-Ikhlas',
            'blok' => 'A',
            'jumlah_santri' => 10,
            'maksimal_santri' => 20,
        ]);

        $response = $this->getJson(route('kamar.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals('Kamar Al-Ikhlas', $data[0]['nama']);
    }

    /**
     * 5. Verify Transfer DataTable AJAX returns HTTP 200 and standard DataTables JSON structure.
     */
    public function test_transfer_datatable_ajax_response_returns_200_and_valid_json(): void
    {
        $this->actingAs($this->admin);

        $pengirim = $this->createSantri('Sender User', '11223344');
        $penerima = $this->createSantri('Receiver User', '55667788');

        Transfer::create([
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'jumlah_transfer' => 50000,
            'keterangan' => 'Transfer Uji Coba DT',
        ]);

        $response = $this->getJson(route('transfer.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals(50000, $data[0]['jumlah_transfer']);
    }

    /**
     * 6. Verify Riwayat DataTable AJAX returns HTTP 200 and standard DataTables JSON structure.
     */
    public function test_riwayat_datatable_ajax_response_returns_200_and_valid_json(): void
    {
        $this->actingAs($this->admin);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'activity' => 'Login to dashboard from unit test',
        ]);

        $response = $this->getJson(route('riwayat.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals($this->admin->name, $data[0]['user']);
    }

    /**
     * 7. Verify Saldo Debit (Tabungan) DataTable AJAX returns HTTP 200 and standard DataTables JSON structure.
     */
    public function test_saldo_debit_datatable_ajax_response_returns_200_and_valid_json(): void
    {
        $this->actingAs($this->admin);

        $santri = $this->createSantri('Santri Tabungan DT', '77889900');

        Tabungan::create([
            'santri_id' => $santri->id,
            'saldo' => 150000,
        ]);

        $response = $this->getJson(route('saldo_debit.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals('77889900', $data[0]['no_induk']);
        $this->assertEquals('Santri Tabungan DT', $data[0]['nama']);
    }
}
