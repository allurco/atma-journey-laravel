<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Models\WaitlistContact;
use Illuminate\Support\Facades\Auth;

/**
 * Records a contact attempt with a waiting patient — who reached out, how, when,
 * and what happened — building the chase history on the entry.
 */
class LogWaitlistContact
{
    public function __invoke(LogWaitlistContactData $data): WaitlistContact
    {
        return WaitlistContact::create([
            'waitlist_entry_id' => $data->waitlistEntryId,
            'user_id' => Auth::id(),
            'channel' => $data->channel,
            'note' => $data->note,
            'contacted_at' => now(),
        ]);
    }
}
