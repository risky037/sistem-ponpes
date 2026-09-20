@extends('layouts.app')

@section('title', 'Profil Santri - ' . $item->user->name . ' | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('santri.index') }}" path='Profil Santri'></x-breadcrumb>

                <!-- Profile Header Banner Card -->
                <div class="card radius-15 border shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                            <div class="position-relative">
                                @if ($item->foto && $item->foto !== 'santri.png')
                                    <img src="{{ url('storage/uploads/santri/' . $item->foto) }}" alt="{{ $item->user->name }}"
                                        class="rounded-circle border shadow-sm" width="105" height="105" style="object-fit: cover;">
                                @else
                                    <img src="{{ url('img/santri.png') }}" alt="{{ $item->user->name }}"
                                        class="rounded-circle border shadow-sm" width="105" height="105" style="object-fit: cover;">
                                @endif
                            </div>
                            <div class="text-center text-md-start flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2 justify-content-center justify-content-md-start mb-1">
                                    <h4 class="mb-0 font-weight-bold text-dark">{{ $item->user->name }}</h4>
                                    <x-status-badge :status="$item->status" />
                                </div>
                                <div class="text-muted font-13 mb-2">
                                    <span class="me-3"><i class="bx bx-id-card align-middle me-1"></i> NIS: <strong>{{ $item->no_induk }}</strong></span>
                                    <span class="me-3">
                                        @if ($item->jenis_kelamin === 'Laki-Laki')
                                            <i class="bx bx-male-sign align-middle text-primary me-1"></i> Laki-Laki
                                        @else
                                            <i class="bx bx-female-sign align-middle text-danger me-1"></i> Perempuan
                                        @endif
                                    </span>
                                    @if ($item->student_batch)
                                        <span><i class="bx bx-calendar-star align-middle text-success me-1"></i> {{ $item->student_batch->name }}</span>
                                    @endif
                                </div>
                                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                                    @if ($item->whatsapp)
                                        <a href="{{ \App\Helpers\Whatsapp::url($item->whatsapp, 'Assalamualaikum ' . $item->user->name) }}"
                                            target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success">
                                            <i class="bx bxl-whatsapp me-1"></i> WhatsApp Santri
                                        </a>
                                    @endif
                                    @if ($item->whatsapp && isset($item->wali_santri))
                                        <a href="{{ \App\Helpers\Whatsapp::url($item->whatsapp, 'Assalamualaikum Bapak/Ibu Wali dari ' . $item->user->name) }}"
                                            target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success">
                                            <i class="bx bxl-whatsapp me-1"></i> Hubungi Wali
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex flex-column gap-2 text-center text-md-end">
                                <a href="{{ route('santri.edit', $item->id) }}" class="btn btn-primary btn-sm">
                                    <i class="bx bx-edit me-1"></i> Edit Data
                                </a>
                                @if (isset($item->wali_santri) && $item->foto !== 'santri.png')
                                    <a href="{{ route('santri.print.kts', $item->no_induk) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                        <i class="bx bx-printer me-1"></i> Cetak KTS
                                    </a>
                                @endif
                                <a href="{{ route('santri.index') }}" class="btn btn-light btn-sm border">
                                    <i class="bx bx-arrow-back me-1"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Tabs / Cards -->
                <div class="row g-4">
                    <!-- Left Column: Identity & Wali -->
                    <div class="col-12 col-lg-6">
                        <!-- Data Pribadi & Kependudukan -->
                        <div class="card radius-15 border shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom pt-3 pb-2">
                                <h6 class="mb-0 font-weight-bold text-dark">
                                    <i class="bx bx-user me-2 text-success"></i>Data Identitas & Kependudukan
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-borderless table-striped mb-0 font-14">
                                    <tbody>
                                        <tr>
                                            <th class="ps-3 text-muted" style="width: 40%;">Nomor Induk Santri</th>
                                            <td class="font-weight-bold">{{ $item->no_induk }}</td>
                                        </tr>
                                        <tr>
                                            <th class="ps-3 text-muted">Nomor Induk Kependudukan (NIK)</th>
                                            <td>{{ $item->nik ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="ps-3 text-muted">Nomor Kartu Keluarga (KK)</th>
                                            <td>{{ $item->kk ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="ps-3 text-muted">Tempat, Tanggal Lahir</th>
                                            <td>{{ $item->tempat_lahir }}, {{ \Illuminate\Support\Carbon::parse($item->tanggal_lahir)->translatedFormat('d F Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th class="ps-3 text-muted">Jenis Kelamin</th>
                                            <td>{{ $item->jenis_kelamin }}</td>
                                        </tr>
                                        <tr>
                                            <th class="ps-3 text-muted">Alamat Lengkap</th>
                                            <td>{{ $item->alamat_santri->alamat_lengkap ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="ps-3 text-muted">Akun Email</th>
                                            <td>{{ $item->user->email ?? '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Data Orang Tua / Wali -->
                        <div class="card radius-15 border shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom pt-3 pb-2">
                                <h6 class="mb-0 font-weight-bold text-dark">
                                    <i class="bx bx-group me-2 text-success"></i>Data Orang Tua & Wali
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-borderless table-striped mb-0 font-14">
                                    <tbody>
                                        <tr>
                                            <th class="ps-3 text-muted" style="width: 40%;">Nama Ayah</th>
                                            <td class="font-weight-bold">{{ $item->wali_santri->nama_ayah ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="ps-3 text-muted">Nama Ibu</th>
                                            <td class="font-weight-bold">{{ $item->wali_santri->nama_ibu ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="ps-3 text-muted">Nomor Kontak / WhatsApp</th>
                                            <td>
                                                {{ $item->whatsapp ?? '-' }}
                                                @if ($item->whatsapp)
                                                    <a href="{{ \App\Helpers\Whatsapp::url($item->whatsapp, 'Assalamualaikum') }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success ms-2 py-0 px-2 font-12">
                                                        <i class="bx bxl-whatsapp"></i> Chat
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Status Tabungan -->
                        <div class="card radius-15 border shadow-sm">
                            <div class="card-header bg-transparent border-bottom pt-3 pb-2 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 font-weight-bold text-dark">
                                    <i class="bx bx-wallet me-2 text-success"></i>Status Rekening Tabungan
                                </h6>
                                @if ($item->tabungan)
                                    <a href="{{ route('saldo_debit.history', $item->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2 font-12">
                                        <i class="bx bx-history"></i> Riwayat Mutasi
                                    </a>
                                @endif
                            </div>
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase font-weight-bold">Saldo Akhir</small>
                                        <h4 class="mb-0 font-weight-bold text-success mt-1">
                                            Rp {{ number_format($item->tabungan?->saldo ?? 0, 0, ',', '.') }}
                                        </h4>
                                    </div>
                                    <div>
                                        @if ($item->tabungan)
                                            <span class="badge bg-light-success text-success font-13 px-3 py-2">
                                                <i class="bx bx-check-circle me-1"></i> Rekening Aktif
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted font-13 px-3 py-2">
                                                Belum Terdaftar Tabungan
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Asrama, Kelas & Riwayat Akademik -->
                    <div class="col-12 col-lg-6">
                        <!-- Informasi Penempatan & Kepesantrenan -->
                        <div class="card radius-15 border shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom pt-3 pb-2">
                                <h6 class="mb-0 font-weight-bold text-dark">
                                    <i class="bx bx-buildings me-2 text-success"></i>Penempatan & Informasi Kepesantrenan
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-borderless table-striped mb-0 font-14">
                                    <tbody>
                                        <tr>
                                            <th class="ps-3 text-muted" style="width: 40%;">Status Santri</th>
                                            <td><x-status-badge :status="$item->status" /></td>
                                        </tr>
                                        <tr>
                                            <th class="ps-3 text-muted">Kamar / Asrama</th>
                                            <td class="font-weight-bold">
                                                @if (isset($item->kamar_santri) && $item->kamar_santri->kamar)
                                                    <span class="badge bg-light-info text-info font-13">
                                                        <i class="bx bx-home me-1"></i> {{ $item->kamar_santri->kamar->nama }} (Blok {{ $item->kamar_santri->kamar->blok }})
                                                    </span>
                                                @else
                                                    <span class="text-muted font-italic">Belum ditempatkan</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="ps-3 text-muted">Kelas Aktif</th>
                                            <td class="font-weight-bold">
                                                @if (isset($item->kelas_santri) && $item->kelas_santri->kelas)
                                                    <span class="badge bg-light-primary text-primary font-13">
                                                        <i class="bx bx-book-open me-1"></i> {{ $item->kelas_santri->kelas->tingkatan }} - {{ $item->kelas_santri->kelas->kelas }}
                                                    </span>
                                                @else
                                                    <span class="text-muted font-italic">Belum ditentukan</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="ps-3 text-muted">Angkatan Masuk</th>
                                            <td>{{ $item->student_batch->name ?? $item->tahun_masuk }}</td>
                                        </tr>
                                        <tr>
                                            <th class="ps-3 text-muted">Tanggal Masuk</th>
                                            <td>{{ $item->tahun_masuk }} ({{ $item->tahun_masuk_hijriyah }} H)</td>
                                        </tr>
                                        @if ($item->status === 'Santri Alumni')
                                            <tr>
                                                <th class="ps-3 text-muted">Tanggal Boyong</th>
                                                <td>{{ $item->tanggal_boyong ?? '-' }} ({{ $item->tanggal_boyong_hijriyah ?? '-' }} H)</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Riwayat Akademik (Academic Foundation) -->
                        <div class="card radius-15 border shadow-sm">
                            <div class="card-header bg-transparent border-bottom pt-3 pb-2">
                                <h6 class="mb-0 font-weight-bold text-dark">
                                    <i class="bx bx-book-bookmark me-2 text-success"></i>Riwayat Pendaftaran Akademik
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                @if ($item->academic_enrollments && $item->academic_enrollments->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped align-middle mb-0 font-13">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Tahun Ajaran</th>
                                                    <th>Semester</th>
                                                    <th>Kelas</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($item->academic_enrollments as $enrollment)
                                                    <tr>
                                                        <td class="font-weight-bold">{{ $enrollment->academicYear->name ?? '-' }}</td>
                                                        <td>
                                                            <x-status-badge :status="$enrollment->academicYear->semester ?? '-'" />
                                                        </td>
                                                        <td>
                                                            {{ isset($enrollment->kelas) ? $enrollment->kelas->tingkatan . ' - ' . $enrollment->kelas->kelas : '-' }}
                                                        </td>
                                                        <td>
                                                            <x-status-badge :status="$enrollment->status" />
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-4 px-3">
                                        <i class="bx bx-calendar-x text-muted font-35 mb-2"></i>
                                        <p class="text-muted font-13 mb-0">Belum ada riwayat pendaftaran akademik tercatat untuk santri ini.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
