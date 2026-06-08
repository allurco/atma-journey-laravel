<?php

declare(strict_types=1);

use App\Actions\Users\InviteUser;
use App\Actions\Users\InviteUserData;
use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Livewire\Auth\AcceptInvitation;
use App\Mail\InvitationMail;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('inviting a user creates a pending account and sends the invitation', function () {
    Mail::fake();
    $doctor = Doctor::factory()->create();

    $user = app(InviteUser::class)(new InviteUserData(
        name: 'Dra. Ana',
        email: 'ana@example.com',
        role: UserRole::Doctor,
        doctorId: $doctor->id,
    ));

    expect($user->active)->toBeFalse()
        ->and($user->role)->toBe(UserRole::Doctor)
        ->and($user->doctor_id)->toBe($doctor->id)
        ->and($user->invited_at)->not->toBeNull()
        ->and($user->invitation_accepted_at)->toBeNull()
        ->and($user->invitationStatus())->toBe(InvitationStatus::Pending);

    Mail::assertSent(InvitationMail::class, fn (InvitationMail $mail): bool => $mail->hasTo('ana@example.com'));
});

test('accepting an invitation sets a password and activates the account', function () {
    $user = User::factory()->create(['role' => UserRole::Doctor, 'active' => false]);
    $token = $user->generateInvitationToken();

    Livewire::test(AcceptInvitation::class, ['token' => $token])
        ->assertSet('valid', true)
        ->set('password', 'segredo-forte-123')
        ->set('passwordConfirmation', 'segredo-forte-123')
        ->call('accept')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->active)->toBeTrue()
        ->and($user->invitation_accepted_at)->not->toBeNull()
        ->and($user->invitation_token)->toBeNull()
        ->and(Hash::check('segredo-forte-123', $user->password))->toBeTrue()
        ->and($user->invitationStatus())->toBe(InvitationStatus::Accepted);
});

test('the invitation accept page is publicly reachable', function () {
    $user = User::factory()->create(['active' => false]);
    $token = $user->generateInvitationToken();

    $this->get(route('convite', $token))->assertOk();
});

test('the accept page rejects an unknown token', function () {
    Livewire::test(AcceptInvitation::class, ['token' => 'does-not-exist'])
        ->assertSet('valid', false);
});

test('an expired invitation cannot be accepted', function () {
    $user = User::factory()->create(['role' => UserRole::Staff, 'active' => false]);
    $token = $user->generateInvitationToken();
    $user->forceFill(['invited_at' => now()->subDays(8)])->save();

    expect($user->invitationStatus())->toBe(InvitationStatus::Expired);

    Livewire::test(AcceptInvitation::class, ['token' => $token])
        ->assertSet('valid', false);
});

test('an already-accepted token no longer works', function () {
    $user = User::factory()->create(['role' => UserRole::Staff, 'active' => false]);
    $token = $user->generateInvitationToken();
    $user->acceptInvitation('first-password-123');

    Livewire::test(AcceptInvitation::class, ['token' => $token])
        ->assertSet('valid', false);
});
