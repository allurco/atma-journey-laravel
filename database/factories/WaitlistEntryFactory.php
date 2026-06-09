<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WaitlistPeriod;
use App\Enums\WaitlistPriority;
use App\Enums\WaitlistStatus;
use App\Models\Patient;
use App\Models\WaitlistEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitlistEntry>
 */
class WaitlistEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => null,
            'procedure_id' => null,
            'service_type' => null,
            'unit' => null,
            'preferred_period' => fake()->randomElement(WaitlistPeriod::cases()),
            'priority' => fake()->randomElement(WaitlistPriority::cases()),
            'status' => WaitlistStatus::Aguardando,
            'notes' => null,
        ];
    }
}
