<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClinicFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    /** @use HasFactory<ClinicFactory> */
    use HasFactory;

    protected $fillable = ['name', 'cnpj', 'email', 'phone', 'address'];

    /**
     * The clinic is a per-tenant singleton. Return it, seeding one from the
     * central tenant name if the row doesn't exist yet (legacy tenants).
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['name' => (string) tenant('name')]);
    }
}
