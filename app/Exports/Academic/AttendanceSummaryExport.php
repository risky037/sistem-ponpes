<?php

namespace App\Exports\Academic;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceSummaryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected Collection $rows;

    protected int $rowIndex = 0;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'No',
            'NIS',
            'Nama Santri',
            'Kelas',
            'Tahun Ajaran',
            'Total Sesi',
            'Hadir',
            'Izin',
            'Sakit',
            'Alpha',
            'Persentase Kehadiran (%)',
        ];
    }

    public function map($row): array
    {
        $this->rowIndex++;

        if (is_array($row)) {
            return [
                $this->rowIndex,
                $row['nis'] ?? '-',
                $row['nama'] ?? '-',
                $row['kelas'] ?? '-',
                $row['tahun_ajaran'] ?? '-',
                $row['total_sessions'] ?? 0,
                $row['present_count'] ?? 0,
                $row['excused_count'] ?? 0,
                $row['sick_count'] ?? 0,
                $row['absent_count'] ?? 0,
                isset($row['attendance_rate']) ? number_format((float) $row['attendance_rate'], 2).'%' : '0.00%',
            ];
        }

        return [
            $this->rowIndex,
            $row->nis ?? '-',
            $row->nama ?? '-',
            $row->kelas ?? '-',
            $row->tahun_ajaran ?? '-',
            $row->total_sessions ?? 0,
            $row->present_count ?? 0,
            $row->excused_count ?? 0,
            $row->sick_count ?? 0,
            $row->absent_count ?? 0,
            isset($row->attendance_rate) ? number_format((float) $row->attendance_rate, 2).'%' : '0.00%',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
