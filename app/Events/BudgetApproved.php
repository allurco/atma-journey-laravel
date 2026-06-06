<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Budget;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A budget was approved. Advances the linked pipeline card forward (PRD-5);
 * later PRDs may notify the patient (PRD-7).
 */
final class BudgetApproved
{
    use Dispatchable;

    public function __construct(public Budget $budget) {}
}
