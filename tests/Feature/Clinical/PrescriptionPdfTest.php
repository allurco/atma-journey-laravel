<?php

declare(strict_types=1);

use App\Livewire\Settings\ClinicProfile;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Livewire\Livewire;

test('the prescription pdf route returns a pdf', function () {
    $this->actingAs(User::factory()->admin()->create());
    $doctor = Doctor::factory()->create();
    $prescription = Prescription::factory()->for(Patient::factory())->create([
        'doctor_id' => $doctor->id,
        'items' => [['drug' => 'Dipirona 500mg', 'dose' => '1 comp', 'frequency' => '6/6h', 'duration' => '3 dias']],
    ]);

    $response = $this->get(route('prescriptions.pdf', $prescription));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('a guest cannot access the prescription pdf', function () {
    $prescription = Prescription::factory()->for(Patient::factory())->create();

    $this->get(route('prescriptions.pdf', $prescription))->assertRedirect(route('login'));
});

test('the prescription pdf shows the clinic letterhead and prescriber by default', function () {
    $clinic = Clinic::current();
    $clinic->update(['name' => 'Clínica Atma Teste', 'uses_custom_prescription_paper' => false]);
    $doctor = Doctor::factory()->create(['name' => 'Dra. Marina', 'crm' => '123456']);
    $prescription = Prescription::factory()->for(Patient::factory())->create([
        'doctor_id' => $doctor->id,
        'items' => [['drug' => 'Amoxicilina 500mg', 'dose' => '', 'frequency' => '', 'duration' => '']],
    ]);

    $html = view('pdf.prescription', [
        'prescription' => $prescription->load('doctor', 'patient'),
        'clinic' => $clinic,
    ])->render();

    expect($html)->toContain('Clínica Atma Teste')   // generated letterhead
        ->and($html)->toContain('123456')             // prescriber CRM
        ->and($html)->toContain('Amoxicilina 500mg'); // body
});

test('with custom prescription paper the generated letterhead is omitted', function () {
    $clinic = Clinic::current();
    $clinic->update(['name' => 'Clínica Atma Teste', 'uses_custom_prescription_paper' => true]);
    $doctor = Doctor::factory()->create(['name' => 'Dra. Marina', 'crm' => '123456']);
    $prescription = Prescription::factory()->for(Patient::factory())->create([
        'doctor_id' => $doctor->id,
        'items' => [['drug' => 'Amoxicilina 500mg', 'dose' => '', 'frequency' => '', 'duration' => '']],
    ]);

    $html = view('pdf.prescription', [
        'prescription' => $prescription->load('doctor', 'patient'),
        'clinic' => $clinic,
    ])->render();

    expect($html)->not->toContain('Clínica Atma Teste')  // letterhead suppressed for pre-printed paper
        ->and($html)->toContain('Amoxicilina 500mg');     // body still present
});

test('the clinic profile saves the custom-paper setting', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ClinicProfile::class)
        ->set('name', 'Clínica X')
        ->set('usesCustomPrescriptionPaper', true)
        ->set('prescriptionHeaderMarginMm', 40)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('clinics', [
        'uses_custom_prescription_paper' => true,
        'prescription_header_margin_mm' => 40,
    ]);
});
