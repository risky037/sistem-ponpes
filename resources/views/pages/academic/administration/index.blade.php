@extends('layouts.app')

@section('title', 'Administrasi Akademik | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Administrasi Akademik'></x-breadcrumb>

                {{-- Status Banner --}}
                <div class="card radius-15 bg-primary text-white mb-4 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <h4 class="mb-1 text-white fw-bold">Pusat Administrasi Akademik</h4>
                                <p class="mb-0 text-white-50">
                                    Tahun Ajaran Aktif: <strong class="text-white">{{ $stats['active_year_name'] }}</strong>
                                    | Status Sistem: <span class="badge bg-success">Operasional</span>
                                </p>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('academic.export.index') }}" class="btn btn-light btn-sm text-primary fw-bold">
                                    <i class="bx bx-download me-1"></i> Pusat Ekspor & Cetak Data
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Metric Cards Grid --}}
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-4">
                    {{-- Tahun Ajaran & Pendaftaran --}}
                    <div class="col">
                        <div class="card radius-15 border shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-lg bg-light-primary text-primary rounded-circle p-3 me-3">
                                        <i class="bx bx-calendar fs-3"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-1 text-uppercase small fw-semibold">Pendaftaran Akademik</p>
                                        <h4 class="mb-0 fw-bold">{{ $stats['active_enrollments'] }} <span class="fs-6 text-muted fw-normal">/ {{ $stats['total_enrollments'] }} Santri</span></h4>
                                        <small class="text-muted">{{ $stats['total_years'] }} Tahun Ajaran Tercatat</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top py-2 text-end">
                                <a href="{{ route('academic-enrollment.index') }}" class="text-primary small fw-semibold">Kelola Penempatan &rarr;</a>
                            </div>
                        </div>
                    </div>

                    {{-- Kurikulum & Pengajaran --}}
                    <div class="col">
                        <div class="card radius-15 border shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-lg bg-light-success text-success rounded-circle p-3 me-3">
                                        <i class="bx bx-book-open fs-3"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-1 text-uppercase small fw-semibold">Kurikulum & Guru</p>
                                        <h4 class="mb-0 fw-bold">{{ $stats['total_assignments'] }} <span class="fs-6 text-muted fw-normal">Penugasan</span></h4>
                                        <small class="text-muted">{{ $stats['total_mapels'] }} Mata Pelajaran Aktif</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top py-2 text-end">
                                <a href="{{ route('teaching-assignment.index') }}" class="text-success small fw-semibold">Kelola Pengajaran &rarr;</a>
                            </div>
                        </div>
                    </div>

                    {{-- Presensi & Sesi Pembelajaran --}}
                    <div class="col">
                        <div class="card radius-15 border shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-lg bg-light-warning text-warning rounded-circle p-3 me-3">
                                        <i class="bx bx-check-shield fs-3"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-1 text-uppercase small fw-semibold">Presensi Pembelajaran</p>
                                        <h4 class="mb-0 fw-bold">{{ $stats['total_attendance_records'] }} <span class="fs-6 text-muted fw-normal">Presensi</span></h4>
                                        <small class="text-muted">{{ $stats['total_sessions'] }} Sesi Kelas Terlaksana</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top py-2 text-end">
                                <a href="{{ route('attendance.index') }}" class="text-warning small fw-semibold">Rekap Presensi &rarr;</a>
                            </div>
                        </div>
                    </div>

                    {{-- Evaluasi & Nilai --}}
                    <div class="col">
                        <div class="card radius-15 border shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-lg bg-light-info text-info rounded-circle p-3 me-3">
                                        <i class="bx bx-edit fs-3"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-1 text-uppercase small fw-semibold">Evaluasi & Penilaian</p>
                                        <h4 class="mb-0 fw-bold">{{ $stats['total_scores'] }} <span class="fs-6 text-muted fw-normal">Nilai Tercatat</span></h4>
                                        <small class="text-muted">{{ $stats['total_components'] }} Komponen dari {{ $stats['total_definitions'] }} Skema</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top py-2 text-end">
                                <a href="{{ route('assessment.score.index') }}" class="text-info small fw-semibold">Buku Nilai &rarr;</a>
                            </div>
                        </div>
                    </div>

                    {{-- Agregasi Performa --}}
                    <div class="col">
                        <div class="card radius-15 border shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-lg bg-light-danger text-danger rounded-circle p-3 me-3">
                                        <i class="bx bx-bar-chart fs-3"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-1 text-uppercase small fw-semibold">Agregasi Performa</p>
                                        <h4 class="mb-0 fw-bold">{{ $stats['total_summaries'] }} <span class="fs-6 text-muted fw-normal">Rekap Dihitung</span></h4>
                                        <small class="text-muted">Analisis Kehadiran & Nilai Rata-rata</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top py-2 text-end">
                                <a href="{{ route('academic.performance.index') }}" class="text-danger small fw-semibold">Lihat Analitik &rarr;</a>
                            </div>
                        </div>
                    </div>

                    {{-- Ekspor & Cetak Data --}}
                    <div class="col">
                        <div class="card radius-15 border shadow-sm h-100 bg-light">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-lg bg-white text-dark rounded-circle p-3 me-3 shadow-sm">
                                        <i class="bx bx-file fs-3"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-1 text-uppercase small fw-semibold">Ekspor & Pelaporan</p>
                                        <h5 class="mb-0 fw-bold">Pusat Ekspor Data</h5>
                                        <small class="text-muted">Unduh Excel & Dokumen Cetak</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top py-2 text-end">
                                <a href="{{ route('academic.export.index') }}" class="btn btn-sm btn-outline-primary">Buka Pusat Ekspor</a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Recent Audit Activity --}}
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="mb-0 fw-bold">Riwayat Aktivitas Ekspor Akademik</h5>
                            <a href="{{ route('academic.export.index') }}" class="btn btn-sm btn-outline-secondary">Lihat Semua Log</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th>Pengguna</th>
                                        <th>Jenis Ekspor</th>
                                        <th>Format</th>
                                        <th>Tahun Ajaran</th>
                                        <th>Kelas</th>
                                        <th class="text-center">Jumlah Baris</th>
                                        <th>IP Address</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentLogs as $log)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="fw-semibold">{{ $log->user?->name ?? 'Sistem' }}</td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    {{ match ($log->export_type) {
                                                        'enrollment' => 'Pendaftaran Santri',
                                                        'teaching_assignment' => 'Penugasan Mengajar',
                                                        'attendance' => 'Rekap Presensi',
                                                        'assessment' => 'Nilai & Evaluasi',
                                                        'performance' => 'Performa Akademik',
                                                        default => $log->export_type,
                                                    } }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($log->format === 'xlsx')
                                                    <span class="badge bg-success"><i class="bx bx-spreadsheet me-1"></i>Excel</span>
                                                @else
                                                    <span class="badge bg-info text-dark"><i class="bx bx-printer me-1"></i>Cetak / PDF</span>
                                                @endif
                                            </td>
                                            <td>{{ $log->academicYear?->name ?? '-' }}</td>
                                            <td>{{ $log->kelas ? ($log->kelas->tingkatan.' - '.$log->kelas->kelas) : '-' }}</td>
                                            <td class="text-center fw-semibold">{{ $log->records_count }}</td>
                                            <td><code>{{ $log->ip_address ?? '-' }}</code></td>
                                            <td>{{ $log->created_at ? $log->created_at->format('d/m/Y H:i') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4 text-muted">
                                                <i class="bx bx-info-circle fs-4 d-block mb-1"></i>
                                                Belum ada aktivitas ekspor yang tercatat.
                                            </td>
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
@endsection
