@extends('layouts.app')

@section('title', 'Terminal Transaksi Tabungan | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" attribute="required" path='Transaksi Tabungan'></x-breadcrumb>

                {{-- TOP KPI SUMMARY CARDS --}}
                <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
                    <div class="col">
                        <div class="card radius-10 border-start border-0 border-3 border-success mb-0 shadow-sm">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="mb-1 text-muted small text-uppercase font-weight-bold">Setoran Hari Ini</p>
                                        <h5 class="mb-0 text-success font-weight-bold">
                                            Rp {{ number_format($totalSetoranHariIni ?? 0, 0, ',', '.') }}
                                        </h5>
                                    </div>
                                    <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto">
                                        <i class="bx bx-down-arrow-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card radius-10 border-start border-0 border-3 border-danger mb-0 shadow-sm">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="mb-1 text-muted small text-uppercase font-weight-bold">Penarikan Hari Ini</p>
                                        <h5 class="mb-0 text-danger font-weight-bold">
                                            Rp {{ number_format($totalPenarikanHariIni ?? 0, 0, ',', '.') }}
                                        </h5>
                                    </div>
                                    <div class="widgets-icons-2 rounded-circle bg-light-danger text-danger ms-auto">
                                        <i class="bx bx-up-arrow-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card radius-10 border-start border-0 border-3 border-primary mb-0 shadow-sm">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="mb-1 text-muted small text-uppercase font-weight-bold">Aktivitas Transaksi Hari Ini</p>
                                        <h5 class="mb-0 text-primary font-weight-bold">
                                            {{ $countTransaksiHariIni ?? 0 }} Transaksi
                                        </h5>
                                    </div>
                                    <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                                        <i class="bx bx-receipt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MAIN TRANSACTION TERMINAL CARD --}}
                <div class="card radius-15 border shadow-sm">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <h5 class="card-title font-weight-bold mb-1">
                                    Kasir Tabungan — Modul {{ request()->get('jenis_transaksi') === 'Penarikan' ? 'Penarikan' : 'Setoran' }}
                                </h5>
                                <small class="text-muted">Pilih mode transaksi dan santri untuk mencatat kas tabungan santri</small>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="small text-muted me-1 font-weight-bold">Mode Transaksi:</span>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('transaksi.index', ['jenis_transaksi' => 'Setoran']) }}"
                                       class="btn btn-sm {{ request()->get('jenis_transaksi') !== 'Penarikan' ? 'btn-success' : 'btn-outline-success' }} d-flex align-items-center gap-1 font-weight-bold">
                                        <i class="bx bx-down-arrow-circle"></i> Setoran
                                    </a>
                                    <a href="{{ route('transaksi.index', ['jenis_transaksi' => 'Penarikan']) }}"
                                       class="btn btn-sm {{ request()->get('jenis_transaksi') === 'Penarikan' ? 'btn-danger' : 'btn-outline-danger' }} d-flex align-items-center gap-1 font-weight-bold">
                                        <i class="bx bx-up-arrow-circle"></i> Penarikan
                                    </a>
                                </div>

                                {{-- Hidden select for backward compatibility if scripts query #select --}}
                                <select name="select" id="select" class="d-none">
                                    <option value="Setoran" {{ request()->get('jenis_transaksi') !== 'Penarikan' ? 'selected' : '' }}>Setoran</option>
                                    <option value="Penarikan" {{ request()->get('jenis_transaksi') === 'Penarikan' ? 'selected' : '' }}>Penarikan</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        {{-- SANTRI QUICK LOOKUP BAR --}}
                        <div class="p-3 bg-light rounded radius-10 mb-2">
                            <label class="form-label small text-muted font-weight-bold mb-2">
                                <i class="bx bx-search-alt text-primary me-1"></i> Pencarian Santri (Ketik NIS 8-digit atau pilih dari daftar):
                            </label>
                            <div class="row g-2 align-items-center">
                                <div class="col-12 col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-barcode font-18"></i></span>
                                        <input type="search" class="form-control" maxlength="8" id="no_induk" name="no_induk"
                                            placeholder="Masukkan 8 digit No Induk" autofocus autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div id="spinner" style="display: none">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-7">
                                    <select name="name" id="name" class="form-control single-select">
                                        <option value="" selected disabled>-- Atau Cari Nama Santri di Sini --</option>
                                        @foreach ($santri as $model)
                                            <option value="{{ $model->id }}">{{ $model->user->name }} (NIS: {{ $model->id }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- SUB-FORM VIEW (SETORAN / PENARIKAN) --}}
                        @if (request()->get('jenis_transaksi') == 'Penarikan')
                            @include('pages.transaksi.penarikan')
                        @else
                            @include('pages.transaksi.setoran')
                        @endif
                    </div>
                </div>

                {{-- RECENT TRANSACTIONS LEDGER AUDIT FEED --}}
                <div class="card radius-15 border shadow-sm mt-4">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 font-weight-bold">
                                <i class="bx bx-history text-primary me-1"></i> Transaksi Terakhir Hari Ini
                            </h6>
                            <a href="{{ route('saldo_debit.index') }}" class="btn btn-outline-primary btn-sm">
                                <i class="bx bx-list-ul"></i> Buka Buku Rekening Tabungan
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if(isset($recentTransactions) && $recentTransactions->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Waktu</th>
                                            <th>Santri</th>
                                            <th>Tipe</th>
                                            <th>Nominal</th>
                                            <th>Saldo Akhir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentTransactions as $tx)
                                            <tr>
                                                <td class="ps-4 text-muted small">
                                                    {{ $tx->created_at ? $tx->created_at->format('H:i:s') : '-' }}
                                                </td>
                                                <td>
                                                    <strong>{{ $tx->santri?->user?->name ?? 'Santri' }}</strong>
                                                    <span class="text-muted small d-block">NIS: {{ $tx->santri?->no_induk }}</span>
                                                </td>
                                                <td>
                                                    @if($tx->jenis_transaksi === 'Setoran')
                                                        <span class="badge bg-light-success text-success border border-success font-12">
                                                            <i class="bx bx-down-arrow-circle me-1"></i> Setoran
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light-danger text-danger border border-danger font-12">
                                                            <i class="bx bx-up-arrow-circle me-1"></i> Penarikan
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong class="{{ $tx->jenis_transaksi === 'Setoran' ? 'text-success' : 'text-danger' }}">
                                                        {{ $tx->jenis_transaksi === 'Setoran' ? '+' : '-' }} Rp {{ number_format($tx->jumlah_transaksi, 0, ',', '.') }}
                                                    </strong>
                                                </td>
                                                <td class="text-muted">
                                                    Rp {{ number_format($tx->saldo_saatini, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="bx bx-receipt font-35 d-block mb-1 opacity-50"></i>
                                <span class="small">Belum ada transaksi tabungan yang tercatat hari ini.</span>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            $('#select').on('change', function() {
                window.location.href = "{{ route('transaksi.index') }}" + '?jenis_transaksi=' + $(this).val();
            });

            $('.single-select').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: '-- Pilih Santri --',
                allowClear: true,
            });
        });
    </script>
@endpush
