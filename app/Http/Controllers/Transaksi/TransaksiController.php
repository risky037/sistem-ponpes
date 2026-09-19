<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\Tabungan;
use App\Models\TransaksiTabungan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Toastr;

class TransaksiController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $noinduk = request()->get('no_induk');
            $santri = Santri::with(['user', 'tabungan'])->where('no_induk', $noinduk)->first();
            if ($santri) {
                $saldo = $santri->tabungan?->saldo ?? 0;
                if (request()->get('jenis') == 'Penarikan') {
                    $hasWithdrawnToday = $santri->transaksi_tabungan()
                        ->whereDate('tanggal_transaksi', now()->toDateString())
                        ->where('jenis_transaksi', 'Penarikan')
                        ->exists();

                    if ($hasWithdrawnToday) {
                        return response()->json(['message' => "Santri dengan nomor induk <strong> $noinduk </strong> telah melakukan penarikan"], 200);
                    }
                }

                $data = [
                    'santri_id' => $santri->id,
                    'no_induk' => $santri->no_induk,
                    'name' => $santri->user->name,
                    'saldo' => number_format($saldo),
                    'foto' => $santri->foto,
                ];

                return response()->json(['data' => $data], 200);
            }

            return response()->json(['message' => 'Tidak ada data santri dengan nomor induk <strong>'.$noinduk.'</strong>'], 200);
        }
        $santri = Santri::with('user')->get(['no_induk as id', 'user_id']);

        return view('pages.transaksi.index', compact('santri'));
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
                DB::transaction(function () use ($validate) {
                    $santri = Santri::firstWhere('no_induk', $validate['santri_noinduk']);
                    $tabungan = Tabungan::where('santri_id', $santri->id)->lockForUpdate()->firstOrFail();

                    $saldoSebelumnya = $tabungan->saldo;
                    $saldoSaatIni = $saldoSebelumnya + $validate['debit'];

                    $transaksi = TransaksiTabungan::create([
                        'santri_id' => $santri->id,
                        'tanggal_transaksi' => date('Y-m-d'),
                        'jenis_transaksi' => $validate['jenis_transaksi'],
                        'jumlah_transaksi' => $validate['debit'],
                        'saldo_sebelumnya' => $saldoSebelumnya,
                        'saldo_saatini' => $saldoSaatIni,
                    ]);

                    $tabungan->update([
                        'saldo' => $saldoSaatIni,
                    ]);
                });

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
                $santri = Santri::firstWhere('no_induk', $validate['santri_noinduk']);

                $executed = DB::transaction(function () use ($validate, $santri) {
                    $tabungan = Tabungan::where('santri_id', $santri->id)->lockForUpdate()->firstOrFail();

                    if ($tabungan->saldo < $validate['kredit']) {
                        Toastr::info('Saldo tidak cukup, saldo saat ini '.$tabungan->saldo);

                        return false;
                    }

                    $tr_now = TransaksiTabungan::where('santri_id', $santri->id)
                        ->whereDate('tanggal_transaksi', now()->toDateString())
                        ->where('jenis_transaksi', 'Penarikan')
                        ->get();

                    if (! $tr_now->isEmpty()) {
                        Toastr::info('Santri dengan nomor induk '."$santri->no_induk".' telah selesai melakukan penarikan');

                        return false;
                    }

                    $saldoSebelumnya = $tabungan->saldo;
                    $saldoSaatIni = $saldoSebelumnya - $validate['kredit'];

                    $transaksi = TransaksiTabungan::create([
                        'santri_id' => $santri->id,
                        'tanggal_transaksi' => date('Y-m-d'),
                        'jenis_transaksi' => $validate['jenis_transaksi'],
                        'jumlah_transaksi' => $validate['kredit'],
                        'saldo_sebelumnya' => $saldoSebelumnya,
                        'saldo_saatini' => $saldoSaatIni,
                        'tujuan' => request()->get('tujuan') != null ? request()->get('tujuan') : 'Uang Jajan',
                    ]);

                    $tabungan->update([
                        'saldo' => $saldoSaatIni,
                    ]);

                    return true;
                });

                if ($executed) {
                    $this->send_message($santri, 'Uang Jajan', number_format($validate['kredit']));
                    Toastr::success('Berhasil menyimpan data');
                }
            }

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

    public function send_message($santri, $tujuan, $nominal)
    {
        $sender = config('whatsapp.sender_number');
        $number = isset($santri->whatsapp) ? $santri->whatsapp : '';
        $apiKey = config('whatsapp.api_key');
        $tanggal = now('Asia/Jakarta')->format('d-F-Y H:i:s');
        $pesan = "*Assalamualaikum Wr. Wb.*\n\n";
        $pesan .= "Hormat Kami,\n";
        $pesan .= "Kami dari pengurus Pondok Pesantren *Al-Ibrohimy Masaran Sentol Daya Pragaan Sumenep* ingin memberitahukan bahwa santri sebagaimana data berikut telah melakukan transaksi tarik tunai tabungan:\n\n";
        $pesan .= "Nama: *{$santri->user->name}*\n";
        $pesan .= "Nominal: *Rp. {$nominal}*\n";
        $pesan .= "Tujuan: *{$tujuan}*\n";
        $pesan .= "Tanggal: *{$tanggal}*\n\n";
        $pesan .= "Demikian pemberitahuan ini kami sampaikan terimakasih, dan mohon maaf telah mengganggu waktu anda.\n";
        $pesan .= "Sekian dari kami Wassalamualaikuk Wr. Wb.\n\n";
        $pesan .= "Hormat kami,\n";
        $pesan .= '*Pengurus Pondok Pesantren Al-Ibrohimy*';
        $params = [
            'api_key' => $apiKey,
            'sender' => $sender,
            'number' => $number,
            'message' => $pesan,
        ];

        // return $params;
        $url = 'https://connect.labelin.co/send-message';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
