@extends('layouts.app')

@section('title', 'Pengguna | DIGITREN')

@section('content')
    <div>
        <!--page-wrapper-->
        <div class="page-wrapper">
            <!--page-content-wrapper-->
            <div class="page-content-wrapper">
                <div class="page-content">
                    <x-breadcrumb url="{{ route('dashboard') }}" path='Pengguna'></x-breadcrumb>
                    <div class="card">
                        <div class="card-body">
                            <x-card-toolbar title="Manajemen Pengguna">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#exampleModal">
                                    <i class="bx bx-plus"></i> Tambah Pengguna
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
                                                    <th>Nama Pengguna</th>
                                                    <th>Email</th>
                                                    <th>Hak Akses (Role)</th>
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
            <!--end page-content-wrapper-->
            <x-modal-form id='exampleModal' title='Tambah data pengguna' fn="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <x-input type='text' name='name' id="name" label='Nama Lengkap' placeholder='Nama Lengkap'
                        value="{{ old('name') }}"></x-input>
                </div>
                <div class="mb-3">
                    <x-input type='email' name='email' id="email" label='Email' placeholder='Email'
                        value="{{ old('email') }}"></x-input>
                </div>
                <div class="mb-3">
                    <x-input type='password' name='password' id="password" label='Password' placeholder='Password'
                        value="{{ old('password') }}"></x-input>
                </div>
                <div class="mb-3">
                    <x-input type='password' name='password_confirmation' id="password_confirmation"
                        label='Konfirmasi Password' placeholder='Konfirmasi Password'></x-input>
                </div>
                <div class="mb-3">
                    <x-select-option id="role_id" name="role_id" label="Jabatan">
                        <option value="" selected disabled>Pilih
                            jabatan</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}"
                                {{ old('role_id') != null ? (old('role_id') == $role->id ? 'selected' : '') : '' }}>
                                {{ $role->name }}</option>
                        @endforeach
                    </x-select-option>
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
                ajax: "{{ route('users.index') }}",
                columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                }, {
                    data: 'name',
                    name: 'name'
                }, {
                    data: 'email',
                    name: 'email'
                }, {
                    data: 'roles',
                    render: function(data) {
                        return data.map((item) => item.name)
                    },
                    name: 'roles'
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
