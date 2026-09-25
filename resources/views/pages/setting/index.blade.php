@extends('layouts.app')

@section('title', 'Pengaturan Aplikasi | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('setting.index') }}" path='Pengaturan Aplikasi'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <x-card-toolbar title="Pengaturan Identitas & Fitur Sistem"></x-card-toolbar>

                        @if (isset($setting))
                            <form action="{{ route('setting.update', $setting->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="card bg-light border p-3">
                                            <h6 class="font-weight-bold font-14 mb-3">Logo Aplikasi</h6>
                                            @if ($setting->logo)
                                                <div class="mb-3">
                                                    <img src="{{ $setting->logoUrl() }}" loading="lazy"
                                                        alt="Logo Aplikasi" class="img-fluid border rounded p-1" style="max-height: 80px;"
                                                        onerror="this.onerror=null;this.src='{{ asset('assets/images/logo-icon.png') }}';">
                                                </div>
                                            @endif
                                            <x-input type="file" id="logo" name="logo" label="Ganti Logo" accept=".png,.jpg,.jpeg,.webp" helper="Format: PNG, JPG, JPEG, WEBP (Maks 5MB)" />
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="card bg-light border p-3">
                                            <h6 class="font-weight-bold font-14 mb-3">Favicon Aplikasi</h6>
                                            @if ($setting->favicon)
                                                <div class="mb-3">
                                                    <img src="{{ $setting->faviconUrl() }}" loading="lazy"
                                                        alt="Favicon Aplikasi" class="img-fluid border rounded p-1" style="max-height: 48px;"
                                                        onerror="this.onerror=null;this.src='{{ asset('assets/images/favicon-32x32.png') }}';">
                                                </div>
                                            @endif
                                            <x-input type="file" id="favicon" name="favicon" label="Ganti Favicon" accept=".png,.jpg,.jpeg,.ico,.webp" helper="Format: PNG, JPG, JPEG, ICO, WEBP (Maks 5MB)" />
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="card bg-light border p-3">
                                            <h6 class="font-weight-bold font-14 mb-2">Audit & Log Aktivitas Pengguna</h6>
                                            <p class="text-muted font-13 mb-3">Mencatat seluruh aksi masuk (login), perubahan data, dan transaksi keuangan demi akuntabilitas sistem.</p>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="log_activity"
                                                    id="log_activity_active" value="1"
                                                    {{ $setting->log_activity ? 'checked' : '' }}>
                                                <label class="form-check-label font-weight-bold" for="log_activity_active">Aktif (Direkomendasikan)</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="log_activity"
                                                    id="log_activity_inactive" value="0"
                                                    {{ ! $setting->log_activity ? 'checked' : '' }}>
                                                <label class="form-check-label text-muted" for="log_activity_inactive">Tidak Aktif</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i> Simpan Pengaturan
                                    </button>
                                </div>
                            </form>
                        @else
                            <form action="{{ route('setting.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <x-input type="file" id="logo" name="logo" label="Logo Aplikasi" accept=".png,.jpg,.jpeg,.webp" helper="Format: PNG, JPG, JPEG, WEBP (Maks 5MB)" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-input type="file" id="favicon" name="favicon" label="Favicon Aplikasi" accept=".png,.jpg,.jpeg,.ico,.webp" helper="Format: PNG, JPG, JPEG, ICO, WEBP (Maks 5MB)" />
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i> Simpan
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
