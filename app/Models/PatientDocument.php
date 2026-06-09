<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentCategory;
use App\Enums\SignatureStatus;
use Database\Factories\PatientDocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An uploaded clinical document (exam, laudo, termo, image…) in the prontuário,
 * or a document sent to the patient for signature. A null signature_status marks
 * a plain upload; Pendente/Assinado documents carry the signature lifecycle.
 *
 * @property Carbon $uploaded_at
 * @property SignatureStatus|null $signature_status
 * @property Carbon|null $sent_at
 * @property Carbon|null $signed_at
 */
class PatientDocument extends Model
{
    /** @use HasFactory<PatientDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'patient_id', 'document_template_id', 'appointment_id', 'category', 'signature_status',
        'title', 'file_path', 'mime', 'size', 'uploaded_by', 'uploaded_at',
        'sent_at', 'signed_at', 'signature_token', 'signed_ip', 'signed_user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'signature_status' => SignatureStatus::class,
            'uploaded_at' => 'datetime',
            'sent_at' => 'datetime',
            'signed_at' => 'datetime',
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
     * The blank template this document was sent from, when applicable.
     *
     * @return BelongsTo<DocumentTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    /**
     * The consulta this document is linked to, when applicable.
     *
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Documents awaiting the patient's signature.
     *
     * @param  Builder<PatientDocument>  $query
     */
    public function scopeAwaitingSignature(Builder $query): void
    {
        $query->where('signature_status', SignatureStatus::Pending);
    }

    /**
     * Documents the patient has signed.
     *
     * @param  Builder<PatientDocument>  $query
     */
    public function scopeSigned(Builder $query): void
    {
        $query->where('signature_status', SignatureStatus::Signed);
    }

    /**
     * Plain uploaded files (exams, laudos…) — not part of the signature flow.
     *
     * @param  Builder<PatientDocument>  $query
     */
    public function scopeFiles(Builder $query): void
    {
        $query->whereNull('signature_status');
    }

    public function isAwaitingSignature(): bool
    {
        return $this->signature_status === SignatureStatus::Pending;
    }

    public function isSigned(): bool
    {
        return $this->signature_status === SignatureStatus::Signed;
    }

    /**
     * Issues a fresh single-use signing token: returns the plaintext (for the
     * public link), persists only its hash. Mirrors the invitation-token pattern.
     */
    public function generateSignatureToken(): string
    {
        $plain = Str::random(48);
        $this->signature_token = hash('sha256', $plain);

        return $plain;
    }

    /**
     * Resolves a document by its plaintext signing token, or null.
     */
    public static function findBySignatureToken(string $plain): ?self
    {
        return static::query()->where('signature_token', hash('sha256', $plain))->first();
    }

    /**
     * Records the patient's acceptance: flips to Assinado with the audit trail.
     */
    public function markSigned(?string $ip = null, ?string $userAgent = null): void
    {
        $this->forceFill([
            'signature_status' => SignatureStatus::Signed,
            'signed_at' => now(),
            'signed_ip' => $ip,
            'signed_user_agent' => $userAgent,
            'signature_token' => null,
        ])->save();
    }

    /**
     * URL to the auth-gated, tenant-scoped serving route.
     */
    public function fileUrl(): string
    {
        return route('documentos.arquivo', $this);
    }
}
