<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\WaitlistEntry;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A waiting patient left the queue without being scheduled — demand lost. Recorded
 * into the metrics table so the fila's conversion rate can be measured against it.
 */
final class WaitlistEntryCancelled
{
    use Dispatchable;

    public function __construct(public WaitlistEntry $entry) {}
}
