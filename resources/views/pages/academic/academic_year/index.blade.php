@extends('layouts.app')

@section('title', 'Tahun Ajaran | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Tahun Ajaran'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <x-card-toolbar title="Kalender & Tahun Ajaran">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#createModal">
                                <i class="bx bx-plus"></i>
                                Tambah Tahun Ajaran
                            </button>
                        </x-card-toolbar>

                        <div class="row">
                            <div class="col">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle dataTable" style="width:100%" role="grid"
                                        id="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th>Tahun Ajaran</th>
                                                <th>Semester</th>
                                                <th>Tanggal Mulai</th>
                                                <th>Tanggal Selesai</th>
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

    <x-modal-form id='createModal' title='Tambah Tahun Ajaran' fn="{{ route('academic-year.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <x-input type='text' name='name' id="name" label='Tahun Ajaran'
                placeholder='contoh: 2026/2027' attribute="required"></x-input>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Semester</label>
            <select name="semester" class="form-select" required>
                <option value="Ganjil" selected>Ganjil</option>
                <option value="Genap">Genap</option>
            </select>
        </div>
        <div class="mb-3">
            <x-input type='date' name='start_date' id="start_date" label='Tanggal Mulai'
                attribute="required"></x-input>
        </div>
        <div class="mb-3">
            <x-input type='date' name='end_date' id="end_date" label='Tanggal Selesai'
                attribute="required"></x-input>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1">
            <label class="form-check-label font-weight-bold" for="is_active">Tahun Ajaran Aktif</label>
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
                ajax: "{{ route('academic-year.index') }}",
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
                        data: 'semester',
                        name: 'semester',
                        render: function(data) {
                            if (data === 'Ganjil') {
                                return '<span class="badge bg-primary">Ganjil</span>';
                            } else if (data === 'Genap') {
                                return '<span class="badge bg-info text-dark">Genap</span>';
                            }
                            return data || '-';
                        }
                    },
                    {
                        data: 'start_date',
                        name: 'start_date',
                        render: $.fn.dataTable.render.indonesianDate()
                    },
                    {
                        data: 'end_date',
                        name: 'end_date',
                        render: $.fn.dataTable.render.indonesianDate()
                    },
                    {
                        data: 'is_active',
                        name: 'is_active'
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
