@extends('layouts.app')

@section('title', 'Angkatan Santri | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Angkatan Santri'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <x-card-toolbar title="Data Angkatan Santri">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#createModal">
                                <i class="bx bx-plus"></i>
                                Tambah Angkatan
                            </button>
                        </x-card-toolbar>

                        <div class="row">
                            <div class="col">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered align-middle dataTable" style="width:100%" role="grid"
                                        id="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th>Nama Angkatan</th>
                                                <th>Tahun</th>
                                                <th>Jumlah Santri</th>
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

    <x-modal-form id='createModal' title='Tambah Angkatan Santri' fn="{{ route('student-batch.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <x-input type='text' name='name' id="name" label='Nama Angkatan'
                placeholder='contoh: Angkatan 2026' attribute="required"></x-input>
        </div>
        <div class="mb-3">
            <x-input type='number' name='year' id="year" label='Tahun Masuk'
                placeholder='contoh: 2026' attribute="required min=2000 max=2100"></x-input>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Keterangan</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Keterangan opsional"></textarea>
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
                ajax: "{{ route('student-batch.index') }}",
                columns: [
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'name',
                        name: 'name',
                        className: 'font-weight-bold'
                    },
                    {
                        data: 'year',
                        name: 'year'
                    },
                    {
                        data: 'santris_count',
                        name: 'santris_count',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'description',
                        name: 'description',
                        render: function(data) {
                            return data ? data : '-';
                        }
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
