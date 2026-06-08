<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentCategory;
use Database\Factories\DocumentTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A clinic-maintained blank document (contrato, termo, questionário) the front
 * desk instantiates and sends to a patient for signature. The file lives on the
 * tenant private disk; archived (active = false) templates drop out of the send
 * picker but keep documents already sent from them intact.
 *
 * @property DocumentCategory $category
 * @property bool $active
 */
class DocumentTemplate extends Model
{
    /** @use HasFactory<DocumentTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'category', 'file_path', 'mime', 'size', 'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'active' => 'boolean',
        ];
    }

    /**
     * Templates offered in the send picker — archived ones are excluded.
     *
     * @param  Builder<DocumentTemplate>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * URL to the auth-gated, tenant-scoped serving route.
     */
    public function fileUrl(): string
    {
        return route('configuracoes.modelos.arquivo', $this);
    }
}
