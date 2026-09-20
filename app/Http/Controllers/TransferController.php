<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Models\Santri;
use App\Models\Transfer;
use App\Services\FinancialTransactionService;
use Illuminate\Support\Facades\Log;
use Toastr;
use Yajra\DataTables\Facades\DataTables;

class TransferController extends Controller
{
    public function __construct(
        protected FinancialTransactionService $financialTransactionService
    ) {}

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
        return $this->financialTransactionService->transfer($penerima, $pengirim, $jumlah, $keterangan);
    }
}
