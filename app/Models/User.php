<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property UserRole $role
 * @property bool $active
 * @property Carbon|null $invited_at
 * @property string|null $invitation_token
 * @property Carbon|null $invitation_accepted_at
 */
#[Fillable(['name', 'email', 'password', 'role', 'active', 'doctor_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * In-memory defaults so `role`/`active` are never null before the DB defaults
     * load (the role-sync hook + the active-user middleware rely on them).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'staff',
        'active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'active' => 'boolean',
            'invited_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
        ];
    }

    /** How long an invitation stays valid. */
    public const int INVITATION_TTL_DAYS = 7;

    /**
     * Issue (or reissue) an invitation token. Stores its hash and returns the
     * plaintext for the e-mailed link.
     */
    public function generateInvitationToken(): string
    {
        $plain = Str::random(48);

        $this->forceFill([
            'invited_at' => Carbon::now(),
            'invitation_token' => hash('sha256', $plain),
            'invitation_accepted_at' => null,
        ])->save();

        return $plain;
    }

    public static function findByInvitationToken(string $plain): ?self
    {
        return static::query()->where('invitation_token', hash('sha256', $plain))->first();
    }

    public function invitationStatus(): InvitationStatus
    {
        return match (true) {
            $this->invitation_accepted_at !== null => InvitationStatus::Accepted,
            $this->invited_at === null => InvitationStatus::NotInvited,
            $this->invited_at->addDays(self::INVITATION_TTL_DAYS)->isPast() => InvitationStatus::Expired,
            default => InvitationStatus::Pending,
        };
    }

    public function isInvitationPending(): bool
    {
        return $this->invitationStatus() === InvitationStatus::Pending;
    }

    /**
     * Accept the invitation: set the password, activate, and burn the token.
     */
    public function acceptInvitation(string $password): void
    {
        $this->forceFill([
            'password' => Hash::make($password),
            'active' => true,
            'email_verified_at' => Carbon::now(),
            'invitation_accepted_at' => Carbon::now(),
            'invitation_token' => null,
        ])->save();
    }

    /**
     * Keep the spatie role in lock-step with the `role` column (one role per user),
     * so permission checks (`$user->can(...)`) resolve from the assigned role.
     */
    protected static function booted(): void
    {
        static::saved(function (User $user): void {
            if ($user->wasRecentlyCreated || $user->wasChanged('role')) {
                $user->syncRoles([$user->role->value]);
            }
        });
    }

    /**
     * Whether the user is a clinic admin (full settings access).
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Whether the user is a practitioner who logs in for clinical work.
     */
    public function isDoctor(): bool
    {
        return $this->role === UserRole::Doctor;
    }

    /**
     * The practitioner record this user logs in as (when role is `doctor`).
     *
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
