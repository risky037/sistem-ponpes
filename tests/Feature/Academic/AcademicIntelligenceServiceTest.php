<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicKpiSnapshot;
use App\Models\AcademicPerformanceSummary;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\User;
use App\Services\Academic\AcademicIntelligenceService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AcademicIntelligenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicIntelligenceService $intelligenceService;

    protected AcademicYear $year1;

    protected AcademicYear $year2;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->intelligenceService = app(AcademicIntelligenceService::class);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin.intelligence@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole('Administrator');

        $this->year1 = AcademicYear::create([
            'name' => '2023/2024',
            'semester' => 'Genap',
            'start_date' => '2024-01-01',
            'end_date' => '2024-06-30',
            'is_active' => false,
        ]);

        $this->year2 = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-01',
            'end_date' => '2024-12-31',
            'is_active' => true,
        ]);
    }

    public function test_get_dashboard_overview_caches_results(): void
    {
        Cache::flush();

        $data1 = $this->intelligenceService->getDashboardOverview($this->year2);
        $this->assertArrayHasKey('kpis', $data1);
        $this->assertArrayHasKey('attendance', $data1);
        $this->assertArrayHasKey('grades', $data1);
        $this->assertArrayHasKey('workload_stats', $data1);
        $this->assertArrayHasKey('class_health', $data1);

        $this->assertTrue(Cache::has("academic_intelligence_kpi_{$this->year2->id}"));

        // When forceFresh is called, cache is refreshed
        $data2 = $this->intelligenceService->getDashboardOverview($this->year2, true);
        $this->assertNotNull($data2);
    }

    public function test_capture_snapshot_creates_all_five_snapshot_types(): void
    {
        $this->assertDatabaseCount('academic_kpi_snapshots', 0);

        $snapshots = $this->intelligenceService->captureSnapshot($this->year2, $this->admin);

        $this->assertCount(5, $snapshots);
        $this->assertDatabaseCount('academic_kpi_snapshots', 5);

        $types = $snapshots->pluck('snapshot_type')->all();
        $this->assertContains(AcademicKpiSnapshot::TYPE_INSTITUTIONAL_KPI, $types);
        $this->assertContains(AcademicKpiSnapshot::TYPE_ATTENDANCE_ANALYTICS, $types);
        $this->assertContains(AcademicKpiSnapshot::TYPE_GRADE_DISTRIBUTION, $types);
        $this->assertContains(AcademicKpiSnapshot::TYPE_TEACHER_WORKLOAD, $types);
        $this->assertContains(AcademicKpiSnapshot::TYPE_OPERATIONAL_HEALTH, $types);

        $first = $snapshots->first();
        $this->assertSame($this->admin->id, $first->captured_by);

        // Verify retrieval
        $retrieved = $this->intelligenceService->getHistoricalSnapshots($this->year2);
        $this->assertCount(5, $retrieved);
    }

    protected function createSantri(string $name, string $email, string $noInduk): Santri
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('Secret123!'),
        ]);

        return Santri::create([
            'user_id' => $user->id,
            'no_induk' => $noInduk,
            'nis' => $noInduk,
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => 628123456789,
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Kediri',
            'status' => 'Santri Aktif',
        ]);
    }

    public function test_get_historical_comparison_computes_deltas_between_years(): void
    {
        $kelas = Kelas::create(['kode' => '7A', 'kelas' => 'A', 'tingkatan' => '7']);

        // Year 1: 1 enrollment with avg score 80.0
        $s1 = $this->createSantri('S1', 's1@y1.com', '801');
        $enr1 = AcademicEnrollment::create([
            'academic_year_id' => $this->year1->id,
            'santri_id' => $s1->id,
            'kelas_id' => $kelas->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => now(),
        ]);
        AcademicPerformanceSummary::create([
            'academic_enrollment_id' => $enr1->id,
            'attendance_rate' => 80.0,
            'average_score' => 80.0,
            'computation_status' => AcademicPerformanceSummary::STATUS_LENGKAP,
        ]);

        // Year 2: 2 enrollments with avg score 90.0
        $s2 = $this->createSantri('S2', 's2@y2.com', '802');
        $enr2 = AcademicEnrollment::create([
            'academic_year_id' => $this->year2->id,
            'santri_id' => $s2->id,
            'kelas_id' => $kelas->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => now(),
        ]);
        AcademicPerformanceSummary::create([
            'academic_enrollment_id' => $enr2->id,
            'attendance_rate' => 90.0,
            'average_score' => 90.0,
            'computation_status' => AcademicPerformanceSummary::STATUS_LENGKAP,
        ]);

        $s3 = $this->createSantri('S3', 's3@y2.com', '803');
        $enr3 = AcademicEnrollment::create([
            'academic_year_id' => $this->year2->id,
            'santri_id' => $s3->id,
            'kelas_id' => $kelas->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => now(),
        ]);
        AcademicPerformanceSummary::create([
            'academic_enrollment_id' => $enr3->id,
            'attendance_rate' => 90.0,
            'average_score' => 90.0,
            'computation_status' => AcademicPerformanceSummary::STATUS_LENGKAP,
        ]);

        $comparison = $this->intelligenceService->getHistoricalComparison($this->year2, $this->year1);

        $this->assertSame(2, $comparison['current']['active_enrollments']);
        $this->assertSame(1, $comparison['previous']['active_enrollments']);
        $this->assertSame(1, $comparison['delta']['active_enrollments']);
        $this->assertEquals(10.0, $comparison['delta']['average_score']);
    }

    public function test_purge_cache_removes_stored_cache_entry(): void
    {
        $this->intelligenceService->getDashboardOverview($this->year2);
        $this->assertTrue(Cache::has("academic_intelligence_kpi_{$this->year2->id}"));

        $this->intelligenceService->purgeCache($this->year2);
        $this->assertFalse(Cache::has("academic_intelligence_kpi_{$this->year2->id}"));
    }
}
