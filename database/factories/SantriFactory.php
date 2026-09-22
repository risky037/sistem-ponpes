<?php

namespace Database\Factories;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Santri>
 */
class SantriFactory extends Factory
{
    protected $model = Santri::class;

    public function definition(): array
    {
        $gender = fake()->randomElement(['Laki-Laki', 'Perempuan']);
        $year = fake()->numberBetween(2022, 2025);

        return [
            'user_id' => User::factory(),
            'no_induk' => (string) fake()->unique()->numerify('1445####'),
            'nik' => fake()->numerify('3529############'),
            'kk' => fake()->numerify('3529############'),
            'jenis_kelamin' => $gender,
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->dateTimeBetween('-18 years', '-12 years')->format('Y-m-d'),
            'status' => 'Santri Aktif',
            'tahun_masuk' => "{$year}-07-15",
            'tahun_masuk_hijriyah' => '144'.($year - 2020).'-01-01',
            'whatsapp' => (int) ('628'.fake()->numerify('#########')),
            'foto' => 'santri.png',
        ];
    }
}
