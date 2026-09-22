@extends('layouts.app')

@section('title', 'Presensi Pembelajaran | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Presensi Pembelajaran'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <x-card-toolbar title="Presensi Pembelajaran Santri">
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
                                <select id="filter_status" class="form-select form-select-sm">
                                    <option value="">Semua Status Sesi</option>
                                    <option value="Planned">Planned</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                        </x-card-toolbar>

                        <div class="row">
                            <div class="col">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle dataTable" style="width:100%" role="grid"
                                        id="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th>Tanggal</th>
                                                <th>Kelas</th>
                                                <th>Mata Pelajaran</th>
                                                <th>Pengajar</th>
                                                <th>Rekap Presensi</th>
                                                <th>Status Sesi</th>
                                                <th style="width: 12%">Aksi</th>
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
                    url: "{{ route('attendance.index') }}",
                    data: function(d) {
                        d.academic_year_id = $('#filter_academic_year').val();
                        d.kelas_id = $('#filter_kelas').val();
                        d.status = $('#filter_status').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'formatted_date', name: 'session_date' },
                    { data: 'kelas_name', name: 'teachingAssignment.kelas.kelas' },
                    { data: 'mapel_name', name: 'teachingAssignment.mapel.name' },
                    { data: 'teacher_name', name: 'teachingAssignment.user.name' },
                    { data: 'attendance_summary', name: 'attendance_summary', orderable: false, searchable: false },
                    { data: 'status_badge', name: 'status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#filter_academic_year, #filter_kelas, #filter_status').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endpush
