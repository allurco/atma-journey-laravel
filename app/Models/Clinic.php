<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClinicFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Clinic extends Model
{
    /** @use HasFactory<ClinicFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'logo_path', 'cnpj', 'email', 'phone', 'address',
        'uses_custom_prescription_paper', 'prescription_header_margin_mm',
        'webhook_secret',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uses_custom_prescription_paper' => 'boolean',
        ];
    }

    /**
     * The clinic is a per-tenant singleton. Return it, seeding one from the
     * central tenant name if the row doesn't exist yet (legacy tenants).
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['name' => (string) tenant('name')]);
    }

    /**
     * URL to the tenant-scoped logo route, or null when no logo is set.
     */
    public function logoUrl(): ?string
    {
        return $this->logo_path !== null ? route('clinica.logo') : null;
    }

    /**
     * The clinic's lead-webhook secret, generated on first use.
     */
    public function webhookSecret(): string
    {
        if ($this->webhook_secret === null) {
            $this->regenerateWebhookSecret();
        }

        return (string) $this->webhook_secret;
    }

    /**
     * Rotate the webhook secret — the old one stops authenticating immediately.
     */
    public function regenerateWebhookSecret(): string
    {
        $this->forceFill(['webhook_secret' => Str::random(48)])->save();

        return (string) $this->webhook_secret;
    }
}
