<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2000, 2099);
        $semester = fake()->randomElement(['Ganjil', 'Genap']);
        $nextYear = $year + 1;

        if ($semester === 'Ganjil') {
            $startDate = "{$year}-07-15";
            $endDate = "{$year}-12-20";
        } else {
            $startDate = "{$nextYear}-01-06";
            $endDate = "{$nextYear}-06-20";
        }

        return [
            'name' => "{$year}/{$nextYear}",
            'semester' => $semester,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => false,
        ];
    }
}
