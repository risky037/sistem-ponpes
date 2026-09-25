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
                <th style="width: 10%">NISN</th>
                <th>Nama Santri</th>
                <th style="width: 10%">L/P</th>
                <th style="width: 12%">Kelas</th>
                <th style="width: 12%">Angkatan</th>
                <th style="width: 10%">Status</th>
                <th style="width: 12%">Tgl Terdaftar</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($result['data'] as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $row->santri?->nis ?? $row->santri?->no_induk ?? '-' }}</td>
                    <td>{{ $row->santri?->nisn ?? '-' }}</td>
                    <td><strong>{{ $row->santri?->nama_lengkap ?? $row->santri?->user?->name ?? '-' }}</strong></td>
                    <td class="text-center">{{ $row->santri?->jenis_kelamin ?? '-' }}</td>
                    <td>{{ $row->kelas ? $row->kelas->tingkatan . ' - ' . $row->kelas->kelas : '-' }}</td>
                    <td>{{ $row->santri?->student_batch?->name ?? ($row->santri?->student_batch?->year ? 'Angkatan ' . $row->santri?->student_batch?->year : '-') }}</td>
                    <td class="text-center">{{ $row->status ?? '-' }}</td>
                    <td class="text-center">{{ $row->enrolled_at ? $row->enrolled_at->format('d/m/Y') : ($row->created_at ? $row->created_at->format('d/m/Y') : '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data pendaftaran yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
