<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\StudentBatch;
use Illuminate\Database\Seeder;

class AcademicFoundationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AcademicYear::firstOrCreate(
            ['name' => '2024/2025', 'semester' => 'Ganjil'],
            [
                'start_date' => '2024-07-15',
                'end_date' => '2024-12-20',
                'is_active' => true,
            ]
        );

        AcademicYear::firstOrCreate(
            ['name' => '2024/2025', 'semester' => 'Genap'],
            [
                'start_date' => '2025-01-06',
                'end_date' => '2025-06-20',
                'is_active' => false,
            ]
        );

        StudentBatch::firstOrCreate(
            ['name' => 'Angkatan 2023'],
            [
                'year' => 2023,
                'description' => 'Santri angkatan masuk tahun 2023',
            ]
        );

        StudentBatch::firstOrCreate(
            ['name' => 'Angkatan 2024'],
            [
                'year' => 2024,
                'description' => 'Santri angkatan masuk tahun 2024',
            ]
        );
    }
}
