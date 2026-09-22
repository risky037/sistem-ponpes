@extends('layouts.app')

@section('title', 'Portal Santri | DIGITREN')

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
                                    <i class="bx bx-user-circle align-middle me-1"></i> Selamat Datang, {{ $santri?->user?->name ?? auth()->user()->name }}
                                </h4>
                                <p class="mb-0 text-white-50">Portal Informasi Pribadi Santri — Pondok Pesantren Fatimah Az-Zahra</p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-light text-dark px-3 py-2 font-13">
                                    <i class="bx bx-calendar align-middle me-1"></i> {{ \Illuminate\Support\Carbon::now()->translatedFormat('l, d F Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                @if (!$santri)
                    <div class="alert alert-warning border-0 radius-15 shadow-sm p-4 text-center">
                        <i class="bx bx-info-circle font-30 mb-2"></i>
                        <h5 class="fw-bold mb-1">Data Profil Santri Belum Terhubung</h5>
                        <p class="text-muted mb-0">Akun Anda belum dikaitkan dengan biodata santri aktif. Silakan hubungi bagian administrasi pondok.</p>
                    </div>
                @else
                    <div class="row">
                        <!-- Profile & Penempatan Card -->
                        <div class="col-12 col-xl-4 mb-4">
                            <div class="card radius-15 border shadow-sm h-100">
                                <div class="card-body text-center p-4">
                                    @if ($santri->foto && $santri->foto !== 'santri.png')
                                        <img src="{{ url('storage/uploads/santri/' . $santri->foto) }}" alt="Foto Santri" class="rounded-circle p-1 border" style="width: 110px; height: 110px; object-fit: cover;">
                                    @else
                                        <img src="{{ url('img/santri.png') }}" alt="Foto Santri" class="rounded-circle p-1 border" style="width: 110px; height: 110px; object-fit: cover;">
                                    @endif
                                    <h5 class="mb-1 mt-3 font-weight-bold text-dark">{{ $santri->user->name }}</h5>
                                    <p class="text-muted mb-2 font-13">NIS: <span class="fw-bold text-dark">{{ $santri->no_induk }}</span></p>
                                    <x-status-badge :status="$santri->status" />

                                    <hr class="my-3">

                                    <div class="text-start">
                                        <div class="d-flex justify-content-between py-2 border-bottom font-13">
                                            <span class="text-muted">Kelas Aktif:</span>
                                            <span class="fw-bold text-dark">
                                                {{ isset($santri->kelas_santri) && $santri->kelas_santri->kelas ? $santri->kelas_santri->kelas->tingkatan . ' - ' . $santri->kelas_santri->kelas->kelas : 'Belum ditentukan' }}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between py-2 border-bottom font-13">
                                            <span class="text-muted">Kamar Asrama:</span>
                                            <span class="fw-bold text-dark">
                                                {{ isset($santri->kamar_santri) && $santri->kamar_santri->kamar ? $santri->kamar_santri->kamar->nama . ' (Blok ' . $santri->kamar_santri->kamar->blok . ')' : 'Belum ditentukan' }}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between py-2 border-bottom font-13">
                                            <span class="text-muted">Tempat, Tgl Lahir:</span>
                                            <span class="text-dark">{{ $santri->tempat_lahir }}, @formatDate($santri->tanggal_lahir)</span>
                                        </div>
                                        <div class="d-flex justify-content-between py-2 border-bottom font-13">
                                            <span class="text-muted">Jenis Kelamin:</span>
                                            <span class="text-dark">{{ $santri->jenis_kelamin }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between py-2 border-bottom font-13">
                                            <span class="text-muted">Tahun Masuk:</span>
                                            <span class="text-dark">{{ $santri->tahun_masuk }} ({{ $santri->tahun_masuk_hijriyah }} H)</span>
                                        </div>
                                        @if ($santri->student_batch)
                                            <div class="d-flex justify-content-between py-2 font-13">
                                                <span class="text-muted">Angkatan:</span>
                                                <span class="badge bg-light-success text-success">{{ $santri->student_batch->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Academic & Attendance Section -->
                        <div class="col-12 col-xl-8 mb-4">
                            <!-- Attendance KPI Grid -->
                            <div class="card radius-15 border shadow-sm mb-4">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 font-weight-bold text-dark">
                                        <i class="bx bx-check-square me-2 text-success"></i>Ringkasan Kehadiran Pembelajaran
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row row-cols-2 row-cols-md-4 g-3 text-center">
                                        <div class="col">
                                            <div class="p-3 border rounded radius-10 bg-light">
                                                <h4 class="mb-1 fw-bold text-success">{{ $attendanceStats['hadir'] }}</h4>
                                                <small class="text-muted font-12">Hadir</small>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="p-3 border rounded radius-10 bg-light">
                                                <h4 class="mb-1 fw-bold text-info">{{ $attendanceStats['izin'] }}</h4>
                                                <small class="text-muted font-12">Izin</small>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="p-3 border rounded radius-10 bg-light">
                                                <h4 class="mb-1 fw-bold text-warning">{{ $attendanceStats['sakit'] }}</h4>
                                                <small class="text-muted font-12">Sakit</small>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="p-3 border rounded radius-10 bg-light">
                                                <h4 class="mb-1 fw-bold text-danger">{{ $attendanceStats['alpha'] }}</h4>
                                                <small class="text-muted font-12">Alpha</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <span class="font-13 text-muted">Tingkat Kehadiran: </span>
                                        <span class="badge bg-success font-13 px-3 py-1">{{ $attendanceStats['percentage'] }}%</span>
                                        <small class="text-muted ms-2">({{ $attendanceStats['hadir'] }} dari {{ $attendanceStats['total'] }} sesi)</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Read-Only Assessment Scores Table -->
                            <div class="card radius-15 border shadow-sm">
                                <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0 font-weight-bold text-dark">
                                        <i class="bx bx-award me-2 text-success"></i>Rekap Nilai Penilaian Akademik
                                    </h6>
                                    @if ($activeEnrollment && $activeEnrollment->academicYear)
                                        <span class="badge bg-light-primary text-primary font-12">
                                            {{ $activeEnrollment->academicYear->name }} ({{ $activeEnrollment->academicYear->semester }})
                                        </span>
                                    @endif
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-bordered align-middle mb-0 font-13">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 5%">#</th>
                                                    <th>Mata Pelajaran</th>
                                                    <th>Komponen Penilaian</th>
                                                    <th>Tipe</th>
                                                    <th class="text-center" style="width: 10%">Bobot</th>
                                                    <th class="text-center" style="width: 12%">Nilai</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($scores as $idx => $score)
                                                    <tr>
                                                        <td>{{ $idx + 1 }}</td>
                                                        <td class="fw-bold">{{ $score->assessmentComponent?->teachingAssignment?->mapel?->name ?? '-' }}</td>
                                                        <td>{{ $score->assessmentComponent?->assessmentDefinition?->name ?? '-' }}</td>
                                                        <td>
                                                            <span class="badge bg-light-secondary text-secondary">
                                                                {{ $score->assessmentComponent?->assessmentDefinition?->type ?? '-' }}
                                                            </span>
                                                        </td>
                                                        <td class="text-center">{{ $score->assessmentComponent?->weight ?? 0 }}%</td>
                                                        <td class="text-center">
                                                            <span class="badge bg-light-primary text-primary fw-bold font-13 px-2 py-1">
                                                                {{ number_format((float) $score->score, 2) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-4">
                                                            <i class="bx bx-info-circle font-20 mb-1 d-block"></i>
                                                            Belum ada nilai penilaian akademik yang tercatat untuk semester ini.
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
                @endif
            </div>
        </div>
    </div>
@endsection
