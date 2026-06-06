<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A recorded domain event — the funnel instrumentation (stage changes, budget
 * approvals, no-shows…) the dashboard/analytics read. Per tenant.
 *
 * @property array<string, mixed>|null $payload
 */
class Metric extends Model
{
    protected $fillable = ['type', 'payload'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function record(string $type, array $payload = []): self
    {
        return self::create(['type' => $type, 'payload' => $payload]);
    }
}
