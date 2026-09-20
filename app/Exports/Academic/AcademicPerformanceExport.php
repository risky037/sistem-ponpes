<?php

namespace App\Exports\Academic;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AcademicPerformanceExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
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
            'Semester',
            'Total Sesi',
            'Kehadiran (Hadir)',
            'Tingkat Kehadiran (%)',
            'Komponen Dinilai',
            'Total Komponen',
            'Rata-rata Nilai',
            'Status Komputasi',
            'Waktu Komputasi',
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
                $row['semester'] ?? '-',
                $row['total_sessions'] ?? 0,
                $row['present_count'] ?? 0,
                isset($row['attendance_rate']) ? number_format((float) $row['attendance_rate'], 2).'%' : '-',
                $row['scored_components'] ?? 0,
                $row['total_components'] ?? 0,
                isset($row['average_score']) ? number_format((float) $row['average_score'], 2) : '-',
                $row['computation_status'] ?? '-',
                $row['computed_at'] ?? '-',
            ];
        }

        return [
            $this->rowIndex,
            $row->nis ?? '-',
            $row->nama ?? '-',
            $row->kelas ?? '-',
            $row->tahun_ajaran ?? '-',
            $row->semester ?? '-',
            $row->total_sessions ?? 0,
            $row->present_count ?? 0,
            isset($row->attendance_rate) ? number_format((float) $row->attendance_rate, 2).'%' : '-',
            $row->scored_components ?? 0,
            $row->total_components ?? 0,
            isset($row->average_score) ? number_format((float) $row->average_score, 2) : '-',
            $row->computation_status ?? '-',
            $row->computed_at ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
