<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\DoctorShift;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<DoctorShift>
 */
class DoctorShiftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'date' => Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'unit' => null,
        ];
    }
}
