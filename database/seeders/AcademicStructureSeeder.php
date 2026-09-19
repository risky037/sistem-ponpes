<?php

namespace Database\Seeders;

use App\Models\Kamar;
use App\Models\Kelas;
use Illuminate\Database\Seeder;

class AcademicStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Kelas::firstOrCreate(
            ['kode' => 'KGJWF31042'],
            [
                'tingkatan' => 'ALFIYAH',
                'kelas' => 'Kelas Umum',
            ]
        );

        Kamar::firstOrCreate(
            ['kode' => 'OKRGX22240'],
            [
                'nama' => 'Kamar Umum',
                'blok' => 'A',
            ]
        );
    }
}
