<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PatientStatus;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->numerify('(##) #####-####'),
            'email' => fake()->safeEmail(),
            'cpf' => fake()->unique()->numerify('###.###.###-##'),
            'birth_date' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'address' => fake()->streetAddress(),
            'status' => PatientStatus::Ativo,
            'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-', null]),
            'allergies' => fake()->randomElement([[], ['Dipirona'], ['Penicilina', 'Látex']]),
            'lead_source' => fake()->randomElement(['website', 'meta', 'google', 'referral', 'manual', null]),
        ];
    }

    public function lead(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => PatientStatus::Lead]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => PatientStatus::Inativo]);
    }
}
