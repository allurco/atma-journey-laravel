<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExamFindingFlag;
use App\Models\ExamFinding;
use App\Models\ExamResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamFinding>
 */
class ExamFindingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_result_id' => ExamResult::factory(),
            'label' => fake()->randomElement(['Hemoglobina', 'Glicose', 'Colesterol total', 'Leucócitos']),
            'value' => (string) fake()->numberBetween(1, 300),
            'unit' => fake()->randomElement(['g/dL', 'mg/dL', 'mil/mm³']),
            'reference_range' => '12-16',
            'flag' => fake()->randomElement(ExamFindingFlag::cases()),
        ];
    }
}
