<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Enums\WaitlistStatus;
use App\Models\WaitlistEntry;

/**
 * Marks an open entry as `chamado` — the front desk has reached out about an
 * opening. A no-op on entries already scheduled or cancelled.
 */
class CallWaitlistEntry
{
    public function __invoke(WaitlistEntry $entry): void
    {
        if (! in_array($entry->status, [WaitlistStatus::Aguardando, WaitlistStatus::Chamado], true)) {
            return;
        }

        $entry->update(['status' => WaitlistStatus::Chamado]);
    }
}
