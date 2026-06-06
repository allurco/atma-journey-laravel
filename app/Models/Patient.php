<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PatientStatus;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property PatientStatus $status
 * @property array<int, string>|null $allergies
 * @property Carbon|null $birth_date
 * @property Carbon|null $last_visit_date
 * @property Carbon|null $first_visit_date
 * @property int $total_appointments
 * @property int $missed_appointments
 */
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    /**
     * Editable fields. The denormalized rollups (ltv, visit counters/dates) are
     * written by later PRDs (Scheduling/Financial), never mass-assigned here; the CPF
     * blind-index columns (cpf_last4, cpf_hash) are derived in the saving hook.
     */
    protected $fillable = [
        'name', 'phone', 'email', 'cpf', 'birth_date', 'address',
        'photo_path', 'status', 'blood_type', 'allergies', 'lead_source',
    ];

    protected static function booted(): void
    {
        // Keep the plaintext last-4 + the deterministic blind index in sync with the
        // encrypted CPF so the registry can display, search, and de-duplicate it.
        static::saving(function (Patient $patient): void {
            $digits = $patient->cpfDigits();
            $patient->cpf_last4 = $digits !== '' ? substr($digits, -4) : null;
            $patient->cpf_hash = $digits !== '' ? self::hashCpf($digits) : null;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cpf' => 'encrypted',
            'status' => PatientStatus::class,
            'allergies' => 'array',
            'birth_date' => 'date',
            'last_visit_date' => 'date',
            'first_visit_date' => 'date',
            'ltv' => 'decimal:2',
        ];
    }

    /**
     * Deterministic blind index of a CPF's digits — used for per-tenant uniqueness and
     * existence checks, since the encrypted value can't be matched in a query.
     */
    public static function hashCpf(string $cpf): string
    {
        $digits = (string) preg_replace('/\D/', '', $cpf);

        return hash_hmac('sha256', $digits, (string) config('app.key'));
    }

    public function cpfDigits(): string
    {
        return (string) preg_replace('/\D/', '', (string) ($this->cpf ?? ''));
    }

    public function maskedCpf(): ?string
    {
        return $this->cpf_last4 !== null ? '•••.•••.•••-'.$this->cpf_last4 : null;
    }

    /**
     * The append-only activity log for this patient, newest first.
     *
     * @return HasMany<TimelineEvent, $this>
     */
    public function timelineEvents(): HasMany
    {
        return $this->hasMany(TimelineEvent::class)->latest('occurred_at');
    }

    /**
     * URL to the tenant-scoped photo route, or null when no photo is set.
     */
    public function photoUrl(): ?string
    {
        return $this->photo_path !== null ? route('pacientes.foto', $this) : null;
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => mb_substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Active patients whose last visit is older than $months — the recall list the
     * Dashboard (PRD-9) consumes.
     *
     * @param  Builder<Patient>  $query
     */
    public function scopeNeedingRecall(Builder $query, int $months = 6): void
    {
        $query->where('status', PatientStatus::Ativo)
            ->whereNotNull('last_visit_date')
            ->where('last_visit_date', '<=', Carbon::now()->subMonths($months));
    }
}
