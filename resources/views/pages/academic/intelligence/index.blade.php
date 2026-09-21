@extends('layouts.app')

@section('title', 'Intelijen Akademik — Dashboard KPI | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('academic.administration.index') }}" path='Intelijen Akademik'></x-breadcrumb>

                {{-- Header Filter & Actions Bar --}}
                <div class="card radius-15 border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); color: #fff;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <h4 class="mb-1 text-white fw-bold"><i class="bx bx-analyse align-middle me-1"></i> Intelijen Akademik & Indikator Kinerja</h4>
                                <p class="mb-0 text-white-50">
                                    Tahun Ajaran: 
                                    @if ($year)
                                        <strong class="text-white">{{ $year->name }} ({{ $year->semester }})</strong>
                                        @if ($year->is_active)
                                            <span class="badge bg-success ms-1">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary ms-1">Arsip</span>
                                        @endif
                                    @else
                                        <span class="text-white">Belum Diatur</span>
                                    @endif
                                    | Waktu Komputasi: <span class="badge bg-light text-dark">{{ $dashboardData ? \Illuminate\Support\Carbon::parse($dashboardData['generated_at'])->translatedFormat('d M Y H:i:s') : '-' }}</span>
                                </p>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <form action="{{ route('academic.intelligence.index') }}" method="GET" class="d-flex align-items-center gap-2">
                                    <select name="academic_year_id" class="form-select form-select-sm bg-white text-dark fw-semibold" onchange="this.form.submit()">
                                        @foreach ($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ $year && $year->id === $ay->id ? 'selected' : '' }}>
                                                {{ $ay->name }} ({{ $ay->semester }}) {{ $ay->is_active ? '★' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                                @if ($year)
                                    @can('intelligence.dashboard')
                                        <form action="{{ route('academic.intelligence.refresh', $year->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-light btn-sm text-primary fw-bold" title="Bersihkan cache & hitung ulang live metrics">
                                                <i class="bx bx-refresh me-1"></i> Segarkan Data
                                            </button>
                                        </form>
                                    @endcan
                                    @can('intelligence.snapshot')
                                        <form action="{{ route('academic.intelligence.snapshot', $year->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Simpan snapshot historis metrik saat ini untuk tahun ajaran {{ $year->name }}?')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-light btn-sm fw-bold" title="Bekukan snapshot untuk histori">
                                                <i class="bx bx-camera me-1"></i> Simpan Snapshot
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if (! $year)
                    <div class="alert alert-warning border-0 shadow-sm radius-15 p-4 text-center">
                        <i class="bx bx-calendar-exclamation fs-1 text-warning d-block mb-2"></i>
                        <h5 class="fw-bold">Belum Ada Tahun Ajaran</h5>
                        <p class="mb-0 text-muted">Silakan konfigurasi master Tahun Ajaran terlebih dahulu untuk mengaktifkan lapisan intelijen akademik.</p>
                    </div>
                @else
                    @php
                        $kpis = $dashboardData['kpis'] ?? [];
                        $attendance = $dashboardData['attendance'] ?? [];
                        $grades = $dashboardData['grades'] ?? [];
                        $workloadStats = $dashboardData['workload_stats'] ?? [];
                        $classHealth = $dashboardData['class_health'] ?? collect();
                    @endphp

                    {{-- Section 1: Executive KPI Cards --}}
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mb-4">
                        {{-- Santri Aktif --}}
                        <div class="col">
                            <div class="card radius-15 border shadow-sm h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <p class="text-muted mb-1 text-uppercase small fw-semibold">Santri Aktif</p>
                                            <h4 class="mb-0 fw-bold text-primary">{{ number_format($kpis['active_enrollments'] ?? 0) }}</h4>
                                            <small class="text-muted">{{ $kpis['total_classes'] ?? 0 }} Kelas Terdaftar</small>
                                        </div>
                                        <div class="avatar-sm bg-light-primary text-primary rounded-circle p-2 ms-auto">
                                            <i class="bx bx-user-check fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Realisasi Sesi Pengajaran --}}
                        <div class="col">
                            <div class="card radius-15 border shadow-sm h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <p class="text-muted mb-1 text-uppercase small fw-semibold">Realisasi Sesi</p>
                                            <h4 class="mb-0 fw-bold text-info">
                                                {{ $kpis['session_fulfillment_rate'] !== null ? $kpis['session_fulfillment_rate'].'%' : '-' }}
                                            </h4>
                                            <small class="text-muted">{{ $kpis['completed_sessions_count'] ?? 0 }} / {{ ($kpis['completed_sessions_count'] ?? 0) + ($kpis['planned_sessions_count'] ?? 0) }} Sesi</small>
                                        </div>
                                        <div class="avatar-sm bg-light-info text-info rounded-circle p-2 ms-auto">
                                            <i class="bx bx-calendar-check fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Indeks Kehadiran Global --}}
                        <div class="col">
                            <div class="card radius-15 border shadow-sm h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <p class="text-muted mb-1 text-uppercase small fw-semibold">Indeks Kehadiran</p>
                                            <h4 class="mb-0 fw-bold text-success">
                                                {{ $kpis['attendance_rate'] !== null ? $kpis['attendance_rate'].'%' : '-' }}
                                            </h4>
                                            <small class="text-danger fw-semibold">{{ $attendance['at_risk_attendance_count'] ?? 0 }} Santri &lt; 75%</small>
                                        </div>
                                        <div class="avatar-sm bg-light-success text-success rounded-circle p-2 ms-auto">
                                            <i class="bx bx-check-double fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Kelengkapan Evaluasi --}}
                        <div class="col">
                            <div class="card radius-15 border shadow-sm h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <p class="text-muted mb-1 text-uppercase small fw-semibold">Kelengkapan Nilai</p>
                                            <h4 class="mb-0 fw-bold text-warning">
                                                {{ $kpis['evaluation_completion_rate'] !== null ? $kpis['evaluation_completion_rate'].'%' : '-' }}
                                            </h4>
                                            <small class="text-muted">Status Lengkap</small>
                                        </div>
                                        <div class="avatar-sm bg-light-warning text-warning rounded-circle p-2 ms-auto">
                                            <i class="bx bx-clipboard fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Rata-rata Nilai Institusi --}}
                        <div class="col">
                            <div class="card radius-15 border shadow-sm h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <p class="text-muted mb-1 text-uppercase small fw-semibold">Rata-rata Institusi</p>
                                            <h4 class="mb-0 fw-bold text-dark">
                                                {{ $kpis['average_score'] !== null ? number_format((float) $kpis['average_score'], 2) : '-' }}
                                            </h4>
                                            <small class="text-muted">{{ $grades['total_scored_students'] ?? 0 }} Santri Terekap</small>
                                        </div>
                                        <div class="avatar-sm bg-light-secondary text-secondary rounded-circle p-2 ms-auto">
                                            <i class="bx bx-bar-chart-alt fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Visual Charts Grid --}}
                    <div class="row g-3 mb-4">
                        {{-- Chart 1: Tren Kehadiran Bulanan --}}
                        <div class="col-12 col-xl-8">
                            <div class="card radius-15 border shadow-sm h-100">
                                <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold"><i class="bx bx-trending-up me-1 text-success"></i> Tren Kehadiran Waktu Pembelajaran</h6>
                                    <small class="text-muted">Tingkat kehadiran (%) per bulan</small>
                                </div>
                                <div class="card-body">
                                    <div id="chart-attendance-trend" style="min-height: 320px;"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Chart 2: Komposisi Realisasi Sesi --}}
                        <div class="col-12 col-xl-4">
                            <div class="card radius-15 border shadow-sm h-100">
                                <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                                    <h6 class="mb-0 fw-bold"><i class="bx bx-pie-chart-alt-2 me-1 text-info"></i> Status Sesi Pembelajaran</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-center">
                                    <div id="chart-session-status" style="min-height: 280px;"></div>
                                    <div class="row text-center mt-3 pt-2 border-top">
                                        <div class="col">
                                            <span class="small text-muted d-block">Selesai</span>
                                            <span class="fw-bold text-success">{{ $kpis['completed_sessions_count'] ?? 0 }}</span>
                                        </div>
                                        <div class="col">
                                            <span class="small text-muted d-block">Terencana</span>
                                            <span class="fw-bold text-primary">{{ $kpis['planned_sessions_count'] ?? 0 }}</span>
                                        </div>
                                        <div class="col">
                                            <span class="small text-muted d-block">Batal</span>
                                            <span class="fw-bold text-danger">{{ $kpis['cancelled_sessions_count'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Grade Distribution & Class Attendance Comparison --}}
                    <div class="row g-3 mb-4">
                        {{-- Chart 3: Distribusi Rentang Nilai (Grade Bands) --}}
                        <div class="col-12 col-xl-6">
                            <div class="card radius-15 border shadow-sm h-100">
                                <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold"><i class="bx bx-bar-chart me-1 text-primary"></i> Distribusi Rentang Nilai Akademik</h6>
                                    <small class="text-muted">Berdasarkan rata-rata nilai rekap</small>
                                </div>
                                <div class="card-body">
                                    <div id="chart-grade-distribution" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Chart 4: Komparasi Kehadiran Antar Kelas --}}
                        <div class="col-12 col-xl-6">
                            <div class="card radius-15 border shadow-sm h-100">
                                <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold"><i class="bx bx-stats me-1 text-warning"></i> Komparasi Kehadiran per Kelas</h6>
                                    <small class="text-muted">Tingkat kehadiran rata-rata (%)</small>
                                </div>
                                <div class="card-body">
                                    <div id="chart-class-attendance" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 4: Class Operational Health Matrix --}}
                    <div class="card radius-15 border shadow-sm mb-4">
                        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-bold"><i class="bx bx-shield-quarter me-1 text-primary"></i> Matriks Kesehatan Operasional Kelas</h6>
                            <span class="badge bg-light text-dark">{{ $classHealth->count() }} Kelas Terdata</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Kelas</th>
                                            <th>Wali Kelas</th>
                                            <th class="text-center">Santri Aktif</th>
                                            <th class="text-center">Penugasan</th>
                                            <th class="text-center">Sesi Selesai / Rencana</th>
                                            <th class="text-center">Kehadiran</th>
                                            <th class="text-center">Rata-rata Nilai</th>
                                            <th class="text-center">Kelayakan Evaluasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($classHealth as $idx => $cls)
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td class="fw-bold">{{ $cls['kelas_name'] }}</td>
                                                <td>{{ $cls['wali_kelas_name'] }}</td>
                                                <td class="text-center">{{ $cls['active_students_count'] }}</td>
                                                <td class="text-center">{{ $cls['total_assignments_count'] }}</td>
                                                <td class="text-center">
                                                    <span class="text-success fw-bold">{{ $cls['completed_sessions_count'] }}</span>
                                                    / <span class="text-muted">{{ $cls['planned_sessions_count'] }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if ($cls['attendance_rate'] !== null)
                                                        <span class="badge {{ $cls['attendance_rate'] >= 75.0 ? 'bg-success' : 'bg-danger' }}">
                                                            {{ $cls['attendance_rate'] }}%
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($cls['average_score'] !== null)
                                                        <span class="fw-bold">{{ number_format((float) $cls['average_score'], 2) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($cls['evaluation_completion_rate'] !== null)
                                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                                            <div class="progress flex-grow-1" style="height: 6px; min-width: 60px;">
                                                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ $cls['evaluation_completion_rate'] }}%"></div>
                                                            </div>
                                                            <small class="fw-semibold text-muted">{{ $cls['evaluation_completion_rate'] }}%</small>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center py-4 text-muted">Belum ada data operasional kelas pada tahun ajaran ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Section 5: Historical Snapshots Audit Log --}}
                    @if ($snapshots->isNotEmpty())
                        <div class="card radius-15 border shadow-sm">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h6 class="mb-0 fw-bold"><i class="bx bx-history me-1 text-secondary"></i> Arsip Snapshot Historis Tahun Ajaran</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tanggal Snapshot</th>
                                                <th>Domain Snapshot</th>
                                                <th>Perekam</th>
                                                <th>Ringkasan Metrik</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($snapshots->take(10) as $snap)
                                                <tr>
                                                    <td>{{ $snap->snapshot_date->format('d M Y') }}</td>
                                                    <td><span class="badge bg-light text-dark border">{{ $snap->snapshot_type }}</span></td>
                                                    <td>{{ $snap->capturedBy?->name ?? 'Sistem / Otomatis' }}</td>
                                                    <td class="small text-muted">
                                                        @if ($snap->snapshot_type === 'institutional_kpi')
                                                            Santri: {{ $snap->metrics['active_enrollments'] ?? '-' }}, Kehadiran: {{ $snap->metrics['attendance_rate'] ?? '-' }}%, Rata-rata: {{ $snap->metrics['average_score'] ?? '-' }}
                                                        @elseif ($snap->snapshot_type === 'teacher_workload')
                                                            Pengajar: {{ $snap->metrics['total_active_teachers'] ?? '-' }}, Sesi Selesai: {{ $snap->metrics['total_sessions_completed'] ?? '-' }}
                                                        @else
                                                            {{ count($snap->metrics) }} metrik terarsip
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection

@push('js')
@if ($year && $dashboardData)
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Monthly Attendance Trend Area Chart
    const monthlyTrendData = @json($attendance['monthly_trend'] ?? []);
    const months = monthlyTrendData.map(item => item.label);
    const rates = monthlyTrendData.map(item => item.rate !== null ? item.rate : 0);

    if (months.length > 0) {
        new ApexCharts(document.querySelector("#chart-attendance-trend"), {
            series: [{
                name: 'Tingkat Kehadiran (%)',
                data: rates
            }],
            chart: {
                type: 'area',
                height: 320,
                toolbar: { show: false }
            },
            colors: ['#0d6efd'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            xaxis: { categories: months },
            yaxis: { min: 0, max: 100 },
            tooltip: {
                y: { formatter: val => val + " %" }
            }
        }).render();
    } else {
        document.querySelector("#chart-attendance-trend").innerHTML = '<div class="text-center text-muted py-5">Belum ada catatan presensi pembelajaran terdata.</div>';
    }

    // 2. Session Status Donut Chart
    const completed = {{ $kpis['completed_sessions_count'] ?? 0 }};
    const planned = {{ $kpis['planned_sessions_count'] ?? 0 }};
    const cancelled = {{ $kpis['cancelled_sessions_count'] ?? 0 }};

    if ((completed + planned + cancelled) > 0) {
        new ApexCharts(document.querySelector("#chart-session-status"), {
            series: [completed, planned, cancelled],
            labels: ['Selesai', 'Terencana', 'Dibatalkan'],
            chart: {
                type: 'donut',
                height: 280
            },
            colors: ['#198754', '#0d6efd', '#dc3545'],
            legend: { position: 'bottom' }
        }).render();
    } else {
        document.querySelector("#chart-session-status").innerHTML = '<div class="text-center text-muted py-5">Belum ada sesi pembelajaran dibuat.</div>';
    }

    // 3. Grade Distribution Column Chart
    const bands = @json($grades['bands'] ?? []);
    const bandLabels = Object.values(bands).map(b => b.label);
    const bandCounts = Object.values(bands).map(b => b.count);

    if (bandLabels.length > 0) {
        new ApexCharts(document.querySelector("#chart-grade-distribution"), {
            series: [{
                name: 'Jumlah Santri',
                data: bandCounts
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false }
            },
            colors: ['#6f42c1'],
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '45%',
                    distributed: true
                }
            },
            dataLabels: { enabled: true },
            xaxis: { categories: bandLabels },
            legend: { show: false }
        }).render();
    } else {
        document.querySelector("#chart-grade-distribution").innerHTML = '<div class="text-center text-muted py-5">Belum ada rekap nilai santri.</div>';
    }

    // 4. Class Attendance Comparison Bar Chart
    const classComparison = @json($attendance['class_comparison'] ?? []);
    const classNames = classComparison.map(c => c.kelas_name);
    const classRates = classComparison.map(c => c.attendance_rate !== null ? c.attendance_rate : 0);

    if (classNames.length > 0) {
        new ApexCharts(document.querySelector("#chart-class-attendance"), {
            series: [{
                name: 'Kehadiran (%)',
                data: classRates
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false }
            },
            colors: ['#ffc107'],
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    barHeight: '50%'
                }
            },
            xaxis: { min: 0, max: 100 },
            yaxis: { categories: classNames },
            tooltip: {
                y: { formatter: val => val + " %" }
            }
        }).render();
    } else {
        document.querySelector("#chart-class-attendance").innerHTML = '<div class="text-center text-muted py-5">Belum ada data kelas terdaftar.</div>';
    }
});
</script>
@endif
@endpush
