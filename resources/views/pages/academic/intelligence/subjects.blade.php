@extends('layouts.app')

@section('title', 'Intelijen Mata Pelajaran | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('academic.intelligence.index') }}" path='Analisis Mata Pelajaran'></x-breadcrumb>

                {{-- Header Filter & Info Bar --}}
                <div class="card radius-15 border-0 shadow-sm mb-4 bg-subject-gradient">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <h4 class="mb-1 text-white fw-bold"><i class="bx bx-book-bookmark align-middle me-1"></i> Analisis & Indikator Mata Pelajaran</h4>
                                <p class="mb-0 text-white-50">
                                    Tahun Ajaran: 
                                    @if ($year)
                                        <strong class="text-white">{{ $year->name }} ({{ $year->semester }})</strong>
                                    @else
                                        <span class="text-white">Belum Diatur</span>
                                    @endif
                                    | Pemantauan rata-rata nilai, sebaran komponen evaluasi, dan kinerja kurikulum
                                </p>
                            </div>
                            <div>
                                <form action="{{ route('academic.intelligence.subjects') }}" method="GET" class="d-flex align-items-center gap-2">
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
                    {{-- Subject Indicators Table --}}
                    <div class="card radius-15 border shadow-sm">
                        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-bold"><i class="bx bx-table me-1 text-primary"></i> Indikator Capaian Pembelajaran per Mata Pelajaran</h6>
                            <span class="badge bg-light text-dark">{{ $subjectIndicators->count() }} Penugasan Mapel</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Kelas</th>
                                            <th>Pengajar Pengampu</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Komponen Nilai</th>
                                            <th class="text-center">Entri Nilai</th>
                                            <th class="text-center">Rata-rata Nilai</th>
                                            <th class="text-center">Status Capaian</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($subjectIndicators as $idx => $item)
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td class="fw-bold text-dark">{{ $item['mapel_name'] }}</td>
                                                <td><span class="badge bg-light text-dark border">{{ $item['kelas_name'] }}</span></td>
                                                <td>{{ $item['teacher_name'] }}</td>
                                                <td class="text-center">
                                                    @if ($item['assignment_status'] === 'Aktif')
                                                        <span class="badge bg-success">Aktif</span>
                                                    @else
                                                        <span class="badge bg-secondary">Nonaktif</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($item['component_count'] > 0)
                                                        <span class="badge bg-light text-info border">{{ $item['component_count'] }} Komponen</span>
                                                    @else
                                                        <span class="badge bg-light text-danger border">0 Komponen</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $item['scored_count'] }} skor</td>
                                                <td class="text-center">
                                                    @if ($item['average_score'] !== null)
                                                        <span class="fw-bold fs-6 {{ $item['average_score'] >= 75 ? 'text-success' : 'text-danger' }}">
                                                            {{ number_format((float) $item['average_score'], 2) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($item['average_score'] === null)
                                                        <span class="badge bg-light text-muted border">Belum Ada Skor</span>
                                                    @elseif ($item['average_score'] >= 85)
                                                        <span class="badge bg-success">Optimal</span>
                                                    @elseif ($item['average_score'] >= 75)
                                                        <span class="badge bg-primary">Baik</span>
                                                    @elseif ($item['average_score'] >= 60)
                                                        <span class="badge bg-warning text-dark">Cukup</span>
                                                    @else
                                                        <span class="badge bg-danger">Perlu Perhatian</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center py-4 text-muted">Belum ada data mata pelajaran pada tahun ajaran ini.</td>
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
