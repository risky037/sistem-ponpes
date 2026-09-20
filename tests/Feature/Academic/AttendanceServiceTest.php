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
use App\Services\Academic\AttendanceService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $ustadz;

    protected Kelas $kelas;

    protected Kelas $otherKelas;

    protected Mapel $mapel;

    protected AcademicYear $academicYear;

    protected AcademicYear $otherAcademicYear;

    protected TeachingAssignment $teachingAssignment;

    protected TeachingSession $teachingSession;

    protected Santri $santri1;

    protected Santri $santri2;

    protected AcademicEnrollment $enrollment1;

    protected AcademicEnrollment $enrollment2;

    protected AttendanceService $service;

    protected function createTestSantri(string $name, string $email, string $noInduk): Santri
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('Secret123!'),
        ]);

        return Santri::create([
            'user_id' => $user->id,
            'no_induk' => $noInduk,
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '0812'.substr($noInduk, -8),
            'tanggal_lahir' => '2008-01-01',
            'tempat_lahir' => 'Kediri',
            'status' => 'Santri Aktif',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);

        $this->ustadz = User::create([
            'name' => 'Ustadz Abdullah',
            'email' => 'abdullah@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->ustadz->assignRole($pengurusRole);

        $this->kelas = Kelas::create([
            'kode' => 'KLS-SVC01',
            'tingkatan' => 'ALFIYAH',
            'kelas' => 'Alfiyah 1',
        ]);

        $this->otherKelas = Kelas::create([
            'kode' => 'KLS-SVC02',
            'tingkatan' => 'JURUMIYAH',
            'kelas' => 'Jurumiyah 1',
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-15',
            'end_date' => '2024-12-20',
            'is_active' => true,
        ]);

        $this->otherAcademicYear = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'Ganjil',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => false,
        ]);

        $this->mapel = Mapel::create([
            'kelas_id' => $this->kelas->id,
            'code' => 'MPL-SVC-01',
            'name' => 'Nahwu Alfiyah',
            'is_active' => true,
        ]);

        $this->teachingAssignment = TeachingAssignment::create([
            'kelas_id' => $this->kelas->id,
            'mapel_id' => $this->mapel->id,
            'user_id' => $this->ustadz->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => 'Aktif',
        ]);

        $this->teachingSession = TeachingSession::create([
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'session_date' => '2024-08-01',
            'status' => TeachingSession::STATUS_PLANNED,
        ]);

        $this->santri1 = $this->createTestSantri('Santri Satu', 'santri1@example.com', '88001');
        $this->enrollment1 = AcademicEnrollment::create([
            'santri_id' => $this->santri1->id,
            'kelas_id' => $this->kelas->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => '2024-07-15',
        ]);

        $this->santri2 = $this->createTestSantri('Santri Dua', 'santri2@example.com', '88002');
        $this->enrollment2 = AcademicEnrollment::create([
            'santri_id' => $this->santri2->id,
            'kelas_id' => $this->kelas->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => '2024-07-15',
        ]);

        $this->service = app(AttendanceService::class);
    }

    public function test_mark_attendance_creates_record_successfully(): void
    {
        $record = $this->service->markAttendance(
            session: $this->teachingSession,
            enrollment: $this->enrollment1,
            status: AttendanceRecord::STATUS_HADIR,
            marker: $this->ustadz,
            notes: 'Tepat waktu'
        );

        $this->assertInstanceOf(AttendanceRecord::class, $record);
        $this->assertEquals(AttendanceRecord::STATUS_HADIR, $record->status);
        $this->assertEquals($this->ustadz->id, $record->marked_by);
        $this->assertEquals('Tepat waktu', $record->notes);
        $this->assertDatabaseHas('attendance_records', [
            'teaching_session_id' => $this->teachingSession->id,
            'academic_enrollment_id' => $this->enrollment1->id,
            'status' => AttendanceRecord::STATUS_HADIR,
        ]);
    }

    public function test_mark_attendance_rejects_duplicate_attendance(): void
    {
        $this->service->markAttendance(
            session: $this->teachingSession,
            enrollment: $this->enrollment1,
            status: AttendanceRecord::STATUS_HADIR,
            marker: $this->ustadz
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('sudah ada');

        $this->service->markAttendance(
            session: $this->teachingSession,
            enrollment: $this->enrollment1,
            status: AttendanceRecord::STATUS_IZIN,
            marker: $this->ustadz
        );
    }

    public function test_mark_attendance_rejects_when_enrollment_belongs_to_different_class(): void
    {
        $santriOther = $this->createTestSantri('Santri Kelas Lain', 'santri.other@example.com', '88099');
        $enrollmentOtherClass = AcademicEnrollment::create([
            'santri_id' => $santriOther->id,
            'kelas_id' => $this->otherKelas->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => '2024-07-15',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Santri tidak terdaftar di kelas sesi pembelajaran ini.');

        $this->service->markAttendance(
            session: $this->teachingSession,
            enrollment: $enrollmentOtherClass,
            status: AttendanceRecord::STATUS_HADIR,
            marker: $this->ustadz
        );
    }

    public function test_mark_attendance_rejects_when_teaching_session_status_is_cancelled(): void
    {
        $this->teachingSession->update(['status' => TeachingSession::STATUS_CANCELLED]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Tidak dapat mencatat kehadiran untuk sesi pembelajaran yang telah dibatalkan.');

        $this->service->markAttendance(
            session: $this->teachingSession,
            enrollment: $this->enrollment1,
            status: AttendanceRecord::STATUS_HADIR,
            marker: $this->ustadz
        );
    }

    public function test_mark_attendance_rejects_when_enrollment_belongs_to_different_academic_year(): void
    {
        $santriOtherYear = $this->createTestSantri('Santri Beda Tahun', 'santri.year@example.com', '88098');
        $enrollmentOtherYear = AcademicEnrollment::create([
            'santri_id' => $santriOtherYear->id,
            'kelas_id' => $this->kelas->id,
            'academic_year_id' => $this->otherAcademicYear->id,
            'status' => AcademicEnrollment::STATUS_AKTIF,
            'enrolled_at' => '2025-07-15',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Santri tidak terdaftar di tahun ajaran sesi pembelajaran ini.');

        $this->service->markAttendance(
            session: $this->teachingSession,
            enrollment: $enrollmentOtherYear,
            status: AttendanceRecord::STATUS_HADIR,
            marker: $this->ustadz
        );
    }

    public function test_mark_attendance_rejects_inactive_enrollment(): void
    {
        $this->enrollment1->update(['status' => AcademicEnrollment::STATUS_NONAKTIF]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Santri tidak berstatus aktif');

        $this->service->markAttendance(
            session: $this->teachingSession,
            enrollment: $this->enrollment1,
            status: AttendanceRecord::STATUS_HADIR,
            marker: $this->ustadz
        );
    }

    public function test_bulk_mark_attendance_records_all_students_atomically(): void
    {
        $records = [
            [
                'academic_enrollment_id' => $this->enrollment1->id,
                'status' => AttendanceRecord::STATUS_HADIR,
                'notes' => 'Hadir aktif',
            ],
            [
                'academic_enrollment_id' => $this->enrollment2->id,
                'status' => AttendanceRecord::STATUS_IZIN,
                'notes' => 'Izin keperluan keluarga',
            ],
        ];

        $result = $this->service->bulkMarkAttendance(
            session: $this->teachingSession,
            records: $records,
            marker: $this->ustadz
        );

        $this->assertCount(2, $result);
        $this->assertDatabaseHas('attendance_records', [
            'teaching_session_id' => $this->teachingSession->id,
            'academic_enrollment_id' => $this->enrollment1->id,
            'status' => AttendanceRecord::STATUS_HADIR,
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'teaching_session_id' => $this->teachingSession->id,
            'academic_enrollment_id' => $this->enrollment2->id,
            'status' => AttendanceRecord::STATUS_IZIN,
        ]);
    }

    public function test_update_attendance_modifies_status_and_notes(): void
    {
        $record = $this->service->markAttendance(
            session: $this->teachingSession,
            enrollment: $this->enrollment1,
            status: AttendanceRecord::STATUS_ALPHA,
            marker: $this->ustadz
        );

        $updated = $this->service->updateAttendance(
            record: $record,
            status: AttendanceRecord::STATUS_HADIR,
            marker: $this->ustadz,
            notes: 'Revisi: hadir terlambat'
        );

        $this->assertEquals(AttendanceRecord::STATUS_HADIR, $updated->status);
        $this->assertEquals('Revisi: hadir terlambat', $updated->notes);
        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'status' => AttendanceRecord::STATUS_HADIR,
            'notes' => 'Revisi: hadir terlambat',
        ]);
    }

    public function test_complete_attendance_validates_completeness_without_modifying_session_status(): void
    {
        $this->service->bulkMarkAttendance(
            session: $this->teachingSession,
            records: [
                ['academic_enrollment_id' => $this->enrollment1->id, 'status' => 'Hadir'],
                ['academic_enrollment_id' => $this->enrollment2->id, 'status' => 'Hadir'],
            ],
            marker: $this->ustadz
        );

        $isComplete = $this->service->completeAttendance($this->teachingSession);

        $this->assertTrue($isComplete);
        // Session status must remain unchanged per user adjustment
        $this->assertEquals(TeachingSession::STATUS_PLANNED, $this->teachingSession->fresh()->status);
    }

    public function test_complete_attendance_throws_exception_when_students_are_missing_attendance(): void
    {
        // Only mark enrollment1, leave enrollment2 unmarked
        $this->service->markAttendance(
            session: $this->teachingSession,
            enrollment: $this->enrollment1,
            status: AttendanceRecord::STATUS_HADIR,
            marker: $this->ustadz
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Presensi belum lengkap: masih terdapat 1 santri aktif yang belum dicatat.');

        $this->service->completeAttendance($this->teachingSession);
    }
}
