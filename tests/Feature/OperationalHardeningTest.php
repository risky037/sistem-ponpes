<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use App\Helpers\Whatsapp;
use App\Models\Santri;
use App\Models\Tabungan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OperationalHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Test 1: Login page renders with Remember Me checkbox, Forgot Password link, and Onboarding Modal.
     */
    public function test_login_page_renders_with_remember_me_and_forgot_password_and_onboarding_modal(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('name="remember"', false);
        $response->assertSee(route('password.request'), false);
        $response->assertSee('Lupa Password?');
        $response->assertSee('modalHelpOnboarding');
        $response->assertSee('Petunjuk Akses Akun', false);
    }

    /**
     * Test 2: Forgot Password page renders with WhatsApp template as primary and native reset form as alternative.
     */
    public function test_forgot_password_page_renders_with_primary_whatsapp_template_and_reset_form(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
        $response->assertSee('Bantuan Cepat via WhatsApp Admin');
        $response->assertSee('wa.me', false);
        $response->assertSee(route('password.email'), false);
        $response->assertSee(route('login'), false);
        $response->assertSee('Kembali ke Halaman Login');
    }

    /**
     * Test 3: Forgot Password form requires valid email submission.
     */
    public function test_forgot_password_post_validates_email(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'invalid-email-format',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test 4: Helper::formatDate and Helper::formatDateTime format accurately in Indonesian locale.
     */
    public function test_date_formatter_helper_and_blade_directives_render_indonesian_locale(): void
    {
        $date = Carbon::create(2025, 8, 17, 10, 30, 0);

        $formattedDate = Helper::formatDate($date);
        $this->assertEquals('17 Agustus 2025', $formattedDate);

        $formattedDateTime = Helper::formatDateTime($date);
        $this->assertEquals('17 Agustus 2025 10:30', $formattedDateTime);

        // Null and empty strings return '-'
        $this->assertEquals('-', Helper::formatDate(null));
        $this->assertEquals('-', Helper::formatDateTime(''));
    }

    /**
     * Test 5: Whatsapp::adminResetUrl generates valid wa.me URL with pre-filled message.
     */
    public function test_whatsapp_admin_reset_url_generates_valid_link(): void
    {
        $url = Whatsapp::adminResetUrl('2024001', 'Ahmad Fauzi');

        $this->assertStringStartsWith('https://wa.me/', $url);
        $this->assertStringContainsString('Ahmad%20Fauzi', $url);
        $this->assertStringContainsString('2024001', $url);
    }

    /**
     * Test 6: Unauthenticated guest accessing /panduan is redirected to login.
     */
    public function test_documentation_portal_redirects_unauthenticated_guest_to_login(): void
    {
        $response = $this->get(route('documentation.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test 7: Documentation portal renders correctly for authenticated users across roles.
     */
    public function test_documentation_portal_renders_for_authenticated_roles(): void
    {
        $admin = User::factory()->create(['name' => 'Administrator Test']);
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)->get(route('documentation.index'));
        $response->assertStatus(200);
        $response->assertSee('Panduan Penggunaan Sistem DIGITREN');
        $response->assertSee('Administrator');
        $response->assertSee('Manajemen Pengguna', false);

        $guru = User::factory()->create(['name' => 'Ustadz Guru']);
        $guru->assignRole('Guru');

        $responseGuru = $this->actingAs($guru)->get(route('documentation.index'));
        $responseGuru->assertStatus(200);
        $responseGuru->assertSee('Guru / Asatidz');
        $responseGuru->assertSee('Input Presensi Kehadiran Santri');
    }

    /**
     * Test 8: Transaction terminal page renders KPI cards and operator tab controls.
     */
    public function test_transaction_terminal_renders_kpi_cards_and_operator_tabs(): void
    {
        $keuangan = User::factory()->create(['name' => 'Staf Keuangan']);
        $keuangan->assignRole('Keuangan');

        $response = $this->actingAs($keuangan)->get(route('transaksi.index'));

        $response->assertStatus(200);
        $response->assertSee('Setoran Hari Ini');
        $response->assertSee('Penarikan Hari Ini');
        $response->assertSee('Aktivitas Transaksi Hari Ini');
        $response->assertSee('Transaksi Terakhir Hari Ini');
        $response->assertSee('Nomor Induk Santri (NIS)');
    }

    /**
     * Test 9: Transaction AJAX lookup returns student details and withdrawal eligibility.
     */
    public function test_transaction_ajax_returns_student_data_and_eligibility(): void
    {
        $keuangan = User::factory()->create(['name' => 'Staf Keuangan']);
        $keuangan->assignRole('Keuangan');

        $santriUser = User::factory()->create(['name' => 'Santri Budi']);
        $santri = Santri::factory()->create([
            'user_id' => $santriUser->id,
            'no_induk' => '12345678',
        ]);
        Tabungan::create([
            'santri_id' => $santri->id,
            'saldo' => 150000,
        ]);

        $response = $this->actingAs($keuangan)->getJson(route('transaksi.index', [
            'no_induk' => '12345678',
            'jenis' => 'Setoran',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'no_induk' => '12345678',
                'name' => 'Santri Budi',
                'can_withdraw' => true,
            ],
        ]);
    }

    /**
     * Test 10: Custom error views render with brand layout and navigation buttons.
     */
    public function test_custom_error_pages_render_with_brand_layout(): void
    {
        $view403 = view('errors.403', ['exception' => null])->render();
        $this->assertStringContainsString('403', $view403);
        $this->assertStringContainsString('Akses Terbatas / Ditolak', $view403);
        $this->assertStringContainsString('Kembali ke Dashboard', $view403);

        $view404 = view('errors.404')->render();
        $this->assertStringContainsString('404', $view404);
        $this->assertStringContainsString('Halaman Tidak Ditemukan', $view404);

        $view500 = view('errors.500')->render();
        $this->assertStringContainsString('500', $view500);
        $this->assertStringContainsString('Terjadi Kendala pada Server', $view500);
    }
}
