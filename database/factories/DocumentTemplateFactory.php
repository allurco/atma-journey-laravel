<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentCategory;
use App\Models\DocumentTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentTemplate>
 */
class DocumentTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Contrato de prestação de serviços', 'Termo de consentimento',
                'Questionário de saúde', 'Termo de responsabilidade',
            ]),
            'category' => fake()->randomElement([
                DocumentCategory::Contract, DocumentCategory::Consent, DocumentCategory::Questionnaire,
            ]),
            'file_path' => 'document-templates/'.fake()->uuid().'.pdf',
            'mime' => 'application/pdf',
            'size' => fake()->numberBetween(20_000, 1_000_000),
            'active' => true,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => ['active' => false]);
    }
}
