<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactType;
use App\Enums\PipelineStage;
use App\Models\Patient;
use App\Models\PipelineCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PipelineCard>
 */
class PipelineCardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'stage' => fake()->randomElement(PipelineStage::cases()),
            'treatment' => fake()->randomElement(['Implante unitário', 'Clareamento', 'Ortodontia', 'Prótese', 'Limpeza']),
            'value' => fake()->randomFloat(2, 200, 12000),
            'last_contact' => fake()->dateTimeBetween('-3 months'),
            'contact_type' => fake()->randomElement(ContactType::cases()),
            'budget_id' => null,
        ];
    }

    public function inStage(PipelineStage $stage): static
    {
        return $this->state(fn (array $attributes): array => ['stage' => $stage]);
    }
}
