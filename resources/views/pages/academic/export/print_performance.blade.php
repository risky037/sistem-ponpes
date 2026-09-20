<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $result['title'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: normal;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .meta-table td {
            padding: 3px 6px;
            font-size: 11px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            border: 1px solid #777;
            padding: 6px 8px;
            text-align: left;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .text-center {
            text-align: center !important;
        }
        .no-print {
            margin-bottom: 15px;
        }
        .btn-print {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
            }
            @page {
                size: landscape;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">&#128438; Cetak Dokumen / Simpan PDF</button>
    </div>

    <div class="header">
        <h2>PONDOK PESANTREN FATIMAH AZ-ZAHRA</h2>
        <h3>{{ $result['title'] }}</h3>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 15%"><strong>Tahun Ajaran</strong></td>
            <td style="width: 35%">: {{ $academicYear ? $academicYear->name . ' (' . $academicYear->semester . ')' : 'Semua Tahun Ajaran' }}</td>
            <td style="width: 15%"><strong>Dicetak Oleh</strong></td>
            <td style="width: 35%">: {{ auth()->user()->name ?? 'Administrator' }}</td>
        </tr>
        <tr>
            <td><strong>Kelas</strong></td>
            <td>: {{ $kelas ? $kelas->tingkatan . ' - ' . $kelas->kelas : 'Semua Kelas' }}</td>
            <td><strong>Waktu Cetak</strong></td>
            <td>: {{ now()->translatedFormat('d F Y H:i') }} WIB</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%">No</th>
                <th style="width: 10%">NIS</th>
                <th>Nama Santri</th>
                <th style="width: 10%">Kelas</th>
                <th style="width: 10%">Tahun Ajaran</th>
                <th style="width: 8%">Sesi Hadir</th>
                <th style="width: 10%">Kehadiran (%)</th>
                <th style="width: 10%">Komponen Nilai</th>
                <th style="width: 10%">Rata-rata Nilai</th>
                <th style="width: 10%">Status Rekap</th>
                <th style="width: 12%">Tgl Hitung</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($result['data'] as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $row->nis ?? '-' }}</td>
                    <td><strong>{{ $row->nama ?? '-' }}</strong></td>
                    <td>{{ $row->kelas ?? '-' }}</td>
                    <td>{{ $row->tahun_ajaran ?? '-' }}</td>
                    <td class="text-center">{{ $row->present_count ?? 0 }} / {{ $row->total_sessions ?? 0 }}</td>
                    <td class="text-center"><strong>{{ isset($row->attendance_rate) ? number_format((float) $row->attendance_rate, 2) . '%' : '-' }}</strong></td>
                    <td class="text-center">{{ $row->scored_components ?? 0 }} / {{ $row->total_components ?? 0 }}</td>
                    <td class="text-center fw-bold"><strong>{{ isset($row->average_score) ? number_format((float) $row->average_score, 2) : '-' }}</strong></td>
                    <td class="text-center">{{ $row->computation_status ?? '-' }}</td>
                    <td class="text-center">{{ $row->computed_at ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">Tidak ada data performa yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
