<?php

namespace Tests\Feature\DataTables;

use App\Models\AcademicCalendarEvent;
use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Santri;
use App\Models\StudentBatch;
use App\Models\TeachingAssignment;
use App\Models\TeachingSession;
use App\Models\User;
use App\Models\WaliSantri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Sprint3DataTablesHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher1;

    protected User $teacher2;

    protected AcademicYear $yearActive;

    protected AcademicYear $yearInactive;

    protected Kelas $kelasA;

    protected Kelas $kelasB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Operator',
            'email' => 'admin_operator@example.com',
            'password' => Hash::make('password123'),
        ]);
        $this->admin->assignRole('Administrator');

        $this->teacher1 = User::create([
            'name' => 'Ustadz Zaid',
            'email' => 'zaid@example.com',
            'password' => Hash::make('password123'),
        ]);
        $this->teacher1->assignRole('Guru');

        $this->teacher2 = User::create([
            'name' => 'Ustadz Umar',
            'email' => 'umar@example.com',
            'password' => Hash::make('password123'),
        ]);
        $this->teacher2->assignRole('Guru');

        $this->yearActive = AcademicYear::create([
            'name' => '2024/2025 Ganjil',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-01',
            'end_date' => '2024-12-31',
            'is_active' => true,
        ]);

        $this->yearInactive = AcademicYear::create([
            'name' => '2024/2025 Genap',
            'semester' => 'Genap',
            'start_date' => '2025-01-01',
            'end_date' => '2025-06-30',
            'is_active' => false,
        ]);

        $this->kelasA = Kelas::create([
            'kode' => 'KLS-A01',
            'tingkatan' => 'Ula',
            'kelas' => '1A',
        ]);

        $this->kelasB = Kelas::create([
            'kode' => 'KLS-B01',
            'tingkatan' => 'Wustho',
            'kelas' => '2B',
        ]);
    }

    protected function createSantriWithUser(string $name, string $noInduk, string $status, string $tahunMasuk, ?int $batchId = null): Santri
    {
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)).'@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('Santri');

        $kamar = Kamar::firstOrCreate([
            'kode' => 'KMR-'.substr($noInduk, -3),
            'nama' => 'Kamar '.substr($noInduk, -3),
            'blok' => 'A',
        ]);

        $santri = Santri::create([
            'user_id' => $user->id,
            'student_batch_id' => $batchId,
            'kamar_id' => $kamar->id,
            'kelas_id' => $this->kelasA->id,
            'no_induk' => $noInduk,
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Sumenep',
            'tahun_masuk' => $tahunMasuk,
            'tahun_masuk_hijriyah' => '1445',
            'status' => $status,
        ]);

        WaliSantri::create([
            'santri_id' => $santri->id,
            'nama_ayah' => 'Ayah '.$name,
            'nama_ibu' => 'Ibu '.$name,
        ]);

        return $santri;
    }

    /**
     * Santri DataTables: test search, status filter, and entry year filter.
     */
    public function test_santri_datatable_filtering_and_ordering(): void
    {
        $this->actingAs($this->admin);

        $s1 = $this->createSantriWithUser('Fatimah Az-Zahra', '1001', 'Santri Aktif', '2024-07-01');
        $s2 = $this->createSantriWithUser('Aisyah Humaira', '1002', 'Santri Alumni', '2023-07-01');
        $s3 = $this->createSantriWithUser('Khadijah Al-Kubra', '1003', 'Santri Aktif', '2023-07-01');

        // 1. Search by name (relation user.name)
        $res = $this->getJson(route('santri.index', [
            'search' => ['value' => 'Fatimah'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('1001', $res->json('data.0.no_induk'));

        // 2. Filter by status
        $res = $this->getJson(route('santri.index', [
            'status' => 'Santri Alumni',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('1002', $res->json('data.0.no_induk'));

        // 3. Filter by tahun masuk
        $res = $this->getJson(route('santri.index', [
            'tahun_masuk' => '2023',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(2, $res->json('data'));
    }

    /**
     * Academic Enrollment DataTables: test academic year and batch filters without ambiguous columns.
     */
    public function test_academic_enrollment_datatable_filters_and_accessors(): void
    {
        $this->actingAs($this->admin);

        $batch1 = StudentBatch::create(['name' => 'Angkatan 2023', 'year' => 2023]);
        $batch2 = StudentBatch::create(['name' => 'Angkatan 2024', 'year' => 2024]);

        $s1 = $this->createSantriWithUser('Santri Angkatan 23', '2001', 'Santri Aktif', '2023-07-01', $batch1->id);
        $s2 = $this->createSantriWithUser('Santri Angkatan 24', '2002', 'Santri Aktif', '2024-07-01', $batch2->id);

        AcademicEnrollment::create([
            'academic_year_id' => $this->yearActive->id,
            'santri_id' => $s1->id,
            'kelas_id' => $this->kelasA->id,
            'status' => 'Aktif',
            'enrolled_at' => '2024-07-01',
        ]);

        AcademicEnrollment::create([
            'academic_year_id' => $this->yearInactive->id,
            'santri_id' => $s2->id,
            'kelas_id' => $this->kelasB->id,
            'status' => 'Aktif',
            'enrolled_at' => '2025-01-01',
        ]);

        // Filter by academic year
        $res = $this->getJson(route('academic-enrollment.index', [
            'academic_year_id' => $this->yearActive->id,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Santri Angkatan 23', $res->json('data.0.santri_name'));

        // Filter by batch
        $res = $this->getJson(route('academic-enrollment.index', [
            'student_batch_id' => $batch2->id,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Santri Angkatan 24', $res->json('data.0.santri_name'));

        // Global search by name (must not fail on santri.nama_lengkap)
        $res = $this->getJson(route('academic-enrollment.index', [
            'search' => ['value' => 'Angkatan 24'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
    }

    /**
     * Teaching Assignment DataTables: test teacher, class, and subject filters.
     */
    public function test_teaching_assignment_datatable_filters(): void
    {
        $this->actingAs($this->admin);

        $mapel1 = Mapel::create([
            'kelas_id' => $this->kelasA->id,
            'code' => 'MPL-FIQ-01',
            'name' => 'Fiqih Dasar',
            'is_active' => true,
        ]);

        $mapel2 = Mapel::create([
            'kelas_id' => $this->kelasB->id,
            'code' => 'MPL-NAH-01',
            'name' => 'Nahwu Jurumiyah',
            'is_active' => true,
        ]);

        TeachingAssignment::create([
            'academic_year_id' => $this->yearActive->id,
            'kelas_id' => $this->kelasA->id,
            'mapel_id' => $mapel1->id,
            'user_id' => $this->teacher1->id,
            'status' => 'Aktif',
        ]);

        TeachingAssignment::create([
            'academic_year_id' => $this->yearActive->id,
            'kelas_id' => $this->kelasB->id,
            'mapel_id' => $mapel2->id,
            'user_id' => $this->teacher2->id,
            'status' => 'Aktif',
        ]);

        // Filter by teacher
        $res = $this->getJson(route('teaching-assignment.index', [
            'user_id' => $this->teacher1->id,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals($this->teacher1->name, $res->json('data.0.teacher_name'));

        // Filter by class
        $res = $this->getJson(route('teaching-assignment.index', [
            'kelas_id' => $this->kelasB->id,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals($this->teacher2->name, $res->json('data.0.teacher_name'));

        // Filter by subject
        $res = $this->getJson(route('teaching-assignment.index', [
            'mapel_id' => $mapel1->id,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
    }

    /**
     * Class Schedule DataTables: test class, teacher, and subject filters.
     */
    public function test_class_schedule_datatable_filters(): void
    {
        $this->actingAs($this->admin);

        $mapel1 = Mapel::create([
            'kelas_id' => $this->kelasA->id,
            'code' => 'MPL-TAJ-01',
            'name' => 'Tajwid',
            'is_active' => true,
        ]);

        $mapel2 = Mapel::create([
            'kelas_id' => $this->kelasB->id,
            'code' => 'MPL-HAD-01',
            'name' => 'Hadits Arbain',
            'is_active' => true,
        ]);

        $ta1 = TeachingAssignment::create([
            'academic_year_id' => $this->yearActive->id,
            'kelas_id' => $this->kelasA->id,
            'mapel_id' => $mapel1->id,
            'user_id' => $this->teacher1->id,
            'status' => 'Aktif',
        ]);

        $ta2 = TeachingAssignment::create([
            'academic_year_id' => $this->yearActive->id,
            'kelas_id' => $this->kelasB->id,
            'mapel_id' => $mapel2->id,
            'user_id' => $this->teacher2->id,
            'status' => 'Aktif',
        ]);

        ClassSchedule::create([
            'teaching_assignment_id' => $ta1->id,
            'academic_year_id' => $this->yearActive->id,
            'kelas_id' => $this->kelasA->id,
            'day_of_week' => 'Senin',
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
        ]);

        ClassSchedule::create([
            'teaching_assignment_id' => $ta2->id,
            'academic_year_id' => $this->yearActive->id,
            'kelas_id' => $this->kelasB->id,
            'day_of_week' => 'Selasa',
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
        ]);

        // Filter by teacher
        $res = $this->getJson(route('class-schedule.index', [
            'user_id' => $this->teacher1->id,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals($this->teacher1->name, $res->json('data.0.teacher_name'));

        // Filter by subject
        $res = $this->getJson(route('class-schedule.index', [
            'mapel_id' => $mapel2->id,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals($this->teacher2->name, $res->json('data.0.teacher_name'));
    }

    /**
     * Attendance (TeachingSession) DataTables: test class, teacher, and subject filters.
     */
    public function test_attendance_datatable_filters(): void
    {
        $this->actingAs($this->admin);

        $mapel1 = Mapel::create([
            'kelas_id' => $this->kelasA->id,
            'code' => 'MPL-AKH-01',
            'name' => 'Akhlak Lil Banin',
            'is_active' => true,
        ]);

        $ta1 = TeachingAssignment::create([
            'academic_year_id' => $this->yearActive->id,
            'kelas_id' => $this->kelasA->id,
            'mapel_id' => $mapel1->id,
            'user_id' => $this->teacher1->id,
            'status' => 'Aktif',
        ]);

        TeachingSession::create([
            'teaching_assignment_id' => $ta1->id,
            'session_date' => '2024-08-01',
            'status' => 'Planned',
        ]);

        // Filter by teacher
        $res = $this->getJson(route('attendance.index', [
            'user_id' => $this->teacher1->id,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals($this->teacher1->name, $res->json('data.0.teacher_name'));

        // Filter by subject
        $res = $this->getJson(route('attendance.index', [
            'mapel_id' => $mapel1->id,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
    }

    /**
     * Academic Calendar Event DataTables: test academic year filter.
     */
    public function test_academic_calendar_datatable_filters(): void
    {
        $this->actingAs($this->admin);

        AcademicCalendarEvent::create([
            'academic_year_id' => $this->yearActive->id,
            'title' => 'Awal Masuk Santri',
            'event_type' => 'Kegiatan',
            'start_date' => '2024-07-15',
            'end_date' => '2024-07-15',
        ]);

        AcademicCalendarEvent::create([
            'academic_year_id' => $this->yearInactive->id,
            'title' => 'Libur Semester Genap',
            'event_type' => 'Libur',
            'start_date' => '2025-06-20',
            'end_date' => '2025-06-30',
        ]);

        $res = $this->getJson(route('academic-calendar-event.index', [
            'academic_year_id' => $this->yearActive->id,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Awal Masuk Santri', $res->json('data.0.title'));
    }

    /**
     * Kelas DataTables: verify server-side query builder works.
     */
    public function test_kelas_datatable_server_side(): void
    {
        $this->actingAs($this->admin);

        Kelas::create([
            'kode' => 'KLS-UNIQ',
            'tingkatan' => 'Aliyah Khusus',
            'kelas' => '3C',
        ]);

        $res = $this->getJson(route('kelas.index', [
            'search' => ['value' => 'Aliyah Khusus'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('3C', $res->json('data.0.kelas'));
    }

    /**
     * Mapel DataTables: verify class and active status filters.
     */
    public function test_mapel_datatable_filters(): void
    {
        $this->actingAs($this->admin);

        Mapel::create([
            'kelas_id' => $this->kelasA->id,
            'code' => 'MPL-M1',
            'name' => 'Mata Pelajaran 1',
            'is_active' => true,
        ]);

        Mapel::create([
            'kelas_id' => $this->kelasB->id,
            'code' => 'MPL-M2',
            'name' => 'Mata Pelajaran 2',
            'is_active' => false,
        ]);

        // Filter by class
        $res = $this->getJson(route('mapel.index', [
            'kelas_id' => $this->kelasA->id,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Mata Pelajaran 1', $res->json('data.0.name'));

        // Filter by active status
        $res = $this->getJson(route('mapel.index', [
            'is_active' => '0',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Mata Pelajaran 2', $res->json('data.0.name'));
    }
}
