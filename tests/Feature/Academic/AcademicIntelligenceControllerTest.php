<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicYear;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AcademicIntelligenceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $keuangan;

    protected User $santri;

    protected AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin Int',
            'email' => 'admin.int@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole('Administrator');

        $this->pengurus = User::create([
            'name' => 'Pengurus Int',
            'email' => 'pengurus.int@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->pengurus->assignRole('Pengurus');

        $this->keuangan = User::create([
            'name' => 'Keuangan Int',
            'email' => 'keuangan.int@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->keuangan->assignRole('Keuangan');

        $this->santri = User::create([
            'name' => 'Santri Int',
            'email' => 'santri.int@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->santri->assignRole('Santri');

        $this->year = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-01',
            'end_date' => '2024-12-31',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('academic.intelligence.index'))->assertRedirect(route('login'));
        $this->get(route('academic.intelligence.workload'))->assertRedirect(route('login'));
        $this->get(route('academic.intelligence.subjects'))->assertRedirect(route('login'));
    }

    public function test_santri_is_forbidden_from_academic_intelligence(): void
    {
        $this->actingAs($this->santri)
            ->get(route('academic.intelligence.index'))
            ->assertStatus(403);

        $this->actingAs($this->santri)
            ->get(route('academic.intelligence.workload'))
            ->assertStatus(403);

        $this->actingAs($this->santri)
            ->get(route('academic.intelligence.subjects'))
            ->assertStatus(403);
    }

    public function test_keuangan_is_forbidden_from_academic_intelligence(): void
    {
        $this->actingAs($this->keuangan)
            ->get(route('academic.intelligence.index'))
            ->assertStatus(403);

        $this->actingAs($this->keuangan)
            ->get(route('academic.intelligence.workload'))
            ->assertStatus(403);

        $this->actingAs($this->keuangan)
            ->get(route('academic.intelligence.subjects'))
            ->assertStatus(403);
    }

    public function test_administrator_can_view_intelligence_pages(): void
    {
        $this->actingAs($this->admin)
            ->get(route('academic.intelligence.index'))
            ->assertStatus(200)
            ->assertViewIs('pages.academic.intelligence.index')
            ->assertSee('Intelijen Akademik');

        $this->actingAs($this->admin)
            ->get(route('academic.intelligence.workload'))
            ->assertStatus(200)
            ->assertViewIs('pages.academic.intelligence.workload')
            ->assertSee('Beban Mengajar Guru');

        $this->actingAs($this->admin)
            ->get(route('academic.intelligence.subjects'))
            ->assertStatus(200)
            ->assertViewIs('pages.academic.intelligence.subjects')
            ->assertSee('Mata Pelajaran');
    }

    public function test_pengurus_can_view_intelligence_pages(): void
    {
        $this->actingAs($this->pengurus)
            ->get(route('academic.intelligence.index'))
            ->assertStatus(200)
            ->assertViewIs('pages.academic.intelligence.index');

        $this->actingAs($this->pengurus)
            ->get(route('academic.intelligence.workload'))
            ->assertStatus(200)
            ->assertViewIs('pages.academic.intelligence.workload');

        $this->actingAs($this->pengurus)
            ->get(route('academic.intelligence.subjects'))
            ->assertStatus(200)
            ->assertViewIs('pages.academic.intelligence.subjects');
    }

    public function test_refresh_action_clears_cache_and_redirects(): void
    {
        $this->actingAs($this->admin)
            ->post(route('academic.intelligence.refresh', $this->year->id))
            ->assertStatus(302);
    }

    public function test_snapshot_action_creates_snapshots_and_redirects(): void
    {
        $this->actingAs($this->admin)
            ->post(route('academic.intelligence.snapshot', $this->year->id))
            ->assertStatus(302);

        $this->assertDatabaseCount('academic_kpi_snapshots', 5);
    }

    public function test_filter_validation_rejects_invalid_academic_year(): void
    {
        $this->actingAs($this->admin)
            ->get(route('academic.intelligence.index', ['academic_year_id' => 99999]))
            ->assertSessionHasErrors(['academic_year_id']);
    }

    public function test_filter_validation_rejects_same_compare_year_as_main_year(): void
    {
        $this->actingAs($this->admin)
            ->get(route('academic.intelligence.index', [
                'academic_year_id' => $this->year->id,
                'compare_year_id' => $this->year->id,
            ]))
            ->assertSessionHasErrors(['compare_year_id']);
    }
}
