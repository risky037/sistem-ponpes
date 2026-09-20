@extends('layouts.app')

@section('title', 'Penugasan Mengajar | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Penugasan Mengajar'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                            <div>
                                <h5 class="mb-0 fw-bold">Penugasan Pengajar Mata Pelajaran</h5>
                                <p class="text-muted small mb-0">Kelola penetapan ustadz/ustadzah pengampu mata pelajaran per kelas dan tahun ajaran</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="me-2">
                                    <select id="filter_academic_year" class="form-select form-select-sm">
                                        <option value="">Semua Tahun Ajaran</option>
                                        @foreach ($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ $activeYear && $activeYear->id === $ay->id ? 'selected' : '' }}>
                                                {{ $ay->name }} ({{ $ay->semester }}) {{ $ay->is_active ? '★' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="me-2">
                                    <select id="filter_kelas" class="form-select form-select-sm">
                                        <option value="">Semua Kelas</option>
                                        @foreach ($kelasList as $k)
                                            <option value="{{ $k->id }}">
                                                {{ $k->tingkatan }} - {{ $k->kelas }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#createModal">
                                    <i class="bx bx-plus"></i>
                                    Tugaskan Pengajar
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered align-middle dataTable" style="width:100%" role="grid"
                                        id="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th>Nama Pengajar</th>
                                                <th>Mata Pelajaran</th>
                                                <th>Kelas</th>
                                                <th>Tahun Ajaran</th>
                                                <th>Status</th>
                                                <th>Catatan</th>
                                                <th style="width: 15%">Aksi</th>
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

    <x-modal-form id='createModal' title='Tugaskan Pengajar Mata Pelajaran' fn="{{ route('teaching-assignment.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label font-weight-bold">Tahun Ajaran <span class="text-danger">*</span></label>
            <select name="academic_year_id" class="form-select" required>
                <option value="">Pilih Tahun Ajaran</option>
                @foreach ($academicYears as $ay)
                    <option value="{{ $ay->id }}" {{ $activeYear && $activeYear->id === $ay->id ? 'selected' : '' }}>
                        {{ $ay->name }} ({{ $ay->semester }}) {{ $ay->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Kelas <span class="text-danger">*</span></label>
            <select name="kelas_id" id="modal_kelas_id" class="form-select" required>
                <option value="">Pilih Kelas</option>
                @foreach ($kelasList as $k)
                    <option value="{{ $k->id }}">{{ $k->tingkatan }} - {{ $k->kelas }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Mata Pelajaran <span class="text-danger">*</span></label>
            <select name="mapel_id" id="modal_mapel_id" class="form-select" required>
                <option value="">Pilih Mata Pelajaran</option>
                @foreach ($mapels as $m)
                    <option value="{{ $m->id }}" data-kelas="{{ $m->kelas_id }}">
                        {{ $m->name }} ({{ $m->code }}) - {{ $m->kelas?->tingkatan }} {{ $m->kelas?->kelas }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Ustadz / Ustadzah (Pengajar) <span class="text-danger">*</span></label>
            <select name="user_id" class="form-select" required>
                <option value="">Pilih Tenaga Pengajar</option>
                @foreach ($teachers as $t)
                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->roles->pluck('name')->implode(', ') }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Status Awal</label>
            <select name="status" class="form-select">
                <option value="Aktif" selected>Aktif</option>
                <option value="Nonaktif">Nonaktif</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Catatan Penugasan</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Catatan opsional penugasan mengajar"></textarea>
        </div>
    </x-modal-form>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            var table = $('#table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('teaching-assignment.index') }}",
                    data: function(d) {
                        d.academic_year_id = $('#filter_academic_year').val();
                        d.kelas_id = $('#filter_kelas').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'teacher_name', name: 'user.name' },
                    { data: 'mapel_name', name: 'mapel.name' },
                    { data: 'kelas_name', name: 'kelas.kelas' },
                    { data: 'academic_year_name', name: 'academic_year.name' },
                    { data: 'status', name: 'status' },
                    { data: 'notes', name: 'notes' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#filter_academic_year, #filter_kelas').on('change', function() {
                table.ajax.reload();
            });

            // Filter mapel options when kelas changes in create modal
            $('#modal_kelas_id').on('change', function() {
                var selectedKelas = $(this).val();
                $('#modal_mapel_id option').each(function() {
                    var mapelKelas = $(this).data('kelas');
                    if (!selectedKelas || !mapelKelas || mapelKelas == selectedKelas) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
    </script>
@endpush
