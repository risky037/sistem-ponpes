@extends('layouts.app')

@section('title', 'Riwayat Tabungan | DIGITREN')

@section('content')
    <div>
        <!--page-wrapper-->
        <div class="page-wrapper">
            <!--page-content-wrapper-->
            <div class="page-content-wrapper">
                <div class="page-content">
                    <x-breadcrumb url="{{ route('dashboard') }}" attribute="required" path='Tabungan'></x-breadcrumb>
                    <div class="card">
                        <div class="card-body">
                        <x-card-toolbar title="Riwayat Transaksi {{ isset($data) && $data->isNotEmpty() ? '- ' . $data->first()?->santri?->user?->name : '' }}">
                            <a href="{{ route('saldo_debit.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bx bx-arrow-back"></i> Kembali
                            </a>
                        </x-card-toolbar>

                        @if(isset($data) && $data->isNotEmpty())
                            @php
                                $totalSetor = $data->where('jenis_transaksi', 'Setoran')->sum('jumlah_transaksi');
                                $totalTarik = $data->where('jenis_transaksi', 'Penarikan')->sum('jumlah_transaksi');
                                $currentSaldo = $data->first()->saldo_saatini ?? 0;
                            @endphp
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <div class="p-2 border rounded bg-light-success text-success">
                                        <small class="text-muted d-block">Total Setoran</small>
                                        <strong>Rp {{ number_format($totalSetor, 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 border rounded bg-light-danger text-danger">
                                        <small class="text-muted d-block">Total Penarikan</small>
                                        <strong>Rp {{ number_format($totalTarik, 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 border rounded bg-light-primary text-primary">
                                        <small class="text-muted d-block">Saldo Terakhir</small>
                                        <strong>Rp {{ number_format($currentSaldo, 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                            </div>
                        @endif

                            <div class="row mb-2">
                                <div class="col">
                                    <div class="table-responsive">
                                        <table class="table table-hover dataTable" style="width:100%" role="grid"
                                            id="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Tanggal Transaksi</th>
                                                    <th>Setor</th>
                                                    <th>Tarik</th>
                                                    <th>Saldo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($data as $item)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>
                                                            <div class="font-weight-bold">@formatDate($item->tanggal_transaksi)</div>
                                                            @if($item->created_at)
                                                                <small class="text-muted">{{ $item->created_at->diffForHumans() }}</small>
                                                            @endif
                                                        </td>
                                                        <td class="text-success font-weight-bold">
                                                            @if ($item->jenis_transaksi === 'Setoran')
                                                                + Rp {{ number_format($item->jumlah_transaksi, 0, ',', '.') }}
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td class="text-danger font-weight-bold">
                                                            @if ($item->jenis_transaksi === 'Penarikan')
                                                                - Rp {{ number_format($item->jumlah_transaksi, 0, ',', '.') }}
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td>
                                                            Rp {{ number_format($item->saldo_saatini, 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
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
