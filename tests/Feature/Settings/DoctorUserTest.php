<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Doctor;
use App\Models\User;

test('a doctor user carries the doctor role with clinical permissions only', function () {
    $doctor = Doctor::factory()->create();
    $user = User::factory()->create(['role' => UserRole::Doctor, 'doctor_id' => $doctor->id]);

    expect($user->hasRole('doctor'))->toBeTrue()
        ->and($user->can('manage-patients'))->toBeTrue()
        ->and($user->can('manage-scheduling'))->toBeTrue()
        ->and($user->can('manage-financial'))->toBeFalse()
        ->and($user->can('manage-clinic-settings'))->toBeFalse()
        ->and($user->can('manage-users'))->toBeFalse();
});

test('a doctor user is linked to its doctor record', function () {
    $doctor = Doctor::factory()->create(['name' => 'Dra. House']);
    $user = User::factory()->create(['role' => UserRole::Doctor, 'doctor_id' => $doctor->id]);

    expect($user->doctor)->not->toBeNull()
        ->and($user->doctor->name)->toBe('Dra. House')
        ->and($user->isDoctor())->toBeTrue();
});

test('the doctor factory state links a doctor and sets the role', function () {
    $user = User::factory()->doctor()->create();

    expect($user->role)->toBe(UserRole::Doctor)
        ->and($user->doctor)->not->toBeNull();
});
