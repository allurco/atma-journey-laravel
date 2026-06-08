<?php

declare(strict_types=1);

use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Livewire\Settings\Team;
use App\Mail\InvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('an admin holds every permission and staff is limited', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create();

    expect($admin->can('manage-users'))->toBeTrue()
        ->and($admin->can('manage-clinic-settings'))->toBeTrue()
        ->and($admin->can('manage-financial'))->toBeTrue()
        ->and($staff->can('manage-patients'))->toBeTrue()
        ->and($staff->can('manage-scheduling'))->toBeTrue()
        ->and($staff->can('manage-financial'))->toBeTrue()
        ->and($staff->can('manage-users'))->toBeFalse()
        ->and($staff->can('manage-clinic-settings'))->toBeFalse();
});

test('a created user is assigned the spatie role matching its column', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create();

    expect($admin->hasRole('admin'))->toBeTrue()
        ->and($staff->hasRole('staff'))->toBeTrue();
});

test('an admin invites a staff user (pending until they accept)', function () {
    Mail::fake();
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Team::class)
        ->call('create')
        ->set('name', 'Recepção')
        ->set('email', 'recepcao@clinica.test')
        ->set('role', UserRole::Staff->value)
        ->call('save')
        ->assertHasNoErrors();

    $user = User::firstWhere('email', 'recepcao@clinica.test');
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Staff)
        ->and($user->active)->toBeFalse()
        ->and($user->invitationStatus())->toBe(InvitationStatus::Pending)
        ->and($user->can('manage-patients'))->toBeTrue()
        ->and($user->can('manage-users'))->toBeFalse();
    Mail::assertSent(InvitationMail::class, fn (InvitationMail $mail): bool => $mail->hasTo('recepcao@clinica.test'));
});

test('the team list shows a pending invite with resend and revoke', function () {
    Mail::fake();
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Team::class)
        ->call('create')->set('name', 'Nova')->set('email', 'nova@clinica.test')->set('role', 'staff')->call('save');
    $invited = User::firstWhere('email', 'nova@clinica.test');

    Livewire::test(Team::class)
        ->assertSee('Convite enviado')
        ->call('resendInvitation', $invited->id);
    Mail::assertSent(InvitationMail::class, 2);

    Livewire::test(Team::class)->call('revokeInvitation', $invited->id);
    expect(User::find($invited->id))->toBeNull();
});

test('staff cannot manage the team', function () {
    $this->actingAs(User::factory()->staff()->create());

    Livewire::test(Team::class)
        ->set('name', 'X')->set('email', 'x@y.test')
        ->call('save')
        ->assertForbidden();
});

test('staff cannot view the team page', function () {
    $this->actingAs(User::factory()->staff()->create());

    $this->get(route('equipe'))->assertForbidden();
});

test('an admin can view the team page', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('equipe'))->assertOk()->assertSee('Equipe');
});

test('the only admin cannot be demoted to staff', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(Team::class)
        ->call('edit', $admin->id)
        ->set('role', UserRole::Staff->value)
        ->call('save')
        ->assertHasErrors('role');

    expect($admin->refresh()->role)->toBe(UserRole::Admin);
});

test('the only admin cannot be deactivated', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(Team::class)->call('toggleActive', $admin->id);

    expect($admin->refresh()->active)->toBeTrue();
});

test('an admin can deactivate a staff user when another admin remains', function () {
    $this->actingAs(User::factory()->admin()->create());
    $staff = User::factory()->staff()->create();

    Livewire::test(Team::class)->call('toggleActive', $staff->id);

    expect($staff->refresh()->active)->toBeFalse();
});

test('a deactivated user is logged out of the app', function () {
    $user = User::factory()->create(['active' => false]);
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
