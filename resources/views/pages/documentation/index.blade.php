@extends('layouts.app')

@section('title', 'Pusat Dokumentasi & Panduan Sistem | DIGITREN')

@push('css')
<style>
    .search-results-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1050;
        max-height: 380px;
        overflow-y: auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        border: 1px solid #e2e8f0;
    }
    .search-result-item {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: background 0.15s ease;
        text-decoration: none;
        display: block;
    }
    .search-result-item:hover {
        background: #f8fafc;
    }
    .doc-category-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .doc-category-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08) !important;
    }
</style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" attribute="required" path='Panduan Sistem'></x-breadcrumb>

                {{-- HERO HEADER --}}
                <div class="card radius-15 border-0 bg-primary text-white shadow-sm mb-4 overflow-hidden position-relative">
                    <div class="card-body p-4 p-md-5">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <span class="badge bg-light text-primary px-3 py-1 mb-2 font-12 font-weight-bold">
                                    <i class="bx bx-book-open me-1"></i> Dokumentasi & SOP Resmi DIGITREN
                                </span>
                                <h3 class="font-weight-bold text-white mb-2">Panduan Penggunaan Sistem DIGITREN</h3>
                                <p class="text-white-50 mb-4 font-14">
                                    Pusat petunjuk teknis operasional terpadu Pondok Pesantren Fatimah Az Zahra. Panduan disesuaikan dengan peran hak akses Anda:
                                    <strong class="text-white">{{ ucfirst($primaryRole) }}</strong>.
                                </p>
                                <div class="position-relative" style="max-width: 550px;">
                                    <input type="text" id="doc_search" class="form-control form-control-lg border-0 ps-5"
                                           placeholder="Cari topik panduan... (mis: presensi, setoran, reset, lms, skenario)">
                                    <i class="bx bx-search position-absolute top-50 start-0 translate-middle-y ms-3 font-22 text-muted"></i>
                                    <div id="search_results_container" class="search-results-dropdown d-none text-dark"></div>
                                </div>
                            </div>
                            <div class="col-lg-4 text-center d-none d-lg-block">
                                <i class="bx bx-book-reader font-100 text-white-50 opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- KPI METRICS STRIP --}}
                <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
                    <div class="col">
                        <div class="card radius-15 border shadow-sm mb-0">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="widgets-icons rounded-circle bg-light-primary text-primary me-3">
                                        <i class="bx bx-file"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted text-uppercase font-11 fw-bold">Total Panduan Tersedia</small>
                                        <h5 class="mb-0 fw-bold text-primary">{{ $totalArticles }} Dokumen Resmi</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card radius-15 border shadow-sm mb-0">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="widgets-icons rounded-circle bg-light-success text-success me-3">
                                        <i class="bx bx-category"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted text-uppercase font-11 fw-bold">Kategori Panduan</small>
                                        <h5 class="mb-0 fw-bold text-success">{{ $totalCategories }} Modul Utama</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card radius-15 border shadow-sm mb-0">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="widgets-icons rounded-circle bg-light-info text-info me-3">
                                        <i class="bx bx-shield-quarter"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted text-uppercase font-11 fw-bold">Akses Hak Peran</small>
                                        <h5 class="mb-0 fw-bold text-info">{{ ucfirst($primaryRole) }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ONBOARDING SECTION: MULAI MENGGUNAKAN DIGITREN --}}
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bx bx-compass me-1 text-primary"></i> Mulai Menggunakan DIGITREN</h5>
                            <p class="text-muted font-13 mb-0">Panduan orientasi kilat langkah awal bagi seluruh pengguna baru di lingkungan pesantren.</p>
                        </div>
                        <div>
                            <a href="{{ route('documentation.show', ['category' => 'user-guide', 'slug' => 'start-here']) }}" class="btn btn-sm btn-outline-primary radius-8">
                                <i class="bx bx-map-pin me-1"></i> Mulai Dari Sini (Alur Peta)
                            </a>
                        </div>
                    </div>

                    <div class="row row-cols-1 row-cols-md-3 g-3">
                        {{-- Card 1: Mulai Dari Awal --}}
                        <div class="col">
                            <div class="card radius-15 border shadow-sm h-100 doc-category-card">
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-3 font-24">
                                                🚀
                                            </div>
                                            <h6 class="fw-bold mb-0">Mulai Dari Awal</h6>
                                        </div>
                                        <p class="text-muted font-13 mb-3">
                                            Untuk administrator yang baru menggunakan sistem dari kondisi awal.
                                        </p>
                                    </div>
                                    <div>
                                        <a href="{{ route('documentation.show', ['category' => 'onboarding', 'slug' => 'administrator-first-setup']) }}" class="btn btn-primary btn-sm radius-8 w-100 fw-bold">
                                            <i class="bx bx-play-circle me-1"></i> Mulai Setup
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Card 2: Panduan Guru --}}
                        <div class="col">
                            <div class="card radius-15 border shadow-sm h-100 doc-category-card">
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success me-3 font-24">
                                                👨‍🏫
                                            </div>
                                            <h6 class="fw-bold mb-0">Panduan Guru</h6>
                                        </div>
                                        <p class="text-muted font-13 mb-3">
                                            Untuk ustadz/ustadzah pengampu kegiatan belajar mengajar di kelas.
                                        </p>
                                    </div>
                                    <div>
                                        <a href="{{ route('documentation.show', ['category' => 'onboarding', 'slug' => 'guru-first-use']) }}" class="btn btn-success btn-sm radius-8 w-100 fw-bold">
                                            <i class="bx bx-book-reader me-1"></i> Buka Panduan Guru
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Card 3: Panduan Santri --}}
                        <div class="col">
                            <div class="card radius-15 border shadow-sm h-100 doc-category-card">
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="widgets-icons-2 rounded-circle bg-light-info text-info me-3 font-24">
                                                🎓
                                            </div>
                                            <h6 class="fw-bold mb-0">Panduan Santri</h6>
                                        </div>
                                        <p class="text-muted font-13 mb-3">
                                            Untuk santri pengguna aplikasi dan wali santri pembina.
                                        </p>
                                    </div>
                                    <div>
                                        <a href="{{ route('documentation.show', ['category' => 'onboarding', 'slug' => 'santri-first-use']) }}" class="btn btn-info text-white btn-sm radius-8 w-100 fw-bold">
                                            <i class="bx bx-user me-1"></i> Buka Panduan Santri
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 1: JELAJAHI PANDUAN RESMI (MARKDOWN REPOSITORY) --}}
                <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="bx bx-grid-alt me-1 text-primary"></i> Pustaka Panduan Lengkap</h5>
                        <p class="text-muted font-13 mb-0">Pilih modul panduan berbasis dokumen markdown resmi pesantren untuk membaca petunjuk lengkap.</p>
                    </div>
                </div>

                <div class="row g-3 mb-5">
                    @forelse ($menu as $catKey => $category)
                        <div class="col-12 col-md-6 col-xl-4" id="cat-{{ $catKey }}">
                            <div class="card radius-15 border shadow-sm h-100 doc-category-card">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-2 font-20">
                                                <i class="{{ $category['icon'] }}"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bold font-15">{{ $category['name'] }}</h6>
                                        </div>
                                        <span class="badge bg-light text-primary rounded-pill font-11 fw-bold">
                                            {{ count($category['docs']) }} Bab
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body p-3">
                                    <p class="text-muted font-13 mb-3">{{ $category['description'] }}</p>
                                    <div class="list-group list-group-flush">
                                        @foreach ($category['docs'] as $doc)
                                            <a href="{{ $doc['url'] }}" class="list-group-item list-group-item-action border-0 px-2 py-2 d-flex align-items-center justify-content-between font-13 text-secondary hover-primary radius-8 mb-1">
                                                <div class="text-truncate me-2">
                                                    <i class="bx bx-chevron-right text-muted me-1"></i>
                                                    <span>{{ $doc['title'] }}</span>
                                                </div>
                                                <span class="badge bg-light-secondary text-muted font-10">Buka</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="card radius-15 border shadow-sm p-4 text-center">
                                <p class="text-muted mb-0">Tidak ada modul panduan yang diizinkan untuk peran Anda saat ini.</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- SECTION 2: SOP & RINGKASAN CEPAT BERBASIS ROLE --}}
                <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="bx bx-bolt-circle me-1 text-warning"></i> Petunjuk Singkat Cepat Peran</h5>
                        <p class="text-muted font-13 mb-0">Akses cepat langkah kerja operasional harian sesuai peran Anda.</p>
                    </div>
                </div>

                {{-- ROLE NAV PILLS --}}
                <div class="card radius-15 border shadow-sm mb-4">
                    <div class="card-body p-2">
                        <ul class="nav nav-pills nav-fill gap-2" id="docPillsTab" role="tablist">
                            @hasrole('Administrator')
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeTab === 'administrator' ? 'active' : '' }} font-weight-bold"
                                        id="pills-admin-tab" data-bs-toggle="pill" data-bs-target="#pills-admin" type="button" role="tab">
                                    <i class="bx bx-shield-quarter me-1"></i> Administrator
                                </button>
                            </li>
                            @endhasrole

                            @hasanyrole('Administrator|Pengurus')
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeTab === 'pengurus' ? 'active' : '' }} font-weight-bold"
                                        id="pills-pengurus-tab" data-bs-toggle="pill" data-bs-target="#pills-pengurus" type="button" role="tab">
                                    <i class="bx bx-briefcase-alt me-1"></i> Pengurus
                                </button>
                            </li>
                            @endhasanyrole

                            @hasanyrole('Administrator|Pengurus|Guru')
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeTab === 'guru' ? 'active' : '' }} font-weight-bold"
                                        id="pills-guru-tab" data-bs-toggle="pill" data-bs-target="#pills-guru" type="button" role="tab">
                                    <i class="bx bx-book-reader me-1"></i> Guru / Asatidz
                                </button>
                            </li>
                            @endhasanyrole

                            @hasanyrole('Administrator|Keuangan')
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeTab === 'keuangan' ? 'active' : '' }} font-weight-bold"
                                        id="pills-keuangan-tab" data-bs-toggle="pill" data-bs-target="#pills-keuangan" type="button" role="tab">
                                    <i class="bx bx-wallet me-1"></i> Keuangan
                                </button>
                            </li>
                            @endhasanyrole

                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeTab === 'santri' ? 'active' : '' }} font-weight-bold"
                                        id="pills-santri-tab" data-bs-toggle="pill" data-bs-target="#pills-santri" type="button" role="tab">
                                    <i class="bx bx-user me-1"></i> Santri & Wali
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- DOCUMENTATION CONTENT TABS --}}
                <div class="tab-content" id="docPillsTabContent">

                    {{-- TAB 1: ADMINISTRATOR --}}
                    @hasrole('Administrator')
                    <div class="tab-pane fade {{ $activeTab === 'administrator' ? 'show active' : '' }}" id="pills-admin" role="tabpanel">
                        <div class="row g-4 doc-section">
                            <div class="col-md-6 doc-item">
                                <div class="card radius-10 border shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-3">
                                                <i class="bx bx-user-plus"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold mb-0">Manajemen Pengguna & Role</h5>
                                        </div>
                                        <p class="text-secondary small">
                                            Pengelolaan akun asatidz, pengurus, staf keuangan, dan administrator.
                                        </p>
                                        <ol class="small text-secondary ps-3 mb-3">
                                            <li>Buka menu <strong>Users</strong> untuk menambah atau mengedit akun.</li>
                                            <li>Tentukan peran pengguna: <code>Administrator</code>, <code>Pengurus</code>, <code>Guru</code>, <code>Keuangan</code>, atau <code>Santri</code>.</li>
                                            <li>Setiap akun dapat direset kata sandinya oleh Admin melalui tombol <strong>Reset Password</strong> di tabel pengguna.</li>
                                            <li>Aturan keamanan: Akun Administrator tidak dapat menghapus akunnya sendiri dan akun admin terakhir dilindungi oleh sistem.</li>
                                        </ol>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-primary">Buka Menu Pengguna</a>
                                            <a href="{{ route('documentation.show', ['category' => 'user-guide', 'slug' => 'role-dan-hak-akses']) }}" class="btn btn-sm btn-primary">
                                                <i class="bx bx-book-open me-1"></i> Baca Panduan Lengkap
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 doc-item">
                                <div class="card radius-10 border shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success me-3">
                                                <i class="bx bx-calendar-event"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold mb-0">Tahun Akademik & Kalender</h5>
                                        </div>
                                        <p class="text-secondary small">
                                            Pengaturan periode semester aktif madrasah dan agenda kegiatan pondok.
                                        </p>
                                        <ol class="small text-secondary ps-3 mb-3">
                                            <li>Buka menu <strong>Tahun Akademik</strong> untuk menetapkan semester aktif (Ganjil/Genap).</li>
                                            <li>Hanya satu tahun akademik yang berstatus aktif dalam satu waktu.</li>
                                            <li>Jadwalkan agenda di <strong>Kalender Akademik</strong> untuk sinkronisasi jadwal libur, ujian, dan kegiatan pondok.</li>
                                        </ol>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('academic-year.index') }}" class="btn btn-sm btn-outline-success">Buka Tahun Akademik</a>
                                            <a href="{{ route('documentation.show', ['category' => 'user-guide', 'slug' => 'academic-setup-workflow']) }}" class="btn btn-sm btn-success">
                                                <i class="bx bx-book-open me-1"></i> Baca Panduan Setup
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endhasrole

                    {{-- TAB 2: PENGURUS --}}
                    @hasanyrole('Administrator|Pengurus')
                    <div class="tab-pane fade {{ $activeTab === 'pengurus' ? 'show active' : '' }}" id="pills-pengurus" role="tabpanel">
                        <div class="row g-4 doc-section">
                            <div class="col-md-6 doc-item">
                                <div class="card radius-10 border shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="widgets-icons-2 rounded-circle bg-light-info text-info me-3">
                                                <i class="bx bx-line-chart"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold mb-0">Academic Intelligence & Monitoring</h5>
                                        </div>
                                        <p class="text-secondary small">
                                            Pemantauan tren kehadiran santri, deteksi santri berisiko, dan ringkasan distribusi kelas.
                                        </p>
                                        <ul class="small text-secondary ps-3 mb-3">
                                            <li>Menu <strong>Intelligence</strong> menyajikan KPI rata-rata kehadiran seluruh tingkatan kelas.</li>
                                            <li>Daftar santri dengan kehadiran di bawah ambang batas (75%) disorot secara otomatis untuk tindakan wali kelas.</li>
                                            <li>Gunakan filter semester untuk mengomparasi performa antar periode akademik.</li>
                                        </ul>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('academic.intelligence.index') }}" class="btn btn-sm btn-outline-info">Buka Intelligence Portal</a>
                                            <a href="{{ route('documentation.show', ['category' => 'user-guide', 'slug' => 'intelligence-dashboard']) }}" class="btn btn-sm btn-info text-white">
                                                <i class="bx bx-book-open me-1"></i> Panduan Dasbor
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 doc-item">
                                <div class="card radius-10 border shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-3">
                                                <i class="bx bx-export"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold mb-0">Ekspor Laporan & Administrasi</h5>
                                        </div>
                                        <p class="text-secondary small">
                                            Penerbitan rekapitulasi data santri, absensi, dan nilai ke format spreadsheet resmi.
                                        </p>
                                        <ul class="small text-secondary ps-3 mb-3">
                                            <li>Akses menu <strong>Administrasi & Ekspor</strong> untuk unduhan data batch.</li>
                                            <li>Format ekspor kompatibel dengan Microsoft Excel dan Google Sheets.</li>
                                            <li>Semua tanggal terformat dalam format standar Indonesia (DD MMMM YYYY).</li>
                                        </ul>
                                        <a href="{{ route('academic.export.index') }}" class="btn btn-sm btn-outline-primary">Buka Pusat Ekspor</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endhasanyrole

                    {{-- TAB 3: GURU / ASATIDZ --}}
                    @hasanyrole('Administrator|Pengurus|Guru')
                    <div class="tab-pane fade {{ $activeTab === 'guru' ? 'show active' : '' }}" id="pills-guru" role="tabpanel">
                        <div class="row g-4 doc-section">
                            <div class="col-md-6 doc-item">
                                <div class="card radius-10 border shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success me-3">
                                                <i class="bx bx-check-square"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold mb-0">Input Presensi Kehadiran Santri</h5>
                                        </div>
                                        <p class="text-secondary small">
                                            Prosedur pencatatan absensi kelas dan halaqah pengajian:
                                        </p>
                                        <ol class="small text-secondary ps-3 mb-3">
                                            <li>Buka menu <strong>Presensi</strong> lalu pilih sesi mengajar hari ini.</li>
                                            <li>Status kehadiran: <code>Hadir (H)</code>, <code>Sakit (S)</code>, <code>Izin (I)</code>, <code>Alpa (A)</code>.</li>
                                            <li>Gunakan tombol <strong>Tandai Semua Hadir</strong> untuk mempercepat input kelas.</li>
                                            <li>Klik <strong>Simpan Presensi</strong> untuk menyimpan catatan sesi ke server.</li>
                                        </ol>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-success">Buka Presensi Kelas</a>
                                            <a href="{{ route('documentation.show', ['category' => 'user-guide', 'slug' => 'guru-workflow']) }}" class="btn btn-sm btn-success">
                                                <i class="bx bx-book-open me-1"></i> Panduan Guru Lengkap
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 doc-item">
                                <div class="card radius-10 border shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning me-3">
                                                <i class="bx bx-award"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold mb-0">Perekaman Capaian Asesmen</h5>
                                        </div>
                                        <p class="text-secondary small">
                                            Tata cara memasukkan nilai harian, ujian lisan, dan ujian semester:
                                        </p>
                                        <ol class="small text-secondary ps-3 mb-3">
                                            <li>Buka menu <strong>Asesmen</strong> dan pilih mata pelajaran yang Anda ampu.</li>
                                            <li>Tentukan jenis asesmen: <code>Formatif</code> atau <code>Sumatif</code>.</li>
                                            <li>Masukkan nilai santri (rentang 0 - 100).</li>
                                            <li>Nilai tersimpan akan dapat dipantau oleh santri bersangkutan secara transparan (read-only).</li>
                                        </ol>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('assessment.score.index') }}" class="btn btn-sm btn-outline-warning">Buka Perekaman Asesmen</a>
                                            <a href="{{ route('documentation.show', ['category' => 'user-guide', 'slug' => 'penilaian-workflow']) }}" class="btn btn-sm btn-warning text-dark">
                                                <i class="bx bx-book-open me-1"></i> Panduan Penilaian
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endhasanyrole

                    {{-- TAB 4: KEUANGAN --}}
                    @hasanyrole('Administrator|Keuangan')
                    <div class="tab-pane fade {{ $activeTab === 'keuangan' ? 'show active' : '' }}" id="pills-keuangan" role="tabpanel">
                        <div class="row g-4 doc-section">
                            <div class="col-md-6 doc-item">
                                <div class="card radius-10 border shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success me-3">
                                                <i class="bx bx-down-arrow-circle"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold mb-0">SOP Setoran Kas Tabungan</h5>
                                        </div>
                                        <p class="text-secondary small">
                                            Penerimaan titipan uang tabungan santri dari wali santri:
                                        </p>
                                        <ul class="small text-secondary ps-3 mb-3">
                                            <li>Ketik 8-digit NIS santri atau pilih nama santri dari dropdown.</li>
                                            <li>Periksa kesesuaian identitas santri pada kartu verifikasi sebelah kanan.</li>
                                            <li><strong>Batas Minimum:</strong> Rp 50.000 per transaksi.</li>
                                            <li>Gunakan tombol nominal cepat (<code>+50rb</code>, <code>+100rb</code>, <code>+200rb</code>) untuk efisiensi kasir.</li>
                                            <li>Klik <strong>Simpan Setoran</strong> dan pastikan saldo terkini bertambah secara langsung.</li>
                                        </ul>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('transaksi.index', ['jenis_transaksi' => 'Setoran']) }}" class="btn btn-sm btn-outline-success">Buka Terminal Setoran</a>
                                            <a href="{{ route('documentation.show', ['category' => 'user-guide', 'slug' => 'keuangan-workflow']) }}" class="btn btn-sm btn-success">
                                                <i class="bx bx-book-open me-1"></i> Panduan Keuangan
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 doc-item">
                                <div class="card radius-10 border shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="widgets-icons-2 rounded-circle bg-light-danger text-danger me-3">
                                                <i class="bx bx-up-arrow-circle"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold mb-0">SOP Penarikan Kas Harian</h5>
                                        </div>
                                        <p class="text-secondary small">
                                            Pemberian uang jajan atau keperluan belanja santri:
                                        </p>
                                        <ul class="small text-secondary ps-3 mb-3">
                                            <li><strong>Batas Minimum:</strong> Rp 10.000 per transaksi.</li>
                                            <li><strong>Frekuensi Maksimal:</strong> 1 kali per hari untuk setiap santri (aturan pondok pesantren).</li>
                                            <li>Sistem otomatis menolak dan menonaktifkan tombol simpan bila santri telah menarik saldo pada hari yang sama.</li>
                                            <li>Isi catatan keperluan (contoh: Uang Jajan, Beli Kitab, Periksa Kesehatan).</li>
                                            <li>Transaksi diamankan dengan database atomic locks untuk mencegah saldo minus.</li>
                                        </ul>
                                        <a href="{{ route('transaksi.index', ['jenis_transaksi' => 'Penarikan']) }}" class="btn btn-sm btn-outline-danger">Buka Terminal Penarikan</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endhasanyrole

                    {{-- TAB 5: SANTRI & WALI --}}
                    <div class="tab-pane fade {{ $activeTab === 'santri' ? 'show active' : '' }}" id="pills-santri" role="tabpanel">
                        <div class="row g-4 doc-section">
                            <div class="col-md-6 doc-item">
                                <div class="card radius-10 border shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-3">
                                                <i class="bx bx-desktop"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold mb-0">Fitur Portal Santri Mandiri</h5>
                                        </div>
                                        <p class="text-secondary small">
                                            Portal pribadi santri dirancang sebagai media transparansi informasi (read-only):
                                        </p>
                                        <ul class="small text-secondary ps-3 mb-3">
                                            <li><strong>Biodata Santri:</strong> Memeriksa kebenaran NIS, nama, kamar, kelas, dan kontak wali.</li>
                                            <li><strong>Rekap Kehadiran:</strong> Memantau persentase hadir, sakit, izin, dan alpa pada semester aktif.</li>
                                            <li><strong>Capaian Asesmen:</strong> Mengetahui nilai tugas, ujian lisan, dan evaluasi pengajar tanpa ranking kompetitif.</li>
                                            <li><strong>Catatan Tabungan:</strong> Memantau saldo akhir dan riwayat transaksi setoran/penarikan.</li>
                                        </ul>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-primary">Ke Beranda Santri</a>
                                            <a href="{{ route('documentation.show', ['category' => 'user-guide', 'slug' => 'santri-workflow']) }}" class="btn btn-sm btn-primary">
                                                <i class="bx bx-book-open me-1"></i> Panduan Santri Lengkap
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 doc-item">
                                <div class="card radius-10 border shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success me-3">
                                                <i class="bx bxl-whatsapp"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold mb-0">Bantuan Sandi & Layanan WhatsApp</h5>
                                        </div>
                                        <p class="text-secondary small">
                                            Saluran komunikasi resmi sekretariat pondok pesantren:
                                        </p>
                                        <ul class="small text-secondary ps-3 mb-3">
                                            <li>Bila Anda lupa kata sandi akun, gunakan fitur <strong>Lupa Password</strong> di halaman masuk.</li>
                                            <li>Sistem menyediakan tombol otomatis untuk mengirim format permohonan reset via WhatsApp ke nomor Admin Pesantren.</li>
                                            <li>Waktu respon sekretariat madrasah: Senin - Ahad (08.00 - 16.00 WIB).</li>
                                        </ul>
                                        <a href="https://wa.me/{{ config('pesantren.admin_whatsapp', '6281234567890') }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success">
                                            <i class="bx bxl-whatsapp me-1"></i> Kontak Admin Pesantren
                                        </a>
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
            var searchIndexData = null;
            var searchTimeout = null;

            // Load search index JSON in background
            $.getJSON("{{ route('documentation.search-index') }}", function(data) {
                searchIndexData = data;
            });

            $('#doc_search').on('input', function() {
                var query = $(this).val().toLowerCase().trim();
                var resultsContainer = $('#search_results_container');

                // 1. Filter local doc-item on current page
                if (!query) {
                    $('.doc-item').show();
                    resultsContainer.addClass('d-none').empty();
                    return;
                }

                $('.doc-item').each(function() {
                    var text = $(this).text().toLowerCase();
                    if (text.indexOf(query) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });

                // 2. Debounced search across full markdown knowledge base
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    if (!searchIndexData || query.length < 2) {
                        resultsContainer.addClass('d-none').empty();
                        return;
                    }

                    var matches = searchIndexData.filter(function(item) {
                        var inTitle = item.title.toLowerCase().indexOf(query) !== -1;
                        var inKeywords = item.keywords.toLowerCase().indexOf(query) !== -1;
                        var inExcerpt = item.excerpt.toLowerCase().indexOf(query) !== -1;
                        return inTitle || inKeywords || inExcerpt;
                    });

                    resultsContainer.empty();

                    if (matches.length === 0) {
                        resultsContainer.html('<div class="p-3 text-muted font-13 text-center"><i class="bx bx-info-circle me-1"></i> Tidak ditemukan panduan untuk kata kunci tersebut.</div>').removeClass('d-none');
                        return;
                    }

                    var html = '<div class="p-2 bg-light border-bottom font-11 text-uppercase fw-bold text-muted d-flex justify-content-between">' +
                               '<span>Hasil Pencarian Dokumen (' + matches.length + ')</span>' +
                               '<span class="text-primary">Klik untuk membaca</span>' +
                               '</div>';

                    matches.slice(0, 7).forEach(function(item) {
                        html += '<a href="' + item.url + '" class="search-result-item">' +
                                '<div class="d-flex align-items-center justify-content-between mb-1">' +
                                '<span class="fw-bold font-13 text-primary">' + item.title + '</span>' +
                                '<span class="badge bg-light text-secondary font-10">' + item.category_name + '</span>' +
                                '</div>' +
                                '<p class="mb-0 font-12 text-muted text-truncate">' + item.excerpt + '</p>' +
                                '</a>';
                    });

                    resultsContainer.html(html).removeClass('d-none');
                }, 200);
            });

            // Close search results dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#doc_search, #search_results_container').length) {
                    $('#search_results_container').addClass('d-none');
                }
            });
        });
    </script>
@endpush
