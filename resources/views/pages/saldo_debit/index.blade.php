@extends('layouts.app')

@section('title', 'Tabungan Santri | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Tabungan Santri'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <x-card-toolbar title="Rekening & Saldo Tabungan">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#exampleModal">
                                <i class="bx bx-plus"></i>
                                Buka Rekening / Tambah Data
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
                                                <th>Saldo Tabungan</th>
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

    <x-modal-form title='Tambah Data Tabungan Santri' id='exampleModal'
        fn="{{ route('saldo_debit.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <x-select-option label="Pilih Santri" name="santri_id" id="santri">
                <option value="" selected disabled>Pilih Santri</option>
                <option value="semua">Semua Santri Aktif</option>
                @foreach ($santri as $str)
                    <option value="{{ $str->id }}">{{ $str->user->name }} ({{ $str->no_induk }})</option>
                @endforeach
            </x-select-option>
        </div>
        <div class="mb-3">
            <x-input type="number" name="saldo" id="saldo" label="Nominal Saldo Awal (Rp)"
                placeholder="Nominal Saldo" value="0"></x-input>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Keterangan</label>
            <textarea class="form-control" name="keterangan" id="keterangan" placeholder="Keterangan setoran awal / pembukaan rekening"></textarea>
        </div>
    </x-modal-form>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            $('#table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('saldo_debit.index') }}",
                columns: [
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'no_induk',
                        name: 'no_induk',
                        className: 'font-weight-bold'
                    },
                    {
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'saldo',
                        render: $.fn.dataTable.render.number(',', '.', 0, 'Rp '),
                        name: 'saldo',
                        className: 'font-weight-bold text-success'
                    },
                    {
                        data: 'keterangan',
                        name: 'keterangan'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                    }
                ]
            });
        });
    </script>
@endpush
