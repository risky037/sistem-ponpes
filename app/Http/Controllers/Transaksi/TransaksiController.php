<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\TransaksiTabungan;
use App\Services\FinancialTransactionService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Toastr;

class TransaksiController extends Controller
{
    public function __construct(
        protected FinancialTransactionService $financialTransactionService
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            $noinduk = request()->get('no_induk');
            $santri = Santri::with(['user', 'tabungan'])->where('no_induk', $noinduk)->first();
            if ($santri) {
                $saldo = $santri->tabungan?->saldo ?? 0;
                $hasWithdrawnToday = $santri->transaksi_tabungan()
                    ->whereDate('tanggal_transaksi', now()->toDateString())
                    ->where('jenis_transaksi', 'Penarikan')
                    ->exists();

                if (request()->get('jenis') == 'Penarikan' && $hasWithdrawnToday) {
                    return response()->json(['message' => "Santri dengan nomor induk <strong> $noinduk </strong> telah melakukan penarikan"], 200);
                }

                $data = [
                    'santri_id' => $santri->id,
                    'no_induk' => $santri->no_induk,
                    'name' => $santri->user->name,
                    'saldo' => number_format($saldo),
                    'saldo_raw' => $saldo,
                    'foto' => $santri->foto,
                    'kelas' => $santri->kelas_santri?->kelas?->kelas ?? '-',
                    'kamar' => $santri->kamar_santri?->kamar?->nama_kamar ?? '-',
                    'can_withdraw' => ! $hasWithdrawnToday,
                ];

                return response()->json(['data' => $data], 200);
            }

            return response()->json(['message' => 'Tidak ada data santri dengan nomor induk <strong>'.$noinduk.'</strong>'], 200);
        }

        $santri = Santri::with('user')->get(['no_induk as id', 'user_id']);

        $today = now()->toDateString();
        $totalSetoranHariIni = TransaksiTabungan::whereDate('tanggal_transaksi', $today)
            ->where('jenis_transaksi', 'Setoran')
            ->sum('jumlah_transaksi');

        $totalPenarikanHariIni = TransaksiTabungan::whereDate('tanggal_transaksi', $today)
            ->where('jenis_transaksi', 'Penarikan')
            ->sum('jumlah_transaksi');

        $countTransaksiHariIni = TransaksiTabungan::whereDate('tanggal_transaksi', $today)
            ->count();

        $recentTransactions = TransaksiTabungan::with(['santri.user'])
            ->whereDate('tanggal_transaksi', $today)
            ->latest('id')
            ->limit(5)
            ->get();

        return view('pages.transaksi.index', compact(
            'santri',
            'totalSetoranHariIni',
            'totalPenarikanHariIni',
            'countTransaksiHariIni',
            'recentTransactions'
        ));
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'santri_noinduk' => 'required|numeric|digits:8|exists:santris,no_induk',
            'debit' => 'required|numeric',
            'jenis_transaksi' => 'required',
        ]);
        try {
            if ($validate['debit'] < 50000) {
                Toastr::info('Minimal setoran Rp. 50.000');
            } else {
                $this->financialTransactionService->deposit(
                    $validate['santri_noinduk'],
                    $validate['debit'],
                    $validate['jenis_transaksi']
                );

                Toastr::success('Berhasil menyimpan data');
            }

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('TransaksiController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menyimpan data');

            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request)
    {
        $validate = $request->validate([
            'santri_noinduk' => 'required|numeric|digits:8|exists:santris,no_induk',
            'kredit' => 'required|numeric',
            'jenis_transaksi' => 'required',
        ]);
        try {
            if ($validate['kredit'] < 10000) {
                Toastr::info('Minimal penarikan 10.000 atau diatasnya');
            } else {
                $this->financialTransactionService->withdraw(
                    $validate['santri_noinduk'],
                    $validate['kredit'],
                    $request->get('tujuan') ?: 'Uang Jajan',
                    $validate['jenis_transaksi']
                );

                Toastr::success('Berhasil menyimpan data');
            }

            return redirect()->back()->withQuery(['jenis_transaksi' => 'Penarikan']);
        } catch (DomainException $de) {
            Toastr::info($de->getMessage());

            return redirect()->back()->withQuery(['jenis_transaksi' => 'Penarikan']);
        } catch (\Throwable $th) {
            Log::error('TransaksiController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menyimpan data');

            return redirect()->back()->withInput();
        }
    }
}
