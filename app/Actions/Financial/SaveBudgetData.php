<?php

declare(strict_types=1);

namespace App\Actions\Financial;

/**
 * Typed input for {@see SaveBudget}.
 */
final readonly class SaveBudgetData
{
    /**
     * @param  list<array{procedure_id: int|null, name: string, unit_price: float|string, quantity: int|string, discount: float|string}>  $items
     */
    public function __construct(
        public int $patientId,
        public ?string $notes,
        public array $items,
        public ?int $id = null,
    ) {}
}
