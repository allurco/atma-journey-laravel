<?php

declare(strict_types=1);

use App\Actions\Settings\SaveDoctor;
use App\Actions\Settings\SaveDoctorData;
use App\Models\Doctor;
use App\Models\Specialty;

test('it creates a doctor and syncs specialties', function () {
    $specialties = Specialty::factory()->count(2)->create();

    $doctor = (new SaveDoctor)(new SaveDoctorData(
        name: 'Dra. Ana Souza',
        crm: 'CRM/SP 123456',
        phone: '(11) 99999-0000',
        email: 'ana@clinica.test',
        specialtyIds: $specialties->pluck('id')->all(),
    ));

    expect($doctor->name)->toBe('Dra. Ana Souza')
        ->and($doctor->crm)->toBe('CRM/SP 123456')
        ->and($doctor->specialties)->toHaveCount(2);
});

test('it updates an existing doctor and re-syncs specialties', function () {
    [$first, $second, $third] = Specialty::factory()->count(3)->create()->all();

    $doctor = Doctor::factory()->create(['name' => 'Antigo']);
    $doctor->specialties()->sync([$first->id]);

    (new SaveDoctor)(new SaveDoctorData(
        name: 'Novo Nome',
        crm: 'CRM/SP 654321',
        phone: null,
        email: null,
        specialtyIds: [$second->id, $third->id],
        id: $doctor->id,
    ));

    $doctor->refresh();
    expect(Doctor::count())->toBe(1)
        ->and($doctor->name)->toBe('Novo Nome')
        ->and($doctor->specialties->pluck('id')->sort()->values()->all())
        ->toBe(collect([$second->id, $third->id])->sort()->values()->all());
});
