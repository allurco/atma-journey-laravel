<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Anamnesis;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Anamnesis>
 */
class AnamnesisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'chief_complaint' => fake()->sentence(),
            'history' => fake()->paragraph(),
            'medications' => fake()->randomElement(['Nenhum', 'Losartana 50mg', 'Omeprazol 20mg']),
            'family_history' => fake()->sentence(),
            'lifestyle' => null,
            'updated_by' => null,
        ];
    }
}
