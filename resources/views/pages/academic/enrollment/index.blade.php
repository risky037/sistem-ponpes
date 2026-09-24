@extends('layouts.app')

@section('title', 'Pendaftaran Akademik | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Pendaftaran Akademik'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                            <div>
                                <h5 class="mb-0 fw-bold">Pendaftaran Akademik & Penempatan Kelas</h5>
                                <p class="text-muted small mb-0">Kelola penempatan kelas santri per tahun ajaran aktif</p>
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
                                    <select id="filter_batch" class="form-select form-select-sm">
                                        <option value="">Semua Angkatan</option>
                                        @foreach ($studentBatches ?? [] as $batch)
                                            <option value="{{ $batch->id }}">{{ $batch->name }} ({{ $batch->year }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="me-2">
                                    <select id="filter_kelas" class="form-select form-select-sm">
                                        <option value="">Semua Kelas</option>
                                        @foreach ($kelasList as $k)
                                            <option value="{{ $k->id }}">{{ $k->tingkatan }} - {{ $k->kelas }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#createModal">
                                    <i class="bx bx-plus"></i>
                                    Daftarkan Santri
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle dataTable" style="width:100%" role="grid"
                                        id="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th>Nama Santri</th>
                                                <th>NIS</th>
                                                <th>Tahun Ajaran</th>
                                                <th>Kelas</th>
                                                <th>Status</th>
                                                <th>Tanggal Terdaftar</th>
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

    <x-modal-form id='createModal' title='Pendaftaran Akademik Santri' fn="{{ route('academic-enrollment.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label font-weight-bold">Santri <span class="text-danger">*</span></label>
            <select name="santri_id" class="form-select" required>
                <option value="">-- Pilih Santri --</option>
                @foreach ($santris as $s)
                    <option value="{{ $s->id }}">{{ $s->nama_lengkap }} ({{ $s->no_induk }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Tahun Ajaran <span class="text-danger">*</span></label>
            <select name="academic_year_id" class="form-select" required>
                @foreach ($academicYears as $ay)
                    <option value="{{ $ay->id }}" {{ $activeYear && $activeYear->id === $ay->id ? 'selected' : '' }}>
                        {{ $ay->name }} - Semester {{ $ay->semester }} {{ $ay->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Kelas Akademik <span class="text-danger">*</span></label>
            <select name="kelas_id" class="form-select" required>
                <option value="">-- Pilih Kelas --</option>
                @foreach ($kelasList as $k)
                    <option value="{{ $k->id }}">{{ $k->tingkatan }} - {{ $k->kelas }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <x-input type='date' name='enrolled_at' id="enrolled_at" label='Tanggal Terdaftar'
                value="{{ now()->toDateString() }}" attribute="required"></x-input>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Keterangan / Catatan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan opsional (misal: Santri Baru, Pindahan, dsb)"></textarea>
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
                    url: "{{ route('academic-enrollment.index') }}",
                    data: function(d) {
                        d.academic_year_id = $('#filter_academic_year').val();
                        d.student_batch_id = $('#filter_batch').val();
                        d.kelas_id = $('#filter_kelas').val();
                    }
                },
                columns: [
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'santri_name',
                        name: 'santri_name',
                        className: 'fw-bold'
                    },
                    {
                        data: 'santri_nis',
                        name: 'santri_nis'
                    },
                    {
                        data: 'academic_year_name',
                        name: 'academic_year_name'
                    },
                    {
                        data: 'kelas_name',
                        name: 'kelas_name'
                    },
                    {
                        data: 'status',
                        name: 'academic_enrollments.status'
                    },
                    {
                        data: 'enrolled_at',
                        name: 'academic_enrollments.enrolled_at'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                    }
                ]
            });

            $('#filter_academic_year, #filter_batch, #filter_kelas').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endpush
