<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExamResult;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamResult>
 */
class ExamResultFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'patient_document_id' => null,
            'exam_type' => fake()->randomElement(['Hemograma', 'Glicemia', 'Colesterol', 'Urina']),
            'collected_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'source' => 'manual',
        ];
    }
}
