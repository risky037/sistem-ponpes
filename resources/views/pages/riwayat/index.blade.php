@extends('layouts.app')

@section('title', 'Riwayat | DIGITREN')

@section('content')
    <div>
        <!--page-wrapper-->
        <div class="page-wrapper">
            <!--page-content-wrapper-->
            <div class="page-content-wrapper">
                <div class="page-content">
                    <x-breadcrumb url="{{ route('dashboard') }}" attribute="required" path='Riwayat'></x-breadcrumb>
                    <div class="card">
                        <div class="card-body">
                            <x-card-toolbar title="Log Aktivitas Sistem"></x-card-toolbar>
                            <div class="row mb-2">
                                <div class="col">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-bordered align-middle dataTable" style="width:100%" role="grid"
                                            id="table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 5%">#</th>
                                                    <th>Pengguna</th>
                                                    <th>Aktivitas</th>
                                                    <th>Waktu Kejadian</th>
                                                    <th>Pembaruan</th>
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
    </div>
@endsection
@push('js')
    <script>
        $(document).ready(function() {
            //Default data table
            $('#table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('riwayat.index') }}",
                columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                }, {
                    data: 'user',
                    name: 'user'
                }, {
                    data: 'activity',
                    name: 'activity'
                }, {
                    data: 'created_at',
                    render: function(data) {
                        return moment(data).format('DD MMMM YYYY');
                    },
                    name: 'created_at',
                }, {
                    data: 'updated_at',
                    render: function(data) {
                        return moment(data).format('DD MMMM YYYY');
                    },
                    name: 'updated_at',
                }]
            });
        });
    </script>
@endpush
