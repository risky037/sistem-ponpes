<?php

namespace App\Helpers;

use App\Models\Santri;
use Illuminate\Support\Carbon;

class Helper
{
    public static function make_noinduk($params)
    {
        $last_tahun_masuk = substr($params['tahun_masuk'], 0, 4);
        $jenis_kelamin = $params['gender'];
        $tahun_masuk_hijriyah = $params['tahun_masuk_hijriyah'];

        // Ambil no_induk terakhir langsung dengan mengambil satu data
        $get_santri_latest = Santri::where('jenis_kelamin', $jenis_kelamin)
            ->whereYear('tahun_masuk', $last_tahun_masuk)
            ->orderBy('no_induk', 'desc')
            ->first(['no_induk']);

        if ($get_santri_latest) {
            // Ambil nomor induk terakhir dan tambahkan 1
            $start_noinduk = substr($get_santri_latest->no_induk, 4);
            $next = (int) $start_noinduk + 1;
        } else {
            // Tentukan nomor awal berdasarkan jenis kelamin
            $next = ($jenis_kelamin == 'Laki-Laki') ? 1 : 1001;
        }

        // Format nomor induk berikutnya dengan padding zero
        $next_noinduk = str_pad($next, 4, '0', STR_PAD_LEFT);
        $noinduk = $tahun_masuk_hijriyah.$next_noinduk;

        return $noinduk;
    }

    public static function isChecked($item, $array)
    {
        $checked = false;
        foreach ($item->permissions->toArray() as $key => $value) {
            $checked = in_array($array, $value);
        }

        return $checked;
    }

    public static function bulan_id($params): string
    {
        $bulanIndonesia = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $bulanIndonesia[$params];
    }

    /**
     * Format a date string or Carbon instance to a localized readable Indonesian format.
     *
     * @param  string|\DateTimeInterface|null  $date
     * @param  string  $format  Default is 'd F Y' (e.g. 05 Januari 2025)
     */
    public static function formatDate($date, string $format = 'd F Y'): string
    {
        if (empty($date)) {
            return '-';
        }

        try {
            if ($date instanceof \DateTimeInterface) {
                return Carbon::instance($date)->locale('id')->translatedFormat($format);
            }

            return Carbon::parse($date)->locale('id')->translatedFormat($format);
        } catch (\Throwable) {
            return (string) $date;
        }
    }

    /**
     * Format a datetime string or Carbon instance to a localized readable Indonesian format.
     *
     * @param  string|\DateTimeInterface|null  $date
     * @param  string  $format  Default is 'd F Y H:i'
     */
    public static function formatDateTime($date, string $format = 'd F Y H:i'): string
    {
        return self::formatDate($date, $format);
    }
}
