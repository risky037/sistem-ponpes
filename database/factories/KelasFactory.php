<?php

namespace Database\Factories;

use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    protected $model = Kelas::class;

    public function definition(): array
    {
        $tingkatan = fake()->randomElement(['AWWALIYAH', 'WUSTHO', 'ULYA', 'ALFIYAH']);
        $tingkatNum = fake()->randomElement(['1', '2', '3']);
        $section = fake()->randomElement(['A', 'B', 'C']);

        return [
            'kode' => strtoupper(Str::random(10)),
            'tingkatan' => $tingkatan,
            'kelas' => "Kelas {$tingkatNum}{$section} {$tingkatan}",
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
