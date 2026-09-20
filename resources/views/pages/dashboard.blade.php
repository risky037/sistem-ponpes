@extends('layouts.app')

@section('title', 'Dashboard | DIGITREN - Sistem Informasi Pondok Pesantren Fatimah Az-Zahra')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <!-- Welcome Banner -->
                <div class="card radius-15 border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #157347 0%, #0f5132 100%); color: #fff;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <h4 class="mb-1 text-white font-weight-bold">Selamat Datang di Sistem Informasi Pesantren</h4>
                                <p class="mb-0 text-white-50">Pondok Pesantren Fatimah Az-Zahra — Pusat Pengelolaan Data Akademik, Santri & Keuangan</p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-light text-dark px-3 py-2 font-13">
                                    <i class="bx bx-calendar align-middle me-1"></i> {{ \Illuminate\Support\Carbon::now()->translatedFormat('l, d F Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Core Statistics Grid -->
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-4">
                    <!-- Total Santri -->
                    <div class="col">
                        <div class="card radius-15 mb-0 shadow-sm border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-1 text-muted text-uppercase font-12 font-weight-bold">Total Terdaftar</p>
                                        <h3 class="mb-0 font-weight-bold text-dark">{{ number_format($total_santri) }}</h3>
                                        <small class="text-muted">Seluruh Santri Terdata</small>
                                    </div>
                                    <div class="widgets-icons ms-auto rounded-circle text-white" style="background-color: var(--pesantren-primary, #157347);">
                                        <i class='bx bx-group'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Santri Aktif -->
                    <div class="col">
                        <div class="card radius-15 mb-0 shadow-sm border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-1 text-muted text-uppercase font-12 font-weight-bold">Santri Aktif</p>
                                        <h3 class="mb-0 font-weight-bold text-success">{{ number_format($santri_aktif) }}</h3>
                                        <small class="text-muted">Mukim di Pesantren</small>
                                    </div>
                                    <div class="widgets-icons ms-auto rounded-circle bg-success text-white">
                                        <i class='bx bx-user-check'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Santri Alumni -->
                    <div class="col">
                        <div class="card radius-15 mb-0 shadow-sm border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-1 text-muted text-uppercase font-12 font-weight-bold">Santri Alumni</p>
                                        <h3 class="mb-0 font-weight-bold text-secondary">{{ number_format($santri_alumni) }}</h3>
                                        <small class="text-muted">Telah Boyong / Lulus</small>
                                    </div>
                                    <div class="widgets-icons ms-auto rounded-circle bg-secondary text-white">
                                        <i class='bx bx-user-pin'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Santri Putra Aktif -->
                    <div class="col">
                        <div class="card radius-15 mb-0 shadow-sm border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-1 text-muted text-uppercase font-12 font-weight-bold">Santri Aktif Putra</p>
                                        <h3 class="mb-0 font-weight-bold text-primary">{{ number_format($putra) }}</h3>
                                        <small class="text-muted">Asrama Putra</small>
                                    </div>
                                    <div class="widgets-icons ms-auto rounded-circle bg-primary text-white">
                                        <i class='bx bx-user'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Santri Putri Aktif -->
                    <div class="col">
                        <div class="card radius-15 mb-0 shadow-sm border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-1 text-muted text-uppercase font-12 font-weight-bold">Santri Aktif Putri</p>
                                        <h3 class="mb-0 font-weight-bold" style="color: #0d9488;">{{ number_format($putri) }}</h3>
                                        <small class="text-muted">Asrama Putri</small>
                                    </div>
                                    <div class="widgets-icons ms-auto rounded-circle text-white" style="background-color: #0d9488;">
                                        <i class='bx bx-user'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pengurus -->
                    <div class="col">
                        <div class="card radius-15 mb-0 shadow-sm border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-1 text-muted text-uppercase font-12 font-weight-bold">Pengurus</p>
                                        <h3 class="mb-0 font-weight-bold text-dark">{{ number_format($pengurus) }}</h3>
                                        <small class="text-muted">Staf & Dewan Asatidz</small>
                                    </div>
                                    <div class="widgets-icons ms-auto rounded-circle text-white" style="background-color: #b45309;">
                                        <i class='bx bx-badge-check'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Akses Cepat (Quick Actions) Section -->
                <div class="card radius-15 border shadow-sm">
                    <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                        <h6 class="mb-0 font-weight-bold text-dark"><i class="bx bx-grid-alt me-1 text-success"></i> Menu Pintasan & Akses Cepat</h6>
                    </div>
                    <div class="card-body">
                        <div class="row row-cols-2 row-cols-md-4 g-3">
                            <div class="col">
                                <a href="{{ route('santri.index') }}" class="card radius-15 border mb-0 text-decoration-none text-center p-3 h-100 hover-shadow">
                                    <div class="widgets-icons mx-auto rounded-circle bg-light-success text-success mb-2">
                                        <i class='bx bx-user-plus'></i>
                                    </div>
                                    <div class="font-weight-bold text-dark font-14">Data Santri</div>
                                    <small class="text-muted">Kelola profil santri</small>
                                </a>
                            </div>
                            <div class="col">
                                <a href="{{ route('academic-year.index') }}" class="card radius-15 border mb-0 text-decoration-none text-center p-3 h-100 hover-shadow">
                                    <div class="widgets-icons mx-auto rounded-circle bg-light-primary text-primary mb-2">
                                        <i class='bx bx-calendar'></i>
                                    </div>
                                    <div class="font-weight-bold text-dark font-14">Tahun Ajaran</div>
                                    <small class="text-muted">Semester & kalender</small>
                                </a>
                            </div>
                            <div class="col">
                                <a href="{{ route('kamar.index') }}" class="card radius-15 border mb-0 text-decoration-none text-center p-3 h-100 hover-shadow">
                                    <div class="widgets-icons mx-auto rounded-circle bg-light-info text-info mb-2">
                                        <i class='bx bx-home'></i>
                                    </div>
                                    <div class="font-weight-bold text-dark font-14">Data Kamar</div>
                                    <small class="text-muted">Kapasitas & asrama</small>
                                </a>
                            </div>
                            @hasanyrole('Administrator|Keuangan')
                            <div class="col">
                                <a href="{{ route('saldo_debit.index') }}" class="card radius-15 border mb-0 text-decoration-none text-center p-3 h-100 hover-shadow">
                                    <div class="widgets-icons mx-auto rounded-circle bg-light-warning text-warning mb-2">
                                        <i class='bx bx-wallet'></i>
                                    </div>
                                    <div class="font-weight-bold text-dark font-14">Tabungan Santri</div>
                                    <small class="text-muted">Mutasi & saldo</small>
                                </a>
                            </div>
                            @else
                            <div class="col">
                                <a href="{{ route('kelas.index') }}" class="card radius-15 border mb-0 text-decoration-none text-center p-3 h-100 hover-shadow">
                                    <div class="widgets-icons mx-auto rounded-circle bg-light-warning text-warning mb-2">
                                        <i class='bx bx-book'></i>
                                    </div>
                                    <div class="font-weight-bold text-dark font-14">Data Kelas</div>
                                    <small class="text-muted">Tingkatan & kelas</small>
                                </a>
                            </div>
                            @endhasanyrole
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
