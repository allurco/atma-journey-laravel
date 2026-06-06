<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'total' => fake()->randomFloat(2, 200, 12000),
            'status' => BudgetStatus::Draft,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function status(BudgetStatus $status): static
    {
        return $this->state(fn (array $attributes): array => ['status' => $status]);
    }
}
