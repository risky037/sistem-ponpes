@extends('layouts.app')

@section('title', 'Santri | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Santri'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <x-card-toolbar title="Manajemen Data Santri">
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
                                data-bs-target="#importexport">
                                <i class="bx bx-file"></i>
                                Import / Export
                            </button>

                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#exampleModal">
                                <i class="bx bx-plus"></i>
                                Tambah Santri
                            </button>
                        </x-card-toolbar>

                        <div class="row mb-2">
                            <div class="col">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle dataTable" style="width:100%" role="grid"
                                        id="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th>No Induk</th>
                                                <th>Nama Santri</th>
                                                <th>Jenis Kelamin</th>
                                                <th>Tahun Masuk</th>
                                                <th>Status</th>
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

    <x-modal-form id='exampleModal' title='Tambah Data Santri' fn="{{ route('santri.store') }}"
        method="POST" modalSize="modal-lg">
        @csrf
        @include('pages.santri.include.form')
    </x-modal-form>

    <x-modal title="Import & Export Data Santri" id="importexport" modalSize="modal-lg">
        <div class="row mb-3">
            <div class="col">
                <a href="{{ route('santri.download') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bx bx-download"></i> Unduh Format Import Excel
                </a>
            </div>
            <div class="col text-end">
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#statusModal">
                    <i class="bx bx-export"></i> Export Data Santri
                </button>
            </div>
        </div>
        <div class="card bg-light border p-3 mb-3">
            <h6 class="font-weight-bold font-13 mb-2">Ketentuan Format Excel:</h6>
            <ul class="mb-0 font-13 ps-3 text-muted">
                <li>Format tanggal, bulan, dan tahun harus lengkap sesuai template standar.</li>
                <li>Penulisan nomor telepon / WhatsApp dimulai dengan kode negara 62 (contoh: 628123456789).</li>
                <li>Semua kolom wajib diisi sesuai format referensi.</li>
            </ul>
        </div>
        <form action="{{ route('santri.import') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label font-weight-bold">Upload File Excel (.xlsx, .xls)</label>
                <input type="file" name="file" id="file" class="form-control" required accept=".xlsx,.xls">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bx bx-upload"></i> Proses Import
            </button>
        </form>
    </x-modal>

    <x-modal title="Export Data Santri" id="statusModal">
        <form action="{{ route('santri.export') }}" method="post">
            @csrf
            <p class="text-muted font-14 mb-3">Pilih kategori status santri yang ingin diunduh:</p>
            <div class="d-grid gap-2">
                <input type="submit" value="Santri Aktif" name="status[]" class="btn btn-success">
                <input type="submit" value="Santri Alumni" name="status[]" class="btn btn-secondary">
                <input type="submit" value="Semua Santri" name="status[]" class="btn btn-primary">
            </div>
        </form>
    </x-modal>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            $('#table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('santri.index') }}",
                columns: [
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'no_induk',
                        name: 'no_induk',
                        className: 'font-weight-bold'
                    },
                    {
                        data: 'user.name',
                        name: 'user.name',
                        render: function(data, type, row) {
                            var imgUrl = (row.foto === 'santri.png' || !row.foto)
                                ? '{{ url("img/santri.png") }}'
                                : '{{ url("storage/uploads/santri") }}/' + row.foto;
                            return `<div class="d-flex align-items-center gap-2">
                                <img src="${imgUrl}" alt="santri" class="rounded-circle border shadow-sm" width="38" height="38" style="object-fit: cover;">
                                <div>
                                    <div class="font-weight-bold text-dark">${data || '-'}</div>
                                </div>
                            </div>`;
                        }
                    },
                    {
                        data: 'jenis_kelamin',
                        name: 'jenis_kelamin',
                        render: function(data) {
                            if (data === 'Laki-Laki') {
                                return '<span class="badge bg-light-primary text-primary font-12"><i class="bx bx-male-sign"></i> Laki-Laki</span>';
                            } else if (data === 'Perempuan') {
                                return '<span class="badge bg-light-info text-info font-12"><i class="bx bx-female-sign"></i> Perempuan</span>';
                            }
                            return data || '-';
                        }
                    },
                    {
                        data: 'tahun_masuk',
                        name: 'tahun_masuk'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        render: function(data) {
                            if (data === 'Santri Aktif') {
                                return '<span class="badge bg-success font-12">Santri Aktif</span>';
                            } else if (data === 'Santri Alumni') {
                                return '<span class="badge bg-secondary font-12">Santri Alumni</span>';
                            }
                            return `<span class="badge bg-light text-dark border font-12">${data}</span>`;
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        });
    </script>
@endpush
