<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => null,
            'items' => [
                ['drug' => fake()->word().' '.fake()->randomElement(['250mg', '500mg', '20mg']), 'dose' => '1 comp', 'frequency' => '8/8h', 'duration' => '7 dias'],
            ],
            'notes' => fake()->optional()->sentence(),
            'issued_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
