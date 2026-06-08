<?php

declare(strict_types=1);

use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Livewire\Settings\Doctors;
use App\Mail\InvitationMail;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('an admin invites a doctor, creating a pending doctor-user and sending the mail', function () {
    Mail::fake();
    $this->actingAs(User::factory()->admin()->create());
    $doctor = Doctor::factory()->create(['name' => 'Dra. Ana', 'email' => 'ana@example.com']);

    Livewire::test(Doctors::class)->call('inviteDoctor', $doctor->id);

    $user = User::where('doctor_id', $doctor->id)->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Doctor)
        ->and($user->active)->toBeFalse()
        ->and($user->invitationStatus())->toBe(InvitationStatus::Pending);
    Mail::assertSent(InvitationMail::class, fn (InvitationMail $mail): bool => $mail->hasTo('ana@example.com'));
});

test('the medicos list shows the invitation status after inviting', function () {
    Mail::fake();
    $this->actingAs(User::factory()->admin()->create());
    $doctor = Doctor::factory()->create(['email' => 'x@example.com']);

    Livewire::test(Doctors::class)
        ->call('inviteDoctor', $doctor->id)
        ->assertSee('Convite enviado');
});

test('inviting again resends the invitation without duplicating the user', function () {
    Mail::fake();
    $this->actingAs(User::factory()->admin()->create());
    $doctor = Doctor::factory()->create(['email' => 'x@example.com']);

    Livewire::test(Doctors::class)
        ->call('inviteDoctor', $doctor->id)
        ->call('inviteDoctor', $doctor->id);

    expect(User::where('doctor_id', $doctor->id)->count())->toBe(1);
    Mail::assertSent(InvitationMail::class, 2);
});

test('revoking removes the pending doctor-user', function () {
    Mail::fake();
    $this->actingAs(User::factory()->admin()->create());
    $doctor = Doctor::factory()->create(['email' => 'x@example.com']);

    Livewire::test(Doctors::class)
        ->call('inviteDoctor', $doctor->id)
        ->call('revokeInvitation', $doctor->id);

    expect(User::where('doctor_id', $doctor->id)->count())->toBe(0);
});

test('a doctor without an e-mail cannot be invited', function () {
    Mail::fake();
    $this->actingAs(User::factory()->admin()->create());
    $doctor = Doctor::factory()->create(['email' => null]);

    Livewire::test(Doctors::class)->call('inviteDoctor', $doctor->id);

    expect(User::where('doctor_id', $doctor->id)->count())->toBe(0);
    Mail::assertNothingSent();
});
