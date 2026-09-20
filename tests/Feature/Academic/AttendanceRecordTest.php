<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Santri;
use App\Models\TeachingAssignment;
use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceRecordTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $santriUser;

    protected Kelas $kelas;

    protected Mapel $mapel;

    protected AcademicYear $academicYear;

    protected TeachingAssignment $teachingAssignment;

    protected TeachingSession $teachingSession;

    protected Santri $santri;

    protected AcademicEnrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        $santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Presensi',
            'email' => 'admin.presensi@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->pengurus = User::create([
            'name' => 'Pengurus Presensi',
            'email' => 'pengurus.presensi@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->pengurus->assignRole($pengurusRole);

        $this->santriUser = User::create([
            'name' => 'Ahmad Santri',
            'email' => 'ahmad.santri@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->santriUser->assignRole($santriRole);

        $this->kelas = Kelas::create([
            'kode' => 'KLS-ATT01',
            'tingkatan' => 'ALFIYAH',
            'kelas' => 'Alfiyah 1 Reguler',
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-15',
            'end_date' => '2024-12-20',
            'is_active' => true,
        ]);

        $this->mapel = Mapel::create([
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-ATT-01',
            'name' => 'Nahwu Alfiyah',
            'is_active' => true,
        ]);

        $this->teachingAssignment = TeachingAssignment::create([
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $this->mapel->id,
            'user_id' => $this->pengurus->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => 'Aktif',
        ]);

        $this->teachingSession = TeachingSession::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'session_date' => '2024-08-01',
            'status' => 'Planned',
            'notes' => 'Pertemuan perdana',
        ]);

        $this->santri = Santri::create([
            'user_id' => $this->santriUser->id,
            'no_induk' => '99001',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Kediri',
            'status' => 'Santri Aktif',
        ]);

        $this->enrollment = AcademicEnrollment::create([
            'santri_id' => $this->santri->id,
            'kelas_id' => $this->kelas->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => '2024-07-15',
        ]);
    }

    public function test_attendance_record_has_valid_relationships_and_attributes(): void
    {
        $record = AttendanceRecord::create([
            'teaching_session_id' => $this->teachingSession->id,
            'academic_enrollment_id' => $this->enrollment->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'notes' => 'Hadir tepat waktu',
            'marked_at' => now(),
            'marked_by' => $this->pengurus->id,
        ]);

        $this->assertInstanceOf(AttendanceRecord::class, $record);
        $this->assertEquals($this->teachingSession->id, $record->teachingSession->id);
        $this->assertEquals($this->enrollment->id, $record->academicEnrollment->id);
        $this->assertEquals($this->pengurus->id, $record->marker->id);
        $this->assertNotNull($record->marked_at);
        $this->assertTrue($this->teachingSession->attendanceRecords->contains($record));
        $this->assertTrue($this->enrollment->attendanceRecords->contains($record));
    }

    public function test_attendance_record_scopes_present_and_absent(): void
    {
        $present = AttendanceRecord::create([
            'teaching_session_id' => $this->teachingSession->id,
            'academic_enrollment_id' => $this->enrollment->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'marked_at' => now(),
        ]);

        $otherSantriUser = User::create([
            'name' => 'Budi Santri',
            'email' => 'budi.santri@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $otherSantri = Santri::create([
            'user_id' => $otherSantriUser->id,
            'no_induk' => '99002',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567891',
            'tanggal_lahir' => '2008-02-02',
            'tempat_lahir' => 'Kediri',
            'status' => 'Santri Aktif',
        ]);
        $otherEnrollment = AcademicEnrollment::create([
            'santri_id' => $otherSantri->id,
            'kelas_id' => $this->kelas->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => '2024-07-15',
        ]);

        $absent = AttendanceRecord::create([
            'teaching_session_id' => $this->teachingSession->id,
            'academic_enrollment_id' => $otherEnrollment->id,
            'status' => AttendanceRecord::STATUS_SAKIT,
            'marked_at' => now(),
        ]);

        $this->assertEquals(1, AttendanceRecord::present()->count());
        $this->assertEquals(1, AttendanceRecord::absent()->count());
        $this->assertEquals(2, AttendanceRecord::forSession($this->teachingSession)->count());
    }

    public function test_unique_constraint_rejects_duplicate_attendance_for_same_student_in_same_session(): void
    {
        AttendanceRecord::create([
            'teaching_session_id' => $this->teachingSession->id,
            'academic_enrollment_id' => $this->enrollment->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'marked_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        AttendanceRecord::create([
            'teaching_session_id' => $this->teachingSession->id,
            'academic_enrollment_id' => $this->enrollment->id,
            'status' => AttendanceRecord::STATUS_IZIN,
            'marked_at' => now(),
        ]);
    }

    public function test_restrict_on_delete_prevents_deleting_session_with_attendance_records(): void
    {
        AttendanceRecord::create([
            'teaching_session_id' => $this->teachingSession->id,
            'academic_enrollment_id' => $this->enrollment->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'marked_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        $this->teachingSession->delete();
    }

    public function test_restrict_on_delete_prevents_deleting_enrollment_with_attendance_records(): void
    {
        AttendanceRecord::create([
            'teaching_session_id' => $this->teachingSession->id,
            'academic_enrollment_id' => $this->enrollment->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'marked_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        $this->enrollment->delete();
    }

    public function test_controller_index_renders_for_authorized_users(): void
    {
        $response = $this->actingAs($this->admin)->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('Presensi Pembelajaran');

        $response = $this->actingAs($this->pengurus)->get(route('attendance.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->santriUser)->get(route('attendance.index'));
        $response->assertStatus(403);
    }

    public function test_controller_datatable_ajax_returns_json(): void
    {
        AttendanceRecord::create([
            'teaching_session_id' => $this->teachingSession->id,
            'academic_enrollment_id' => $this->enrollment->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'marked_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('attendance.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'recordsTotal']);
        $this->assertStringContainsString('Nahwu Alfiyah', $response->getContent());
        $this->assertStringContainsString('H: 1', $response->getContent());
    }

    public function test_controller_manage_renders_attendance_sheet(): void
    {
        $response = $this->actingAs($this->admin)->get(route('attendance.manage', $this->teachingSession->id));
        $response->assertStatus(200);
        $response->assertSee('Lembar Presensi Santri');
        $response->assertSee('Ahmad Santri');
    }

    public function test_controller_store_bulk_marks_attendance(): void
    {
        $response = $this->actingAs($this->admin)->post(route('attendance.store', $this->teachingSession->id), [
            'attendance' => [
                [
                    'academic_enrollment_id' => $this->enrollment->id,
                    'status' => AttendanceRecord::STATUS_HADIR,
                    'notes' => 'Hadir aktif',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendance_records', [
            'teaching_session_id' => $this->teachingSession->id,
            'academic_enrollment_id' => $this->enrollment->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'notes' => 'Hadir aktif',
        ]);
    }
}
