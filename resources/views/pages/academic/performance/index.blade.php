@extends('layouts.app')

@section('title', 'Performa Akademik | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Performa Akademik'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <x-card-toolbar title="Rekap & Agregasi Performa Akademik">
                            <div>
                                <select id="filter_academic_year" class="form-select form-select-sm">
                                    <option value="">Semua Tahun Ajaran</option>
                                    @foreach ($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ $activeYear && $activeYear->id === $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }} ({{ $ay->semester }}) {{ $ay->is_active ? '★' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select id="filter_kelas" class="form-select form-select-sm">
                                    <option value="">Semua Kelas</option>
                                    @foreach ($kelasList as $k)
                                        <option value="{{ $k->id }}">
                                            {{ $k->tingkatan }} - {{ $k->kelas }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select id="filter_computation_status" class="form-select form-select-sm">
                                    <option value="">Semua Status Rekap</option>
                                    <option value="Lengkap">Lengkap</option>
                                    <option value="Sebagian">Sebagian</option>
                                    <option value="Kosong">Kosong</option>
                                    <option value="Belum Dihitung">Belum Dihitung</option>
                                </select>
                            </div>
                            @if ($activeYear)
                                <div>
                                    <form action="{{ route('academic.performance.bulk-generate', $activeYear->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hitung ulang rekap performa untuk semua santri aktif pada tahun ajaran aktif?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bx bx-sync me-1"></i> Hitung Semua ({{ $activeYear->name }})
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </x-card-toolbar>

                        <div class="row">
                            <div class="col">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered align-middle dataTable" style="width:100%" role="grid" id="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th>NIS</th>
                                                <th>Nama Santri</th>
                                                <th>Kelas</th>
                                                <th>Tahun Ajaran</th>
                                                <th>Tingkat Presensi</th>
                                                <th>Rata-rata Nilai</th>
                                                <th>Status Rekap</th>
                                                <th style="width: 10%">Aksi</th>
                                            </tr>
                                        </thead>
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

@push('js')
    <script>
        $(document).ready(function() {
            var table = $('#table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('academic.performance.index') }}",
                    data: function(d) {
                        d.academic_year_id = $('#filter_academic_year').val();
                        d.kelas_id = $('#filter_kelas').val();
                        d.computation_status = $('#filter_computation_status').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'nis', name: 'santri.nis' },
                    { data: 'santri_name', name: 'santri.nama_lengkap' },
                    { data: 'kelas_name', name: 'kelas.kelas' },
                    { data: 'academic_year_name', name: 'academicYear.name' },
                    { data: 'attendance_rate_formatted', name: 'performanceSummary.attendance_rate', orderable: false, searchable: false },
                    { data: 'average_score_formatted', name: 'performanceSummary.average_score', orderable: false, searchable: false },
                    { data: 'status_badge', name: 'performanceSummary.computation_status', orderable: false, searchable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false },
                ]
            });

            $('#filter_academic_year, #filter_kelas, #filter_computation_status').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endpush
