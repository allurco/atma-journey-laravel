<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProcedureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Procedure extends Model
{
    /** @use HasFactory<ProcedureFactory> */
    use HasFactory;

    protected $fillable = ['name', 'base_price', 'duration', 'category', 'active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'duration' => 'integer',
            'active' => 'boolean',
        ];
    }
}
