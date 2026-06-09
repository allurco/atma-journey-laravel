<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactType;
use App\Models\WaitlistContact;
use App\Models\WaitlistEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitlistContact>
 */
class WaitlistContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'waitlist_entry_id' => WaitlistEntry::factory(),
            'user_id' => null,
            'channel' => fake()->randomElement(ContactType::cases()),
            'note' => fake()->sentence(),
            'contacted_at' => now(),
        ];
    }
}
