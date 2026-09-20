<?php

namespace App\Exports\Academic;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AcademicEnrollmentExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected Collection $enrollments;

    protected int $rowIndex = 0;

    public function __construct(Collection $enrollments)
    {
        $this->enrollments = $enrollments;
    }

    public function collection(): Collection
    {
        return $this->enrollments;
    }

    public function headings(): array
    {
        return [
            'No',
            'NIS',
            'NISN',
            'Nama Santri',
            'Jenis Kelamin',
            'Kelas',
            'Angkatan',
            'Tahun Ajaran',
            'Semester',
            'Status Pendaftaran',
            'Tanggal Terdaftar',
        ];
    }

    public function map($row): array
    {
        $this->rowIndex++;
        $santri = $row->santri;
        $kelas = $row->kelas;
        $academicYear = $row->academicYear;
        $batch = $santri?->student_batch;

        return [
            $this->rowIndex,
            $santri?->nis ?? $santri?->no_induk ?? '-',
            $santri?->nisn ?? '-',
            $santri?->nama_lengkap ?? $santri?->user?->name ?? '-',
            $santri?->jenis_kelamin ?? '-',
            $kelas ? ($kelas->tingkatan.' - '.$kelas->kelas) : '-',
            $batch?->name ?? ($batch?->year ? 'Angkatan '.$batch->year : '-'),
            $academicYear?->name ?? '-',
            $academicYear?->semester ?? '-',
            $row->status ?? '-',
            $row->enrolled_at ? $row->enrolled_at->format('d/m/Y') : ($row->created_at ? $row->created_at->format('d/m/Y') : '-'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
