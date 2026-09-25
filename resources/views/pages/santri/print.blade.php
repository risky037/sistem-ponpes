@php
    $bulan = [
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

    $userName = $santri->user?->name ? ucwords(strtolower($santri->user->name)) : '-';
    $namaAyah = $santri->wali_santri?->nama_ayah
        ? ucwords(strtolower($santri->wali_santri->nama_ayah))
        : ($santri->wali_santri?->nama_wali ? ucwords(strtolower($santri->wali_santri->nama_wali)) : '-');
    $namaIbu = $santri->wali_santri?->nama_ibu ? ucwords(strtolower($santri->wali_santri->nama_ibu)) : '-';
    $alamatSantri = $santri->alamat_santri?->alamat_lengkap ? ucwords(strtolower($santri->alamat_santri->alamat_lengkap)) : '-';
    $kelasSantri = $santri->kelas_santri?->kelas
        ? trim(($santri->kelas_santri->kelas->tingkatan ?? '') . ' ' . ($santri->kelas_santri->kelas->kelas ?? ''))
        : '-';
    $kamarSantri = $santri->kamar_santri?->kamar?->nama ?? '-';
    $batchSantri = $santri->student_batch?->name ?? ($santri->tahun_masuk ?? '-');

    $ttlParts = [];
    if (!empty($santri->tempat_lahir)) {
        $ttlParts[] = ucwords(strtolower($santri->tempat_lahir));
    }
    if (!empty($santri->tanggal_lahir)) {
        $ttlParts[] = \App\Helpers\Helper::formatDate($santri->tanggal_lahir);
    }
    $ttl = !empty($ttlParts) ? implode(', ', $ttlParts) : '-';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PRINT KTS - {{ strtoupper($santri->user?->name ?? 'SANTRI') }}</title>

    <style>
        @media print {
            @page {
                margin: 0;
                size: landscape
                    /* Menghilangkan margin default untuk kertas cetak */
            }

            body {
                width: 240px;
                height: 295px;
            }
        }

        .bg {
            position: relative;
            z-index: 0;
        }

        .foto-santri {
            position: absolute;
            margin-left: 3.5%;
            margin-top: -30.3%;
            z-index: 0;
            width: 17.3%;
        }
    </style>
</head>

<body>
    <img src="{{ url('img/kts-depan.png') }}" alt="" class="bg">

    {{-- no induk dan nama --}}
    <p>
    <div class="row"style="position: absolute; margin-top: -30rem; margin-left: 15%; font-size: 27px; color: #333333">
        <div class="col" style="margin-top: -1%">
            {{ ucwords($santri->no_induk ?? '-') }}
        </div>
        <div class="col" style="margin-left: 35.3rem; margin-top: -2rem">
            @if (!empty($santri->no_induk))
                <span>
                    <img src="{{ 'data:image/png;base64,' . DNS1D::getBarcodePNG($santri->no_induk, 'I25', 3, 60, [1, 1, 1]) }}"
                        alt="Barcode {{ $santri->no_induk }}">
                </span>
            @endif
        </div>
    </div>
    </p>
    <p style="position: absolute; margin-top: -28.5rem; margin-left: 15%; font-size: 27px; color: #333333">
        {{ $userName }}</p>

    {{-- biodata --}}
    <p style="position: absolute; margin-top: -26rem; margin-left: 34%; font-size: 27px; color: #333333">
        {{ $ttl }}</p>
    <p style="position: absolute; margin-top: -21.2rem; margin-left: 34%; font-size: 27px; color: #333333">
        {{ $namaAyah }}</p>
    <p style="position: absolute; margin-top: -18.7rem; margin-left: 34%; font-size: 27px; color: #333333">
        {{ $namaIbu }}</p>
    {{-- alamat --}}
    <p style="position: absolute; margin-top: -16.5rem; margin-left: 34%; font-size: 27px; color: #333333">
        {{ $alamatSantri }}
    </p>

    {{-- metadata audit for class, room, batch --}}
    <div class="kts-meta" style="display: none;"
        data-user-name="{{ $userName }}"
        data-nis="{{ $santri->no_induk ?? '-' }}"
        data-wali-ayah="{{ $namaAyah }}"
        data-wali-ibu="{{ $namaIbu }}"
        data-address="{{ $alamatSantri }}"
        data-class="{{ $kelasSantri }}"
        data-room="{{ $kamarSantri }}"
        data-batch="{{ $batchSantri }}">
    </div>

    {{-- foto santri --}}
    <img src="{{ $santri->fotoUrl() }}" alt="Foto {{ $userName }}" class="foto-santri"
        onerror="this.onerror=null;this.src='{{ asset('img/santri.png') }}';">
    <script>
        window.print()
        setInterval(() => {
            window.close()
        }, 1000);
    </script>
</body>

</html>
