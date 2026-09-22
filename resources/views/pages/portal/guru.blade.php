@extends('layouts.app')

@section('title', 'Portal Guru & Asatidz | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <!-- Welcome Banner -->
                <div class="card radius-15 border-0 shadow-sm mb-4 bg-brand-gradient">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <h4 class="mb-1 text-white font-weight-bold">
                                    <i class="bx bx-chalkboard align-middle me-1"></i> Portal Guru — Selamat Datang, {{ auth()->user()->name }}
                                </h4>
                                <p class="mb-0 text-white-50">Portal Pengajar & Dewan Asatidz — Pengelolaan Presensi dan Penilaian Akademik</p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-light text-dark px-3 py-2 font-13">
                                    <i class="bx bx-calendar align-middle me-1"></i> {{ \Illuminate\Support\Carbon::now()->translatedFormat('l, d F Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & Stats -->
                <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
                    <div class="col">
                        <div class="card radius-15 mb-0 shadow-sm border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-1 text-muted text-uppercase font-12 font-weight-bold">Tahun Ajaran Aktif</p>
                                        <h5 class="mb-0 font-weight-bold text-dark">{{ $activeYear ? $activeYear->name : 'Belum Diatur' }}</h5>
                                        <small class="text-muted">{{ $activeYear ? 'Semester ' . $activeYear->semester : '-' }}</small>
                                    </div>
                                    <div class="widgets-icons ms-auto rounded-circle bg-primary text-white">
                                        <i class="bx bx-calendar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card radius-15 mb-0 shadow-sm border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-1 text-muted text-uppercase font-12 font-weight-bold">Kelas Ampuan</p>
                                        <h4 class="mb-0 font-weight-bold text-success">{{ $assignments->count() }}</h4>
                                        <small class="text-muted">Total Penugasan Mengajar</small>
                                    </div>
                                    <div class="widgets-icons ms-auto rounded-circle bg-success text-white">
                                        <i class="bx bx-book-open"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card radius-15 mb-0 shadow-sm border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-1 text-muted text-uppercase font-12 font-weight-bold">Total Sesi Terlaksana</p>
                                        <h4 class="mb-0 font-weight-bold text-teal">{{ $totalSessions }}</h4>
                                        <small class="text-muted">Sesi Presensi Pembelajaran</small>
                                    </div>
                                    <div class="widgets-icons ms-auto rounded-circle bg-teal text-white">
                                        <i class="bx bx-check-double"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div class="card radius-15 border shadow-sm mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-bold me-2 text-dark font-14"><i class="bx bx-bolt-circle text-warning align-middle font-18"></i> Akses Cepat:</span>
                            <a href="{{ route('attendance.index') }}" class="btn btn-primary btn-sm radius-10">
                                <i class="bx bx-check-square me-1"></i> Input Presensi Kelas
                            </a>
                            <a href="{{ route('assessment.score.index') }}" class="btn btn-outline-primary btn-sm radius-10">
                                <i class="bx bx-clipboard me-1"></i> Input Nilai Santri
                            </a>
                            <a href="{{ route('class-schedule.index') }}" class="btn btn-outline-secondary btn-sm radius-10">
                                <i class="bx bx-time-five me-1"></i> Lihat Jadwal Mengajar
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Assigned Classes & Subjects Table -->
                <div class="card radius-15 border shadow-sm">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h6 class="mb-0 font-weight-bold text-dark">
                            <i class="bx bx-list-check me-2 text-success"></i>Daftar Mata Pelajaran & Kelas yang Diampu
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle mb-0 font-13">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th>Kelas</th>
                                        <th>Tingkatan</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Jadwal Mingguan</th>
                                        <th class="text-center" style="width: 15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($assignments as $idx => $assignment)
                                        <tr>
                                            <td>{{ $idx + 1 }}</td>
                                            <td class="fw-bold">{{ $assignment->kelas?->kelas ?? '-' }}</td>
                                            <td>{{ $assignment->kelas?->tingkatan ?? '-' }}</td>
                                            <td><span class="badge bg-light-primary text-primary font-13">{{ $assignment->mapel?->name ?? '-' }}</span></td>
                                            <td>
                                                @forelse ($assignment->classSchedules as $schedule)
                                                    <span class="badge bg-light-secondary text-secondary me-1">
                                                        {{ $schedule->day_of_week }}: {{ $schedule->start_time }} - {{ $schedule->end_time }}
                                                    </span>
                                                @empty
                                                    <span class="text-muted font-italic">Belum ada jadwal</span>
                                                @endforelse
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('attendance.index') }}?kelas_id={{ $assignment->kelas_id }}" class="btn btn-sm btn-outline-success py-1 px-2 font-12" title="Presensi">
                                                    <i class="bx bx-check-square"></i> Presensi
                                                </a>
                                                <a href="{{ route('assessment.score.index') }}" class="btn btn-sm btn-outline-primary py-1 px-2 font-12 ms-1" title="Nilai">
                                                    <i class="bx bx-edit"></i> Nilai
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="bx bx-info-circle font-20 mb-1 d-block"></i>
                                                Belum ada penugasan mengajar aktif yang terdaftar untuk akun Anda.
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
