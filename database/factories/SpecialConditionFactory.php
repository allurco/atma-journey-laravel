<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SpecialCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpecialCondition>
 */
class SpecialConditionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'PCD', 'Idoso', 'Criança', 'Autista', 'Gestante',
                'Mobilidade reduzida', 'Imunossuprimido', 'Outros cuidados especiais',
            ]),
            'active' => true,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => ['active' => false]);
    }
}
