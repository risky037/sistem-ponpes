<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\LearningMaterial;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LearningMaterial>
 */
class LearningMaterialFactory extends Factory
{
    protected $model = LearningMaterial::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'teacher_id' => User::factory(),
            'mapel_id' => Mapel::factory(),
            'kelas_id' => Kelas::factory(),
            'academic_year_id' => fn () => AcademicYear::first()?->id ?? AcademicYear::factory()->create()->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->paragraph(),
            'content_type' => LearningMaterial::CONTENT_TYPE_TEXT,
            'content' => fake()->paragraphs(3, true),
            'video_url' => null,
            'external_url' => null,
            'status' => LearningMaterial::STATUS_PUBLISHED,
            'published_at' => now(),
            'created_by' => fn (array $attributes) => $attributes['teacher_id'],
        ];
    }

    /**
     * Mark material as draft.
     */
    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => LearningMaterial::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    /**
     * Mark material as published.
     */
    public function published(): static
    {
        return $this->state(fn () => [
            'status' => LearningMaterial::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Mark material as archived.
     */
    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => LearningMaterial::STATUS_ARCHIVED,
        ]);
    }

    /**
     * Material with PDF content type.
     */
    public function pdf(): static
    {
        return $this->state(fn () => [
            'content_type' => LearningMaterial::CONTENT_TYPE_PDF,
        ]);
    }

    /**
     * Material with Video content type.
     */
    public function video(): static
    {
        return $this->state(fn () => [
            'content_type' => LearningMaterial::CONTENT_TYPE_VIDEO,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
    }
}
