<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Procedure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Procedure>
 */
class ProcedureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Limpeza de Pele', 'Preenchimento', 'Toxina Botulínica',
                'Microagulhamento', 'Peeling Químico', 'Laser CO2',
                'Drenagem Linfática', 'Avaliação Inicial',
            ]),
            'base_price' => fake()->randomFloat(2, 80, 1200),
            'duration' => fake()->randomElement([30, 45, 60, 90]),
            'category' => fake()->randomElement(['Facial', 'Corporal', 'Avaliação', null]),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['active' => false]);
    }
}
