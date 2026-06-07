<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClinicalNote;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicalNote>
 */
class ClinicalNoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => null,
            'appointment_id' => null,
            'content' => fake()->paragraph(),
            'occurred_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
