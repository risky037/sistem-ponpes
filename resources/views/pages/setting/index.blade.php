@extends('layouts.app')

@section('title', 'Setting | DIGITREN')

@section('content')
    <div>
        <!--page-wrapper-->
        <div class="page-wrapper">
            <!--page-content-wrapper-->
            <div class="page-content-wrapper">
                <div class="page-content">
                    <x-breadcrumb url="{{ route('setting.index') }}" attribute="required" path='Setting'></x-breadcrumb>
                    <div class="card">
                        <div class="card-body">
                            @if (isset($setting))
                                <form action="{{ route('setting.update', $setting->id) }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    @method('patch')
                                    <div class="form-group mt-3">
                                        @isset($setting)
                                            <div class="row mb-2">
                                                <div class="col">
                                                    <img src="{{ url('/storage/uploads/setting/', $setting->logo) }}"
                                                        alt="logo" width="50" class="img-fluid">
                                                </div>
                                            </div>
                                        @endisset
                                        <x-input type="file" id="logo" name="logo" label="Logo" />
                                    </div>
                                    <div class="form-group mt-3">
                                        @isset($setting)
                                            <div class="row mb-2">
                                                <div class="col">
                                                    <img src="{{ url('/storage/uploads/setting/', $setting->favicon) }}"
                                                        alt="favicon" width="50" class="img-fluid">
                                                </div>
                                            </div>
                                        @endisset
                                        <x-input type="file" id="favicon" name="favicon" label="Favicon" />
                                    </div>
                                    <div class="form-group mt-3">
                                        <label for="Aktif">Fitur Rekam Aktifitas Pengguna</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="radio" name="log_activity[]"
                                                id="Aktif" value="1"
                                                {{ isset($setting) ? ($setting->log_activity == true ? 'checked' : '') : '' }}>
                                            <label class="form-check-label" for="Aktif">Aktif</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="log_activity[]"
                                                id="Tidak Aktif" value="0"
                                                {{ isset($setting) ? ($setting->log_activity == false ? 'checked' : '') : '' }}>
                                            <label class="form-check-label" for="Tidak Aktif">Tidak Aktif</label>
                                        </div>
                                    </div>
                                    <div class="form-group mt-2">
                                        <button class="btn btn-info">Update</button>
                                    </div>
                                </form>
                            @else
                                <form action="{{ route('setting.store') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group mt-3">
                                        <x-input type="file" id="logo" name="logo" label="Logo" />
                                    </div>
                                    <div class="form-group mt-3">
                                        <x-input type="file" id="favicon" name="favicon" label="Favicon" />
                                    </div>
                                    <div class="form-group mt-2">
                                        <button class="btn btn-primary">Submit</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
