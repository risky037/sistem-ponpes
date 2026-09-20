<?php

namespace App\Services;

use App\Models\Santri;
use App\Models\Tabungan;
use App\Models\TransaksiTabungan;
use App\Models\Transfer;
use DomainException;
use Exception;
use Illuminate\Support\Facades\DB;

class FinancialTransactionService
{
    /**
     * Perform a deposit (setoran) into a student's savings account.
     */
    public function deposit(string|Santri $santri, float|int $amount, string $jenisTransaksi = 'Setoran'): TransaksiTabungan
    {
        return DB::transaction(function () use ($santri, $amount, $jenisTransaksi) {
            $santriModel = $santri instanceof Santri ? $santri : Santri::firstWhere('no_induk', $santri);
            if (! $santriModel) {
                throw new DomainException('Santri tidak ditemukan.');
            }

            $tabungan = Tabungan::where('santri_id', $santriModel->id)->lockForUpdate()->firstOrFail();

            $saldoSebelumnya = $tabungan->saldo;
            $saldoSaatIni = $saldoSebelumnya + $amount;

            $transaksi = TransaksiTabungan::create([
                'santri_id' => $santriModel->id,
                'tanggal_transaksi' => date('Y-m-d'),
                'jenis_transaksi' => $jenisTransaksi,
                'jumlah_transaksi' => $amount,
                'saldo_sebelumnya' => $saldoSebelumnya,
                'saldo_saatini' => $saldoSaatIni,
            ]);

            $tabungan->update([
                'saldo' => $saldoSaatIni,
            ]);

            return $transaksi;
        });
    }

    /**
     * Perform a withdrawal (penarikan) from a student's savings account.
     */
    public function withdraw(string|Santri $santri, float|int $amount, ?string $tujuan = 'Uang Jajan', string $jenisTransaksi = 'Penarikan'): TransaksiTabungan
    {
        return DB::transaction(function () use ($santri, $amount, $tujuan, $jenisTransaksi) {
            $santriModel = $santri instanceof Santri ? $santri : Santri::firstWhere('no_induk', $santri);
            if (! $santriModel) {
                throw new DomainException('Santri tidak ditemukan.');
            }

            $tabungan = Tabungan::where('santri_id', $santriModel->id)->lockForUpdate()->firstOrFail();

            if ($tabungan->saldo < $amount) {
                throw new DomainException('Saldo tidak cukup, saldo saat ini '.$tabungan->saldo);
            }

            $trNow = TransaksiTabungan::where('santri_id', $santriModel->id)
                ->whereDate('tanggal_transaksi', now()->toDateString())
                ->where('jenis_transaksi', 'Penarikan')
                ->get();

            if (! $trNow->isEmpty()) {
                throw new DomainException('Santri dengan nomor induk '.$santriModel->no_induk.' telah selesai melakukan penarikan');
            }

            $saldoSebelumnya = $tabungan->saldo;
            $saldoSaatIni = $saldoSebelumnya - $amount;

            $transaksi = TransaksiTabungan::create([
                'santri_id' => $santriModel->id,
                'tanggal_transaksi' => date('Y-m-d'),
                'jenis_transaksi' => $jenisTransaksi,
                'jumlah_transaksi' => $amount,
                'saldo_sebelumnya' => $saldoSebelumnya,
                'saldo_saatini' => $saldoSaatIni,
                'tujuan' => $tujuan ?: 'Uang Jajan',
            ]);

            $tabungan->update([
                'saldo' => $saldoSaatIni,
            ]);

            return $transaksi;
        });
    }

    /**
     * Transfer funds between two students' savings accounts.
     */
    public function transfer(Santri $penerima, Santri $pengirim, float|int $amount, ?string $keterangan = null): Transfer
    {
        return DB::transaction(function () use ($penerima, $pengirim, $amount, $keterangan) {
            if ($pengirim->id === $penerima->id) {
                throw new DomainException('Santri pengirim dan penerima tidak boleh sama.');
            }

            // Determine deterministic locking order by santri id to eliminate deadlock risk
            $firstId = min($pengirim->id, $penerima->id);
            $secondId = max($pengirim->id, $penerima->id);

            $firstTabungan = Tabungan::where('santri_id', $firstId)->lockForUpdate()->firstOrFail();
            $secondTabungan = Tabungan::where('santri_id', $secondId)->lockForUpdate()->firstOrFail();

            $pengirimTabungan = ($pengirim->id === $firstId) ? $firstTabungan : $secondTabungan;
            $penerimaTabungan = ($penerima->id === $firstId) ? $firstTabungan : $secondTabungan;

            // Re-verify balance inside the transaction under exclusive lock (prevents TOCTOU race condition)
            if ($pengirimTabungan->saldo < $amount) {
                throw new Exception('Saldo tidak mencukupi.');
            }

            // Pengirim
            $pengirimSaldoSebelumnya = $pengirimTabungan->saldo;
            $pengirimTabungan->saldo = $pengirimTabungan->saldo - $amount;
            $pengirimTabungan->save();

            TransaksiTabungan::create([
                'santri_id' => $pengirim->id,
                'tanggal_transaksi' => now(),
                'jenis_transaksi' => 'Penarikan',
                'tujuan' => 'Transfer ke '.$penerima->user->name,
                'jumlah_transaksi' => $amount,
                'saldo_sebelumnya' => $pengirimSaldoSebelumnya,
                'saldo_saatini' => $pengirimTabungan->saldo,
                'keterangan' => $keterangan,
            ]);

            // Penerima
            $penerimaSaldoSebelumnya = $penerimaTabungan->saldo;
            $penerimaTabungan->saldo = $penerimaTabungan->saldo + $amount;
            $penerimaTabungan->save();

            TransaksiTabungan::create([
                'santri_id' => $penerima->id,
                'tanggal_transaksi' => now(),
                'jenis_transaksi' => 'Setoran',
                'jumlah_transaksi' => $amount,
                'saldo_sebelumnya' => $penerimaSaldoSebelumnya,
                'saldo_saatini' => $penerimaTabungan->saldo,
                'tujuan' => 'Transfer dari '.$pengirim->user->name,
                'keterangan' => $keterangan,
            ]);

            // Catat Transfer
            return Transfer::create([
                'pengirim_id' => $pengirim->id,
                'penerima_id' => $penerima->id,
                'jumlah_transfer' => $amount,
                'keterangan' => $keterangan,
            ]);
        });
    }
}
