<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TimelineEventType;
use App\Models\Patient;
use App\Models\TimelineEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimelineEvent>
 */
class TimelineEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'type' => fake()->randomElement(TimelineEventType::cases()),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-1 year'),
        ];
    }
}
