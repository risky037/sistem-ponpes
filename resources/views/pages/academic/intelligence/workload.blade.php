@extends('layouts.app')

@section('title', 'Intelijen Beban Mengajar Guru | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('academic.intelligence.index') }}" path='Beban Mengajar Guru'></x-breadcrumb>

                {{-- Header Filter & Info Bar --}}
                <div class="card radius-15 border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #198754 0%, #146c43 100%); color: #fff;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <h4 class="mb-1 text-white fw-bold"><i class="bx bx-user-check align-middle me-1"></i> Distribusi & Beban Mengajar Guru</h4>
                                <p class="mb-0 text-white-50">
                                    Tahun Ajaran: 
                                    @if ($year)
                                        <strong class="text-white">{{ $year->name }} ({{ $year->semester }})</strong>
                                    @else
                                        <span class="text-white">Belum Diatur</span>
                                    @endif
                                    | Monitoring realisasi sesi pembelajaran dan beban penugasan per asatidz
                                </p>
                            </div>
                            <div>
                                <form action="{{ route('academic.intelligence.workload') }}" method="GET" class="d-flex align-items-center gap-2">
                                    <select name="academic_year_id" class="form-select form-select-sm bg-white text-dark fw-semibold" onchange="this.form.submit()">
                                        @foreach ($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ $year && $year->id === $ay->id ? 'selected' : '' }}>
                                                {{ $ay->name }} ({{ $ay->semester }})
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                @if (! $year)
                    <div class="alert alert-warning border-0 shadow-sm radius-15 p-4 text-center">
                        <h5 class="fw-bold">Belum Ada Tahun Ajaran</h5>
                        <p class="mb-0 text-muted">Silakan konfigurasi tahun ajaran terlebih dahulu.</p>
                    </div>
                @else
                    {{-- Workload Summary Metric Cards --}}
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
                        <div class="col">
                            <div class="card radius-15 border shadow-sm h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <p class="text-muted mb-1 text-uppercase small fw-semibold">Pengajar Bertugas</p>
                                            <h4 class="mb-0 fw-bold text-success">{{ $workloadStats['total_active_teachers'] ?? 0 }}</h4>
                                            <small class="text-muted">Dewan Asatidz / Guru</small>
                                        </div>
                                        <div class="avatar-sm bg-light-success text-success rounded-circle p-2 ms-auto">
                                            <i class="bx bx-user-pin fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col">
                            <div class="card radius-15 border shadow-sm h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <p class="text-muted mb-1 text-uppercase small fw-semibold">Total Penugasan</p>
                                            <h4 class="mb-0 fw-bold text-primary">{{ $workloadStats['total_assignments'] ?? 0 }}</h4>
                                            <small class="text-muted">Rata-rata: {{ $workloadStats['avg_assignments_per_teacher'] ?? 0 }} / Guru</small>
                                        </div>
                                        <div class="avatar-sm bg-light-primary text-primary rounded-circle p-2 ms-auto">
                                            <i class="bx bx-book-reader fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col">
                            <div class="card radius-15 border shadow-sm h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <p class="text-muted mb-1 text-uppercase small fw-semibold">Sesi Terlaksana</p>
                                            <h4 class="mb-0 fw-bold text-info">{{ $workloadStats['total_sessions_completed'] ?? 0 }}</h4>
                                            <small class="text-muted">Rata-rata: {{ $workloadStats['avg_sessions_per_teacher'] ?? 0 }} Sesi / Guru</small>
                                        </div>
                                        <div class="avatar-sm bg-light-info text-info rounded-circle p-2 ms-auto">
                                            <i class="bx bx-calendar-event fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col">
                            <div class="card radius-15 border shadow-sm h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <p class="text-muted mb-1 text-uppercase small fw-semibold">Realisasi Sesi Global</p>
                                            <h4 class="mb-0 fw-bold text-dark">
                                                {{ $workloadStats['overall_fulfillment_rate'] !== null ? $workloadStats['overall_fulfillment_rate'].'%' : '-' }}
                                            </h4>
                                            <small class="text-muted">Persentase Ketercapaian</small>
                                        </div>
                                        <div class="avatar-sm bg-light-warning text-warning rounded-circle p-2 ms-auto">
                                            <i class="bx bx-check-shield fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Faculty Workload Table --}}
                    <div class="card radius-15 border shadow-sm">
                        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-bold"><i class="bx bx-list-check me-1 text-success"></i> Rincian Beban Kerja & Kinerja Pengajar</h6>
                            <span class="badge bg-light text-dark">{{ $workloadOverview->count() }} Guru Terdata</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Nama Pengajar</th>
                                            <th>Email</th>
                                            <th class="text-center">Mapel</th>
                                            <th class="text-center">Kelas</th>
                                            <th class="text-center">Slot Jadwal / Pekan</th>
                                            <th class="text-center">Sesi Selesai / Terencana</th>
                                            <th class="text-center">Tingkat Realisasi</th>
                                            <th class="text-center">Komponen & Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($workloadOverview as $idx => $row)
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td class="fw-bold text-dark">{{ $row['name'] }}</td>
                                                <td class="small text-muted">{{ $row['email'] }}</td>
                                                <td class="text-center"><span class="badge bg-light text-primary border">{{ $row['subjects_count'] }} Mapel</span></td>
                                                <td class="text-center"><span class="badge bg-light text-dark border">{{ $row['classes_count'] }} Kelas</span></td>
                                                <td class="text-center"><span class="fw-semibold">{{ $row['weekly_schedule_slots'] }}</span></td>
                                                <td class="text-center">
                                                    <span class="text-success fw-bold">{{ $row['completed_sessions'] }}</span>
                                                    / <span class="text-muted">{{ $row['total_sessions'] }}</span>
                                                    @if ($row['cancelled_sessions'] > 0)
                                                        <small class="text-danger">({{ $row['cancelled_sessions'] }} batal)</small>
                                                    @endif
                                                </td>
                                                <td class="text-center" style="min-width: 140px;">
                                                    @if ($row['fulfillment_rate'] !== null)
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $row['fulfillment_rate'] }}%"></div>
                                                            </div>
                                                            <span class="small fw-semibold">{{ $row['fulfillment_rate'] }}%</span>
                                                        </div>
                                                    @else
                                                        <span class="text-muted small">Belum ada sesi</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-secondary border">
                                                        {{ $row['components_count'] }} Komponen ({{ $row['scored_records_count'] }} Skor)
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center py-4 text-muted">Belum ada penugasan mengajar untuk tahun ajaran ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
