<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Models\Santri;
use App\Models\Tabungan;
use App\Models\TransaksiTabungan;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Toastr;
use Yajra\DataTables\Facades\DataTables;

class TransferController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $data = Transfer::with(['pengirim.user', 'penerima.user'])->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', 'pages.transfer.include.action')
                ->toJson();
        }

        return view('pages.transfer.index');
    }

    public function store(TransferRequest $request)
    {
        $validated = $request->validated();
        try {
            $pengirim = Santri::with('tabungan')->findOrFail($validated['pengirim_id']);
            $penerima = Santri::with('tabungan')->findOrFail($validated['penerima_id']);
            $jumlah = $validated['nominal'];
            $keterangan = $validated['keterangan'];
            if ($pengirim->id == $penerima->id) {
                Toastr::error('Santri pengirim dan penerima tidak boleh sama.');

                return redirect()->back();
            }

            $pengirimTabungan = $pengirim->tabungan;
            if (! $pengirimTabungan || $pengirimTabungan->saldo < $jumlah) {
                Toastr::error('Saldo tidak mencukupi.');

                return redirect()->back();
            }

            $this->transfer($penerima, $pengirim, $jumlah, $keterangan);
            Toastr::success('Transfer berhasil.');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('TransferController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);

            if ($th->getMessage() === 'Saldo tidak mencukupi.') {
                Toastr::error('Saldo tidak mencukupi.');
            } else {
                Toastr::error('Transfer gagal.');
            }

            return redirect()->back();
        }
    }

    public function transfer(Santri $penerima, Santri $pengirim, $jumlah, $keterangan = null)
    {
        DB::transaction(function () use ($penerima, $pengirim, $jumlah, $keterangan) {
            // Determine deterministic locking order by santri id to eliminate deadlock risk
            $firstId = min($pengirim->id, $penerima->id);
            $secondId = max($pengirim->id, $penerima->id);

            $firstTabungan = Tabungan::where('santri_id', $firstId)->lockForUpdate()->firstOrFail();
            $secondTabungan = Tabungan::where('santri_id', $secondId)->lockForUpdate()->firstOrFail();

            $pengirimTabungan = ($pengirim->id === $firstId) ? $firstTabungan : $secondTabungan;
            $penerimaTabungan = ($penerima->id === $firstId) ? $firstTabungan : $secondTabungan;

            // Re-verify balance inside the transaction under exclusive lock (prevents TOCTOU race condition)
            if ($pengirimTabungan->saldo < $jumlah) {
                throw new \Exception('Saldo tidak mencukupi.');
            }

            // Pengirim
            $pengirimSaldoSebelumnya = $pengirimTabungan->saldo;
            $pengirimTabungan->saldo = $pengirimTabungan->saldo - $jumlah;
            $pengirimTabungan->save();
            TransaksiTabungan::create([
                'santri_id' => $pengirim->id,
                'tanggal_transaksi' => now(),
                'jenis_transaksi' => 'Penarikan',
                'tujuan' => 'Transfer ke '.$penerima->user->name,
                'jumlah_transaksi' => $jumlah,
                'saldo_sebelumnya' => $pengirimSaldoSebelumnya,
                'saldo_saatini' => $pengirimTabungan->saldo,
                'keterangan' => $keterangan,
            ]);

            // Penerima
            $penerimaSaldoSebelumnya = $penerimaTabungan->saldo;
            $penerimaTabungan->saldo = $penerimaTabungan->saldo + $jumlah;
            $penerimaTabungan->save();
            TransaksiTabungan::create([
                'santri_id' => $penerima->id,
                'tanggal_transaksi' => now(),
                'jenis_transaksi' => 'Setoran',
                'jumlah_transaksi' => $jumlah,
                'saldo_sebelumnya' => $penerimaSaldoSebelumnya,
                'saldo_saatini' => $penerimaTabungan->saldo,
                'tujuan' => 'Transfer dari '.$pengirim->user->name,
                'keterangan' => $keterangan,
            ]);

            // Catat Transfer
            Transfer::create([
                'pengirim_id' => $pengirim->id,
                'penerima_id' => $penerima->id,
                'jumlah_transfer' => $jumlah,
                'keterangan' => $keterangan,
            ]);
        });
    }
}
