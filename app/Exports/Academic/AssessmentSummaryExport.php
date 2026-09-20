<?php

namespace App\Exports\Academic;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssessmentSummaryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
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
            'Mata Pelajaran',
            'Tahun Ajaran',
            'Komponen Penilaian',
            'Tipe',
            'Bobot (%)',
            'Nilai',
            'Tanggal Dinilai',
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
                $row['mapel'] ?? '-',
                $row['tahun_ajaran'] ?? '-',
                $row['component_name'] ?? '-',
                $row['type'] ?? '-',
                $row['weight'] ?? 0,
                isset($row['score']) ? number_format((float) $row['score'], 2) : '-',
                $row['graded_at'] ?? '-',
            ];
        }

        return [
            $this->rowIndex,
            $row->nis ?? '-',
            $row->nama ?? '-',
            $row->kelas ?? '-',
            $row->mapel ?? '-',
            $row->tahun_ajaran ?? '-',
            $row->component_name ?? '-',
            $row->type ?? '-',
            $row->weight ?? 0,
            isset($row->score) ? number_format((float) $row->score, 2) : '-',
            $row->graded_at ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
