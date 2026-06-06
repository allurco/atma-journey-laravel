<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hour = fake()->numberBetween(8, 17);

        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => null,
            'procedure_id' => null,
            'service_type' => fake()->randomElement(['Consulta', 'Fisioterapia', 'Retorno', 'Exame']),
            'date' => fake()->dateTimeBetween('now', '+2 weeks')->format('Y-m-d'),
            'start_time' => sprintf('%02d:00', $hour),
            'end_time' => sprintf('%02d:00', $hour + 1),
            'status' => AppointmentStatus::Scheduled,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => AppointmentStatus::Cancelled]);
    }

    public function status(AppointmentStatus $status): static
    {
        return $this->state(fn (array $attributes): array => ['status' => $status]);
    }
}
