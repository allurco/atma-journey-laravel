<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentCategory;
use Database\Factories\PatientDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An uploaded clinical document (exam, laudo, termo, image…) in the prontuário.
 *
 * @property Carbon $uploaded_at
 */
class PatientDocument extends Model
{
    /** @use HasFactory<PatientDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'patient_id', 'category', 'title', 'file_path', 'mime', 'size', 'uploaded_by', 'uploaded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'uploaded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * URL to the auth-gated, tenant-scoped serving route.
     */
    public function fileUrl(): string
    {
        return route('documentos.arquivo', $this);
    }
}
