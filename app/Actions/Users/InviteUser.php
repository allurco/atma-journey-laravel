<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Mail\InvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Invites a user (any role) to access the clinic: creates a pending, inactive
 * account with no usable password, then e-mails an invitation link they use to
 * set a password and activate. Reused by the Médicos and Equipe screens.
 */
final class InviteUser
{
    public function __invoke(InviteUserData $data): User
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'role' => $data->role,
            'doctor_id' => $data->doctorId,
            'active' => false,
            // Placeholder — replaced when the invitation is accepted.
            'password' => Hash::make(Str::random(40)),
        ]);

        $this->send($user);

        return $user;
    }

    /**
     * Issue a fresh token and e-mail the invitation (also used for "resend").
     */
    public function send(User $user): void
    {
        $token = $user->generateInvitationToken();

        Mail::to($user->email)->send(new InvitationMail($user, $token));
    }
}
