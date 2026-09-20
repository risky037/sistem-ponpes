<?php

namespace App\Services\Academic;

use App\Exports\Academic\AcademicEnrollmentExport;
use App\Exports\Academic\AcademicPerformanceExport;
use App\Exports\Academic\AssessmentSummaryExport;
use App\Exports\Academic\AttendanceSummaryExport;
use App\Exports\Academic\TeachingAssignmentExport;
use App\Models\AcademicEnrollment;
use App\Models\AcademicExportLog;
use App\Models\AcademicPerformanceSummary;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Kelas;
use App\Models\StudentAssessmentScore;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AcademicExportService
{
    /**
     * Export academic enrollments.
     */
    public function exportEnrollment(array $filters, string $format, User $user, ?string $ip = null): BinaryFileResponse|array
    {
        $query = AcademicEnrollment::with([
            'santri.user',
            'santri.wali_santri',
            'santri.student_batch',
            'kelas',
            'academicYear',
        ])->orderBy('academic_year_id', 'desc')
            ->orderBy('kelas_id', 'asc')
            ->orderBy('id', 'asc');

        if (! empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }
        if (! empty($filters['kelas_id'])) {
            $query->where('kelas_id', $filters['kelas_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $records = $query->get();

        $this->logExport(
            user: $user,
            type: AcademicExportLog::TYPE_ENROLLMENT,
            format: $format,
            academicYearId: ! empty($filters['academic_year_id']) ? (int) $filters['academic_year_id'] : null,
            kelasId: ! empty($filters['kelas_id']) ? (int) $filters['kelas_id'] : null,
            filters: $filters,
            count: $records->count(),
            ip: $ip
        );

        if ($format === AcademicExportLog::FORMAT_XLSX) {
            $fileName = 'Export-Pendaftaran-Akademik-'.now()->format('YmdHis').'.xlsx';

            return Excel::download(new AcademicEnrollmentExport($records), $fileName);
        }

        return [
            'data' => $records,
            'filters' => $filters,
            'total' => $records->count(),
            'title' => 'Rekap Pendaftaran Akademik Santri',
        ];
    }

    /**
     * Export teaching assignments and schedules.
     */
    public function exportTeachingAssignment(array $filters, string $format, User $user, ?string $ip = null): BinaryFileResponse|array
    {
        $query = TeachingAssignment::with([
            'academicYear',
            'kelas',
            'mapel',
            'user',
            'classSchedules',
        ])->orderBy('academic_year_id', 'desc')
            ->orderBy('kelas_id', 'asc');

        if (! empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }
        if (! empty($filters['kelas_id'])) {
            $query->where('kelas_id', $filters['kelas_id']);
        }
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $records = $query->get();

        $this->logExport(
            user: $user,
            type: AcademicExportLog::TYPE_TEACHING_ASSIGNMENT,
            format: $format,
            academicYearId: ! empty($filters['academic_year_id']) ? (int) $filters['academic_year_id'] : null,
            kelasId: ! empty($filters['kelas_id']) ? (int) $filters['kelas_id'] : null,
            filters: $filters,
            count: $records->count(),
            ip: $ip
        );

        if ($format === AcademicExportLog::FORMAT_XLSX) {
            $fileName = 'Export-Penugasan-Mengajar-'.now()->format('YmdHis').'.xlsx';

            return Excel::download(new TeachingAssignmentExport($records), $fileName);
        }

        return [
            'data' => $records,
            'filters' => $filters,
            'total' => $records->count(),
            'title' => 'Rekap Penugasan Mengajar & Jadwal',
        ];
    }

    /**
     * Export attendance summaries aggregated per enrollment.
     */
    public function exportAttendance(array $filters, string $format, User $user, ?string $ip = null): BinaryFileResponse|array
    {
        $query = AcademicEnrollment::with(['santri', 'kelas', 'academicYear'])
            ->withCount([
                'attendanceRecords as total_sessions',
                'attendanceRecords as present_count' => fn ($q) => $q->where('status', AttendanceRecord::STATUS_HADIR),
                'attendanceRecords as excused_count' => fn ($q) => $q->where('status', AttendanceRecord::STATUS_IZIN),
                'attendanceRecords as sick_count' => fn ($q) => $q->where('status', AttendanceRecord::STATUS_SAKIT),
                'attendanceRecords as absent_count' => fn ($q) => $q->where('status', AttendanceRecord::STATUS_ALPHA),
            ])->orderBy('academic_year_id', 'desc')
            ->orderBy('kelas_id', 'asc');

        if (! empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }
        if (! empty($filters['kelas_id'])) {
            $query->where('kelas_id', $filters['kelas_id']);
        }

        $enrollments = $query->get();

        $rows = $enrollments->map(function ($enr) {
            $total = (int) $enr->total_sessions;
            $present = (int) $enr->present_count;
            $rate = $total > 0 ? round(($present / $total) * 100, 2) : 0.0;

            return (object) [
                'nis' => $enr->santri?->nis ?? $enr->santri?->no_induk ?? '-',
                'nama' => $enr->santri?->nama_lengkap ?? $enr->santri?->user?->name ?? '-',
                'kelas' => $enr->kelas ? ($enr->kelas->tingkatan.' - '.$enr->kelas->kelas) : '-',
                'tahun_ajaran' => $enr->academicYear ? ($enr->academicYear->name.' ('.$enr->academicYear->semester.')') : '-',
                'total_sessions' => $total,
                'present_count' => $present,
                'excused_count' => (int) $enr->excused_count,
                'sick_count' => (int) $enr->sick_count,
                'absent_count' => (int) $enr->absent_count,
                'attendance_rate' => $rate,
            ];
        });

        $this->logExport(
            user: $user,
            type: AcademicExportLog::TYPE_ATTENDANCE,
            format: $format,
            academicYearId: ! empty($filters['academic_year_id']) ? (int) $filters['academic_year_id'] : null,
            kelasId: ! empty($filters['kelas_id']) ? (int) $filters['kelas_id'] : null,
            filters: $filters,
            count: $rows->count(),
            ip: $ip
        );

        if ($format === AcademicExportLog::FORMAT_XLSX) {
            $fileName = 'Export-Rekap-Presensi-'.now()->format('YmdHis').'.xlsx';

            return Excel::download(new AttendanceSummaryExport($rows), $fileName);
        }

        return [
            'data' => $rows,
            'filters' => $filters,
            'total' => $rows->count(),
            'title' => 'Rekapitulasi Presensi Pembelajaran',
        ];
    }

    /**
     * Export assessment summaries and recorded student scores.
     */
    public function exportAssessment(array $filters, string $format, User $user, ?string $ip = null): BinaryFileResponse|array
    {
        $query = StudentAssessmentScore::with([
            'academicEnrollment.santri',
            'academicEnrollment.kelas',
            'academicEnrollment.academicYear',
            'assessmentComponent.assessmentDefinition',
            'assessmentComponent.teachingAssignment.mapel',
            'assessmentComponent.teachingAssignment.kelas',
        ])->orderBy('id', 'asc');

        if (! empty($filters['academic_year_id'])) {
            $query->whereHas('academicEnrollment', function ($q) use ($filters) {
                $q->where('academic_year_id', $filters['academic_year_id']);
            });
        }
        if (! empty($filters['kelas_id'])) {
            $query->whereHas('academicEnrollment', function ($q) use ($filters) {
                $q->where('kelas_id', $filters['kelas_id']);
            });
        }
        if (! empty($filters['mapel_id'])) {
            $query->whereHas('assessmentComponent.teachingAssignment', function ($q) use ($filters) {
                $q->where('mapel_id', $filters['mapel_id']);
            });
        }

        $scores = $query->get();

        $rows = $scores->map(function ($score) {
            $enr = $score->academicEnrollment;
            $comp = $score->assessmentComponent;
            $def = $comp?->assessmentDefinition;
            $assign = $comp?->teachingAssignment;

            return (object) [
                'nis' => $enr?->santri?->nis ?? $enr?->santri?->no_induk ?? '-',
                'nama' => $enr?->santri?->nama_lengkap ?? $enr?->santri?->user?->name ?? '-',
                'kelas' => $enr?->kelas ? ($enr->kelas->tingkatan.' - '.$enr->kelas->kelas) : '-',
                'mapel' => $assign?->mapel?->name ?? '-',
                'tahun_ajaran' => $enr?->academicYear ? ($enr->academicYear->name.' ('.$enr->academicYear->semester.')') : '-',
                'component_name' => $def?->name ?? '-',
                'type' => $def?->type ?? '-',
                'weight' => $comp?->weight ?? $def?->weight ?? 0,
                'score' => $score->score,
                'graded_at' => $score->graded_at ? $score->graded_at->format('d/m/Y H:i') : ($score->created_at ? $score->created_at->format('d/m/Y H:i') : '-'),
            ];
        });

        $this->logExport(
            user: $user,
            type: AcademicExportLog::TYPE_ASSESSMENT,
            format: $format,
            academicYearId: ! empty($filters['academic_year_id']) ? (int) $filters['academic_year_id'] : null,
            kelasId: ! empty($filters['kelas_id']) ? (int) $filters['kelas_id'] : null,
            filters: $filters,
            count: $rows->count(),
            ip: $ip
        );

        if ($format === AcademicExportLog::FORMAT_XLSX) {
            $fileName = 'Export-Rekap-Nilai-Evaluasi-'.now()->format('YmdHis').'.xlsx';

            return Excel::download(new AssessmentSummaryExport($rows), $fileName);
        }

        return [
            'data' => $rows,
            'filters' => $filters,
            'total' => $rows->count(),
            'title' => 'Rekapitulasi Nilai & Evaluasi Pembelajaran',
        ];
    }

    /**
     * Export performance analytics summaries.
     */
    public function exportPerformance(array $filters, string $format, User $user, ?string $ip = null): BinaryFileResponse|array
    {
        $query = AcademicPerformanceSummary::with([
            'academicEnrollment.santri',
            'academicEnrollment.kelas',
            'academicEnrollment.academicYear',
        ])->orderBy('id', 'asc');

        if (! empty($filters['academic_year_id'])) {
            $query->whereHas('academicEnrollment', function ($q) use ($filters) {
                $q->where('academic_year_id', $filters['academic_year_id']);
            });
        }
        if (! empty($filters['kelas_id'])) {
            $query->whereHas('academicEnrollment', function ($q) use ($filters) {
                $q->where('kelas_id', $filters['kelas_id']);
            });
        }
        if (! empty($filters['computation_status'])) {
            $query->where('computation_status', $filters['computation_status']);
        }

        $summaries = $query->get();

        $rows = $summaries->map(function ($s) {
            $enr = $s->academicEnrollment;

            return (object) [
                'nis' => $enr?->santri?->nis ?? $enr?->santri?->no_induk ?? '-',
                'nama' => $enr?->santri?->nama_lengkap ?? $enr?->santri?->user?->name ?? '-',
                'kelas' => $enr?->kelas ? ($enr->kelas->tingkatan.' - '.$enr->kelas->kelas) : '-',
                'tahun_ajaran' => $enr?->academicYear?->name ?? '-',
                'semester' => $enr?->academicYear?->semester ?? '-',
                'total_sessions' => $s->total_sessions,
                'present_count' => $s->present_count,
                'attendance_rate' => $s->attendance_rate,
                'scored_components' => $s->scored_components,
                'total_components' => $s->total_components,
                'average_score' => $s->average_score,
                'computation_status' => $s->computation_status,
                'computed_at' => $s->computed_at ? $s->computed_at->format('d/m/Y H:i') : ($s->created_at ? $s->created_at->format('d/m/Y H:i') : '-'),
            ];
        });

        $this->logExport(
            user: $user,
            type: AcademicExportLog::TYPE_PERFORMANCE,
            format: $format,
            academicYearId: ! empty($filters['academic_year_id']) ? (int) $filters['academic_year_id'] : null,
            kelasId: ! empty($filters['kelas_id']) ? (int) $filters['kelas_id'] : null,
            filters: $filters,
            count: $rows->count(),
            ip: $ip
        );

        if ($format === AcademicExportLog::FORMAT_XLSX) {
            $fileName = 'Export-Performa-Akademik-'.now()->format('YmdHis').'.xlsx';

            return Excel::download(new AcademicPerformanceExport($rows), $fileName);
        }

        return [
            'data' => $rows,
            'filters' => $filters,
            'total' => $rows->count(),
            'title' => 'Rekapitulasi Agregasi Performa Akademik',
        ];
    }

    /**
     * Get recent export audit logs.
     */
    public function getRecentLogs(int $limit = 50): Collection
    {
        return AcademicExportLog::with(['user', 'academicYear', 'kelas'])
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Record immutable audit log entry inside database transaction.
     */
    protected function logExport(
        User $user,
        string $type,
        string $format,
        ?int $academicYearId,
        ?int $kelasId,
        array $filters,
        int $count,
        ?string $ip = null
    ): AcademicExportLog {
        $validYearId = ($academicYearId && AcademicYear::where('id', $academicYearId)->exists()) ? $academicYearId : null;
        $validKelasId = ($kelasId && Kelas::where('id', $kelasId)->exists()) ? $kelasId : null;

        return DB::transaction(function () use ($user, $type, $format, $validYearId, $validKelasId, $filters, $count, $ip) {
            return AcademicExportLog::create([
                'user_id' => $user->id,
                'export_type' => $type,
                'academic_year_id' => $validYearId,
                'kelas_id' => $validKelasId,
                'format' => $format,
                'filter_payload' => $filters,
                'records_count' => $count,
                'ip_address' => $ip,
                'created_at' => now(),
            ]);
        });
    }
}
