<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentCategory;
use App\Models\Patient;
use App\Models\PatientDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientDocument>
 */
class PatientDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'category' => fake()->randomElement(DocumentCategory::cases()),
            'title' => fake()->sentence(3),
            'file_path' => 'patient-documents/'.fake()->uuid().'.pdf',
            'mime' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 2_000_000),
            'uploaded_by' => null,
            'uploaded_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
