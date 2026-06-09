<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactType;
use Database\Factories\WaitlistContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A logged contact attempt with a waiting patient — the chase history that keeps
 * the front desk from dropping someone in the queue.
 *
 * @property ?ContactType $channel
 * @property Carbon $contacted_at
 */
class WaitlistContact extends Model
{
    /** @use HasFactory<WaitlistContactFactory> */
    use HasFactory;

    protected $fillable = ['waitlist_entry_id', 'user_id', 'channel', 'note', 'contacted_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => ContactType::class,
            'contacted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WaitlistEntry, $this>
     */
    public function waitlistEntry(): BelongsTo
    {
        return $this->belongsTo(WaitlistEntry::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
