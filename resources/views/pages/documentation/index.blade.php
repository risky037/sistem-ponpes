@extends('layouts.app')

@section('title', 'Panduan Penggunaan Sistem | DIGITREN')

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
                                    <i class="bx bx-book-open me-1"></i> Dokumentasi & SOP Resmi
                                </span>
                                <h3 class="font-weight-bold text-white mb-2">Panduan Penggunaan Sistem DIGITREN</h3>
                                <p class="text-white-50 mb-4 font-14">
                                    Pusat petunjuk teknis operasional terpadu Pondok Pesantren Fatimah Az Zahra. Panduan disesuaikan dengan peran hak akses Anda:
                                    <strong class="text-white">{{ ucfirst($primaryRole) }}</strong>.
                                </p>
                                <div class="position-relative" style="max-width: 500px;">
                                    <input type="text" id="doc_search" class="form-control form-control-lg border-0 ps-5"
                                           placeholder="Cari topik panduan... (mis: presensi, setoran, reset)">
                                    <i class="bx bx-search position-absolute top-50 start-0 translate-middle-y ms-3 font-22 text-muted"></i>
                                </div>
                            </div>
                            <div class="col-lg-4 text-center d-none d-lg-block">
                                <i class="bx bx-support font-100 text-white-50 opacity-25"></i>
                            </div>
                        </div>
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
                                        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-primary">Buka Menu Pengguna</a>
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
                                        <a href="{{ route('academic-year.index') }}" class="btn btn-sm btn-outline-success">Buka Tahun Akademik</a>
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
                                        <a href="{{ route('academic.intelligence.index') }}" class="btn btn-sm btn-outline-info">Buka Intelligence Portal</a>
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
                                        <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-success">Buka Presensi Kelas</a>
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
                                        <a href="{{ route('assessment.score.index') }}" class="btn btn-sm btn-outline-warning">Buka Perekaman Asesmen</a>
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
                                        <a href="{{ route('transaksi.index', ['jenis_transaksi' => 'Setoran']) }}" class="btn btn-sm btn-outline-success">Buka Terminal Setoran</a>
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
                                        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-primary">Ke Beranda Santri</a>
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
            $('#doc_search').on('input', function() {
                var query = $(this).val().toLowerCase().trim();
                if (!query) {
                    $('.doc-item').show();
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
            });
        });
    </script>
@endpush
