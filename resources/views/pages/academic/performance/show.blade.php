@extends('layouts.app')

@section('title', 'Detail Performa Akademik | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('academic.performance.index') }}" path='Detail Performa Santri'></x-breadcrumb>

                <!-- Santri Header Card -->
                <div class="card radius-15 border shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1 text-primary fw-bold">{{ $enrollment->santri?->nama_lengkap ?? '-' }}</h4>
                                <p class="text-muted mb-0">
                                    <span class="me-3"><i class="bx bx-id-card me-1"></i>NIS: <strong>{{ $enrollment->santri?->nis ?? $enrollment->santri?->no_induk ?? '-' }}</strong></span>
                                    <span class="me-3"><i class="bx bx-building me-1"></i>Kelas: <strong>{{ $enrollment->kelas ? $enrollment->kelas->tingkatan.' - '.$enrollment->kelas->kelas : '-' }}</strong></span>
                                    <span class="me-3"><i class="bx bx-calendar me-1"></i>Tahun Ajaran: <strong>{{ $enrollment->academicYear ? $enrollment->academicYear->name.' ('.$enrollment->academicYear->semester.')' : '-' }}</strong></span>
                                    <span><i class="bx bx-check-circle me-1"></i>Status Penempatan: <span class="badge bg-info">{{ $enrollment->status }}</span></span>
                                </p>
                            </div>
                            <div class="mt-3 mt-md-0 d-flex gap-2">
                                <a href="{{ route('academic.performance.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i> Kembali
                                </a>
                                <form action="{{ route('academic.performance.generate', $enrollment->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hitung ulang rekap performa santri ini?')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bx bx-calculator me-1"></i> Hitung Ulang Performa
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aggregation Summary Cards -->
                @php
                    $summary = $enrollment->performanceSummary;
                @endphp
                <div class="row mb-4">
                    <!-- Attendance Summary Card -->
                    <div class="col-12 col-md-4">
                        <div class="card radius-15 border shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="card-title mb-0 text-secondary fw-semibold">Presensi Pembelajaran</h6>
                                    <div class="avatar-sm bg-light-primary rounded-circle p-2 text-primary">
                                        <i class="bx bx-user-check font-24"></i>
                                    </div>
                                </div>
                                <h3 class="mb-2 fw-bold text-dark">
                                    {{ $summary && $summary->attendance_rate !== null ? $summary->attendance_rate . '%' : '-' }}
                                </h3>
                                <p class="text-muted small mb-2">Total Pertemuan: <strong>{{ $summary?->total_sessions ?? 0 }} Sesi</strong></p>
                                <div class="row text-center g-1 pt-2 border-top">
                                    <div class="col-3">
                                        <span class="badge bg-success-subtle text-success w-100 py-1">Hadir: {{ $summary?->present_count ?? 0 }}</span>
                                    </div>
                                    <div class="col-3">
                                        <span class="badge bg-info-subtle text-info w-100 py-1">Izin: {{ $summary?->excused_count ?? 0 }}</span>
                                    </div>
                                    <div class="col-3">
                                        <span class="badge bg-warning-subtle text-warning w-100 py-1">Sakit: {{ $summary?->sick_count ?? 0 }}</span>
                                    </div>
                                    <div class="col-3">
                                        <span class="badge bg-danger-subtle text-danger w-100 py-1">Alpha: {{ $summary?->absent_count ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assessment Summary Card -->
                    <div class="col-12 col-md-4">
                        <div class="card radius-15 border shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="card-title mb-0 text-secondary fw-semibold">Rata-rata Nilai Akademik</h6>
                                    <div class="avatar-sm bg-light-success rounded-circle p-2 text-success">
                                        <i class="bx bx-line-chart font-24"></i>
                                    </div>
                                </div>
                                <h3 class="mb-2 fw-bold text-dark">
                                    {{ $summary && $summary->average_score !== null ? number_format((float) $summary->average_score, 2) : '-' }}
                                </h3>
                                <p class="text-muted small mb-2">
                                    Komponen Dinilai: <strong>{{ $summary?->scored_components ?? 0 }} / {{ $summary?->total_components ?? 0 }}</strong>
                                </p>
                                <div class="d-flex justify-content-between text-muted small pt-2 border-top">
                                    <span>Total Bobot Dinilai: <strong>{{ $summary?->total_weight ?? 0 }}%</strong></span>
                                    <span>Akumulasi Nilai: <strong>{{ $summary && $summary->weighted_score_sum !== null ? number_format((float) $summary->weighted_score_sum, 2) : '-' }}</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status & Metadata Card -->
                    <div class="col-12 col-md-4">
                        <div class="card radius-15 border shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="card-title mb-0 text-secondary fw-semibold">Status Agregasi Data</h6>
                                    <div class="avatar-sm bg-light-info rounded-circle p-2 text-info">
                                        <i class="bx bx-data font-24"></i>
                                    </div>
                                </div>
                                <div class="my-2">
                                    @if (! $summary)
                                        <span class="badge bg-light text-dark border px-3 py-2 font-14">Belum Dihitung</span>
                                    @elseif ($summary->computation_status === \App\Models\AcademicPerformanceSummary::STATUS_LENGKAP)
                                        <span class="badge bg-success px-3 py-2 font-14">Lengkap</span>
                                    @elseif ($summary->computation_status === \App\Models\AcademicPerformanceSummary::STATUS_SEBAGIAN)
                                        <span class="badge bg-warning text-dark px-3 py-2 font-14">Sebagian</span>
                                    @else
                                        <span class="badge bg-secondary px-3 py-2 font-14">Kosong</span>
                                    @endif
                                </div>
                                <div class="text-muted small pt-2 border-top">
                                    <div>Versi Sumber: <strong>{{ $summary?->source_version ?? '-' }}</strong></div>
                                    <div>Waktu Agregasi: <strong>{{ $summary?->computed_at ? $summary->computed_at->format('d/m/Y H:i') : '-' }}</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Breakdown Tables -->
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <ul class="nav nav-tabs nav-primary" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" data-bs-toggle="tab" href="#tab-attendance" role="tab" aria-selected="true">
                                    <div class="d-flex align-items-center">
                                        <div class="tab-icon"><i class="bx bx-check-square font-18 me-1"></i></div>
                                        <div class="tab-title">Rincian Presensi ({{ $enrollment->attendanceRecords->count() }})</div>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-scores" role="tab" aria-selected="false">
                                    <div class="d-flex align-items-center">
                                        <div class="tab-icon"><i class="bx bx-clipboard font-18 me-1"></i></div>
                                        <div class="tab-title">Rincian Nilai Komponen ({{ $enrollment->studentAssessmentScores->count() }})</div>
                                    </div>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content pt-3">
                            <!-- Attendance Tab -->
                            <div class="tab-pane fade show active" id="tab-attendance" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th>Tanggal Sesi</th>
                                                <th>Mata Pelajaran</th>
                                                <th>Pengajar</th>
                                                <th>Status Kehadiran</th>
                                                <th>Catatan</th>
                                                <th>Waktu Presensi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($enrollment->attendanceRecords as $idx => $att)
                                                <tr>
                                                    <td>{{ $idx + 1 }}</td>
                                                    <td>{{ $att->teachingSession?->session_date ? $att->teachingSession->session_date->format('d/m/Y') : '-' }}</td>
                                                    <td>{{ $att->teachingSession?->teachingAssignment?->mapel?->name ?? '-' }}</td>
                                                    <td>{{ $att->teachingSession?->teachingAssignment?->user?->name ?? '-' }}</td>
                                                    <td>
                                                        @if ($att->status === \App\Models\AttendanceRecord::STATUS_HADIR)
                                                            <span class="badge bg-success">Hadir</span>
                                                        @elseif ($att->status === \App\Models\AttendanceRecord::STATUS_IZIN)
                                                            <span class="badge bg-info">Izin</span>
                                                        @elseif ($att->status === \App\Models\AttendanceRecord::STATUS_SAKIT)
                                                            <span class="badge bg-warning text-dark">Sakit</span>
                                                        @else
                                                            <span class="badge bg-danger">Alpha</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $att->notes ?? '-' }}</td>
                                                    <td>{{ $att->marked_at ? $att->marked_at->format('d/m/Y H:i') : '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted py-3">Belum ada catatan presensi pembelajaran.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Scores Tab -->
                            <div class="tab-pane fade" id="tab-scores" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th>Nama Komponen</th>
                                                <th>Tipe</th>
                                                <th>Mata Pelajaran</th>
                                                <th>Bobot</th>
                                                <th>Nilai</th>
                                                <th>Dinilai Oleh</th>
                                                <th>Tanggal Dinilai</th>
                                                <th>Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($enrollment->studentAssessmentScores as $idx => $sc)
                                                <tr>
                                                    <td>{{ $idx + 1 }}</td>
                                                    <td class="fw-semibold">{{ $sc->assessmentComponent?->assessmentDefinition?->name ?? '-' }}</td>
                                                    <td>{{ $sc->assessmentComponent?->assessmentDefinition?->type ?? '-' }}</td>
                                                    <td>{{ $sc->assessmentComponent?->teachingAssignment?->mapel?->name ?? '-' }}</td>
                                                    <td>{{ $sc->assessmentComponent?->weight ?? 0 }}%</td>
                                                    <td>
                                                        <span class="badge bg-primary fs-6">{{ number_format((float) $sc->score, 2) }}</span>
                                                    </td>
                                                    <td>{{ $sc->grader?->name ?? '-' }}</td>
                                                    <td>{{ $sc->graded_at ? $sc->graded_at->format('d/m/Y H:i') : '-' }}</td>
                                                    <td>{{ $sc->notes ?? '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted py-3">Belum ada nilai komponen penilaian yang tercatat.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
