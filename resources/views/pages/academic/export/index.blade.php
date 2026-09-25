@extends('layouts.app')

@section('title', 'Pusat Ekspor Data Akademik | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Pusat Ekspor Akademik'></x-breadcrumb>

                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="mb-0 fw-bold">Pusat Ekspor & Pelaporan Akademik</h4>
                        <p class="text-muted mb-0">Unduh data akademik dalam format Excel Spreadsheet (.xlsx) atau cetak dokumen ramah printer / PDF.</p>
                    </div>
                    <div>
                        <a href="{{ route('academic.administration.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-arrow-back me-1"></i> Kembali ke Administrasi
                        </a>
                    </div>
                </div>

                {{-- 5 Export Cards Grid --}}
                <div class="row g-4 mb-4">
                    {{-- 1. Pendaftaran Akademik --}}
                    <div class="col-12 col-lg-6">
                        <div class="card radius-15 border shadow-sm h-100">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold text-primary">
                                    <i class="bx bx-id-card me-2"></i>1. Ekspor Pendaftaran Akademik
                                </h5>
                                <small class="text-muted">Data penempatan santri per tahun ajaran, kelas, dan angkatan.</small>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('academic.export.enrollment') }}" method="POST" target="_blank">
                                    @csrf
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Tahun Ajaran</label>
                                            <select name="academic_year_id" class="form-select form-select-sm">
                                                <option value="">Semua Tahun Ajaran</option>
                                                @foreach ($academicYears as $ay)
                                                    <option value="{{ $ay->id }}" {{ $activeYear && $activeYear->id === $ay->id ? 'selected' : '' }}>
                                                        {{ $ay->name }} ({{ $ay->semester }}) {{ $ay->is_active ? '★' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Kelas</label>
                                            <select name="kelas_id" class="form-select form-select-sm">
                                                <option value="">Semua Kelas</option>
                                                @foreach ($kelasList as $k)
                                                    <option value="{{ $k->id }}">{{ $k->tingkatan }} - {{ $k->kelas }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">Status Pendaftaran</label>
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="">Semua Status</option>
                                                <option value="Aktif" selected>Hanya Santri Aktif</option>
                                                <option value="Nonaktif">Nonaktif</option>
                                                <option value="Lulus">Lulus</option>
                                                <option value="Pindah">Pindah</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="format" value="xlsx" class="btn btn-sm btn-success flex-fill">
                                            <i class="bx bx-spreadsheet me-1"></i> Unduh Excel
                                        </button>
                                        <button type="submit" name="format" value="print" class="btn btn-sm btn-outline-dark flex-fill">
                                            <i class="bx bx-printer me-1"></i> Cetak / PDF
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Penugasan Guru & Jadwal --}}
                    <div class="col-12 col-lg-6">
                        <div class="card radius-15 border shadow-sm h-100">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold text-success">
                                    <i class="bx bx-book-reader me-2"></i>2. Ekspor Penugasan Guru & Jadwal
                                </h5>
                                <small class="text-muted">Daftar penugasan pengajaran, guru pengampu, dan alokasi jadwal mingguan.</small>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('academic.export.teaching-assignment') }}" method="POST" target="_blank">
                                    @csrf
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Tahun Ajaran</label>
                                            <select name="academic_year_id" class="form-select form-select-sm">
                                                <option value="">Semua Tahun Ajaran</option>
                                                @foreach ($academicYears as $ay)
                                                    <option value="{{ $ay->id }}" {{ $activeYear && $activeYear->id === $ay->id ? 'selected' : '' }}>
                                                        {{ $ay->name }} ({{ $ay->semester }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Kelas</label>
                                            <select name="kelas_id" class="form-select form-select-sm">
                                                <option value="">Semua Kelas</option>
                                                @foreach ($kelasList as $k)
                                                    <option value="{{ $k->id }}">{{ $k->tingkatan }} - {{ $k->kelas }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">Guru Pengajar</label>
                                            <select name="user_id" class="form-select form-select-sm">
                                                <option value="">Semua Guru Pengajar</option>
                                                @foreach ($teachers as $t)
                                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="format" value="xlsx" class="btn btn-sm btn-success flex-fill">
                                            <i class="bx bx-spreadsheet me-1"></i> Unduh Excel
                                        </button>
                                        <button type="submit" name="format" value="print" class="btn btn-sm btn-outline-dark flex-fill">
                                            <i class="bx bx-printer me-1"></i> Cetak / PDF
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Rekapitulasi Presensi --}}
                    <div class="col-12 col-lg-4">
                        <div class="card radius-15 border shadow-sm h-100">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold text-warning">
                                    <i class="bx bx-check-circle me-2"></i>3. Rekap Presensi
                                </h5>
                                <small class="text-muted">Total sesi, kehadiran (H/I/S/A), dan persentase.</small>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('academic.export.attendance') }}" method="POST" target="_blank">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Tahun Ajaran</label>
                                        <select name="academic_year_id" class="form-select form-select-sm">
                                            <option value="">Semua Tahun Ajaran</option>
                                            @foreach ($academicYears as $ay)
                                                <option value="{{ $ay->id }}" {{ $activeYear && $activeYear->id === $ay->id ? 'selected' : '' }}>
                                                    {{ $ay->name }} ({{ $ay->semester }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Kelas</label>
                                        <select name="kelas_id" class="form-select form-select-sm">
                                            <option value="">Semua Kelas</option>
                                            @foreach ($kelasList as $k)
                                                <option value="{{ $k->id }}">{{ $k->tingkatan }} - {{ $k->kelas }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="format" value="xlsx" class="btn btn-sm btn-success flex-fill">
                                            <i class="bx bx-spreadsheet me-1"></i> Unduh Excel
                                        </button>
                                        <button type="submit" name="format" value="print" class="btn btn-sm btn-outline-dark flex-fill">
                                            <i class="bx bx-printer me-1"></i> Cetak / PDF
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Rekap Nilai & Evaluasi --}}
                    <div class="col-12 col-lg-4">
                        <div class="card radius-15 border shadow-sm h-100">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold text-info">
                                    <i class="bx bx-file me-2"></i>4. Rekap Nilai Evaluasi
                                </h5>
                                <small class="text-muted">Buku nilai komponen & skor penilaian santri.</small>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('academic.export.assessment') }}" method="POST" target="_blank">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Tahun Ajaran</label>
                                        <select name="academic_year_id" class="form-select form-select-sm">
                                            <option value="">Semua Tahun Ajaran</option>
                                            @foreach ($academicYears as $ay)
                                                <option value="{{ $ay->id }}" {{ $activeYear && $activeYear->id === $ay->id ? 'selected' : '' }}>
                                                    {{ $ay->name }} ({{ $ay->semester }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Kelas</label>
                                        <select name="kelas_id" class="form-select form-select-sm">
                                            <option value="">Semua Kelas</option>
                                            @foreach ($kelasList as $k)
                                                <option value="{{ $k->id }}">{{ $k->tingkatan }} - {{ $k->kelas }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Mata Pelajaran</label>
                                        <select name="mapel_id" class="form-select form-select-sm">
                                            <option value="">Semua Mata Pelajaran</option>
                                            @foreach ($mapels as $m)
                                                <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->code }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="format" value="xlsx" class="btn btn-sm btn-success flex-fill">
                                            <i class="bx bx-spreadsheet me-1"></i> Unduh Excel
                                        </button>
                                        <button type="submit" name="format" value="print" class="btn btn-sm btn-outline-dark flex-fill">
                                            <i class="bx bx-printer me-1"></i> Cetak / PDF
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- 5. Agregasi Performa Akademik --}}
                    <div class="col-12 col-lg-4">
                        <div class="card radius-15 border shadow-sm h-100">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold text-danger">
                                    <i class="bx bx-bar-chart-alt-2 me-2"></i>5. Performa Akademik
                                </h5>
                                <small class="text-muted">Ringkasan agregasi kehadiran & nilai rata-rata.</small>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('academic.export.performance') }}" method="POST" target="_blank">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Tahun Ajaran</label>
                                        <select name="academic_year_id" class="form-select form-select-sm">
                                            <option value="">Semua Tahun Ajaran</option>
                                            @foreach ($academicYears as $ay)
                                                <option value="{{ $ay->id }}" {{ $activeYear && $activeYear->id === $ay->id ? 'selected' : '' }}>
                                                    {{ $ay->name }} ({{ $ay->semester }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Kelas</label>
                                        <select name="kelas_id" class="form-select form-select-sm">
                                            <option value="">Semua Kelas</option>
                                            @foreach ($kelasList as $k)
                                                <option value="{{ $k->id }}">{{ $k->tingkatan }} - {{ $k->kelas }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Status Rekapitulasi</label>
                                        <select name="computation_status" class="form-select form-select-sm">
                                            <option value="">Semua Status</option>
                                            <option value="Lengkap">Lengkap</option>
                                            <option value="Sebagian">Sebagian</option>
                                            <option value="Kosong">Kosong</option>
                                        </select>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="format" value="xlsx" class="btn btn-sm btn-success flex-fill">
                                            <i class="bx bx-spreadsheet me-1"></i> Unduh Excel
                                        </button>
                                        <button type="submit" name="format" value="print" class="btn btn-sm btn-outline-dark flex-fill">
                                            <i class="bx bx-printer me-1"></i> Cetak / PDF
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Audit History Log Table --}}
                <div class="card radius-15 border shadow-sm">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="bx bx-history me-2"></i>Riwayat Audit Ekspor Akademik
                        </h5>
                        <small class="text-muted">Catatan riwayat unduhan dokumen untuk kepatuhan dan tata kelola data santri.</small>
                    </div>
                    <div class="card-body">
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
                                        <th class="text-center">Baris</th>
                                        <th>IP Address</th>
                                        <th>Waktu Ekspor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($logs as $log)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="fw-semibold">{{ $log->user?->name ?? 'Sistem' }}</td>
                                            <td>
                                                <span class="badge bg-light-secondary text-secondary border">
                                                    {{ match ($log->export_type) {
                                                        'enrollment' => 'Pendaftaran Santri',
                                                        'teaching_assignment' => 'Penugasan Guru',
                                                        'attendance' => 'Rekap Presensi',
                                                        'assessment' => 'Nilai Evaluasi',
                                                        'performance' => 'Performa Akademik',
                                                        default => $log->export_type,
                                                    } }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($log->format === 'xlsx')
                                                    <span class="badge bg-success"><i class="bx bx-spreadsheet me-1"></i>Excel</span>
                                                @else
                                                    <span class="badge bg-info text-white"><i class="bx bx-printer me-1"></i>Cetak / PDF</span>
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
