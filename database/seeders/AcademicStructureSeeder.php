<?php

namespace Database\Seeders;

use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Mapel;
use Illuminate\Database\Seeder;

class AcademicStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kelas = Kelas::firstOrCreate(
            ['kode' => 'KGJWF31042'],
            [
                'tingkatan' => 'ALFIYAH',
                'kelas' => 'Kelas Umum',
            ]
        );

        Mapel::firstOrCreate(
            ['code' => 'MPL-ALF-01'],
            [
                'kelas_id' => $kelas->id,
                'name' => 'Alfiyah Ibnu Malik',
                'description' => 'Kajian Kaidah Gramatika Bahasa Arab',
                'is_active' => true,
            ]
        );

        Mapel::firstOrCreate(
            ['code' => 'MPL-FIQ-01'],
            [
                'kelas_id' => $kelas->id,
                'name' => 'Fathul Qorib',
                'description' => 'Kajian Fiqih Dasar',
                'is_active' => true,
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
