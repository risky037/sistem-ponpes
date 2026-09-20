@extends('layouts.app')

@section('title', 'Mata Pelajaran | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Mata Pelajaran'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                            <div>
                                <h5 class="mb-0 fw-bold">Kurikulum & Mata Pelajaran</h5>
                                <p class="text-muted small mb-0">Kelola mata pelajaran dan kitab kajian per tingkatan kelas</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
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
                                    Tambah Mata Pelajaran
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
                                                <th>Kode</th>
                                                <th>Mata Pelajaran</th>
                                                <th>Kelas</th>
                                                <th>Status</th>
                                                <th>Keterangan</th>
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

    <x-modal-form id='createModal' title='Tambah Mata Pelajaran' fn="{{ route('mapel.store') }}" method="POST">
        @csrf
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
            <x-input type='text' name='code' id="code" label='Kode Mata Pelajaran'
                placeholder='contoh: MPL-ALF-01' attribute="required"></x-input>
        </div>
        <div class="mb-3">
            <x-input type='text' name='name' id="name" label='Nama Mata Pelajaran'
                placeholder='contoh: Alfiyah Ibnu Malik' attribute="required"></x-input>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Keterangan / Deskripsi</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Keterangan kurikulum atau kitab acuan"></textarea>
        </div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
            <label class="form-check-label" for="is_active">Status Aktif</label>
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
                    url: "{{ route('mapel.index') }}",
                    data: function(d) {
                        d.kelas_id = $('#filter_kelas').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'code_badge', name: 'code' },
                    { data: 'name', name: 'name' },
                    { data: 'kelas_name', name: 'kelas.kelas' },
                    { data: 'is_active', name: 'is_active' },
                    { data: 'description', name: 'description', defaultContent: '-' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#filter_kelas').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endpush
