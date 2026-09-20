<?php

namespace App\Exports\Academic;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeachingAssignmentExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected Collection $assignments;

    protected int $rowIndex = 0;

    public function __construct(Collection $assignments)
    {
        $this->assignments = $assignments;
    }

    public function collection(): Collection
    {
        return $this->assignments;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tahun Ajaran',
            'Semester',
            'Kelas',
            'Mata Pelajaran',
            'Kode Mapel',
            'Guru Pengajar',
            'Status',
            'Jadwal Hari & Jam',
        ];
    }

    public function map($row): array
    {
        $this->rowIndex++;
        $academicYear = $row->academicYear;
        $kelas = $row->kelas;
        $mapel = $row->mapel;
        $teacher = $row->user;

        $schedules = '-';
        if ($row->relationLoaded('classSchedules') && $row->classSchedules->isNotEmpty()) {
            $schedules = $row->classSchedules->map(function ($s) {
                return $s->day_of_week.' ('.$s->start_time.' - '.$s->end_time.')';
            })->implode(', ');
        }

        return [
            $this->rowIndex,
            $academicYear?->name ?? '-',
            $academicYear?->semester ?? '-',
            $kelas ? ($kelas->tingkatan.' - '.$kelas->kelas) : '-',
            $mapel?->name ?? '-',
            $mapel?->code ?? '-',
            $teacher?->name ?? '-',
            $row->status ?? '-',
            $schedules,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
