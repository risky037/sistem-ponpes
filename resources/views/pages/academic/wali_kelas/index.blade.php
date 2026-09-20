@extends('layouts.app')

@section('title', 'Penugasan Wali Kelas | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Wali Kelas'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                            <div>
                                <h5 class="mb-0 fw-bold">Penugasan Wali Kelas</h5>
                                <p class="text-muted small mb-0">Kelola penetapan ustadz/ustadzah wali kelas per tahun ajaran</p>
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
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#createModal">
                                    <i class="bx bx-plus"></i>
                                    Tugaskan Wali Kelas
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
                                                <th>Nama Wali Kelas</th>
                                                <th>Kelas</th>
                                                <th>Tahun Ajaran</th>
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

    <x-modal-form id='createModal' title='Tugaskan Wali Kelas' fn="{{ route('wali-kelas-assignment.store') }}" method="POST">
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
            <select name="kelas_id" class="form-select" required>
                <option value="">Pilih Kelas</option>
                @foreach ($kelasList as $k)
                    <option value="{{ $k->id }}">{{ $k->tingkatan }} - {{ $k->kelas }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Ustadz / Ustadzah (Wali Kelas) <span class="text-danger">*</span></label>
            <select name="user_id" class="form-select" required>
                <option value="">Pilih Tenaga Pengajar</option>
                @foreach ($teachers as $t)
                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->roles->pluck('name')->implode(', ') }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Catatan Penugasan</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Catatan opsional penugasan"></textarea>
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
                    url: "{{ route('wali-kelas-assignment.index') }}",
                    data: function(d) {
                        d.academic_year_id = $('#filter_academic_year').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'teacher_name', name: 'user.name' },
                    { data: 'kelas_name', name: 'kelas.kelas' },
                    { data: 'academic_year_name', name: 'academic_year.name' },
                    { data: 'notes', name: 'notes' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#filter_academic_year').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endpush
