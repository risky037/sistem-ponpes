@extends('layouts.app')

@section('title', 'Tahun Ajaran | DIGITREN')

@section('content')
    <div>
        <!--page-wrapper-->
        <div class="page-wrapper">
            <!--page-content-wrapper-->
            <div class="page-content-wrapper">
                <div class="page-content">
                    <x-breadcrumb url="{{ route('dashboard') }}" path='Tahun Ajaran'></x-breadcrumb>
                    <div class="card">
                        <div class="card-body">
                            <div id="invoice">
                                <div class="toolbar hidden-print">
                                    <div class="text-end">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#createModal">
                                            <i class="bx bx-plus"></i>
                                            Tambah Tahun Ajaran
                                        </button>
                                    </div>
                                    <hr />
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="table-responsive">
                                        <table class="table table-striped dataTable" style="width:100%" role="grid"
                                            id="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Tahun Ajaran</th>
                                                    <th>Semester</th>
                                                    <th>Tanggal Mulai</th>
                                                    <th>Tanggal Selesai</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
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
            <!--end page-content-wrapper-->
            <x-modal-form id='createModal' title='Tambah Tahun Ajaran' fn="{{ route('academic-year.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <x-input type='text' name='name' id="name" label='Tahun Ajaran'
                        placeholder='contoh: 2026/2027' attribute="required"></x-input>
                </div>
                <div class="mb-3">
                    <label class="form-label">Semester</label>
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
                    <label class="form-check-label" for="is_active">Tahun Ajaran Aktif</label>
                </div>
            </x-modal-form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            $('#table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('academic-year.index') }}",
                columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                }, {
                    data: 'name',
                    name: 'name'
                }, {
                    data: 'semester',
                    name: 'semester'
                }, {
                    data: 'start_date',
                    name: 'start_date'
                }, {
                    data: 'end_date',
                    name: 'end_date'
                }, {
                    data: 'is_active',
                    name: 'is_active'
                }, {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                }]
            });
        });
    </script>
@endpush
