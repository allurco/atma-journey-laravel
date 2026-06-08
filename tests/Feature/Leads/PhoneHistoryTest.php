<?php

declare(strict_types=1);

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientPhoneHistory;

test('creating a patient seeds a registration phone-history row', function () {
    $patient = Patient::factory()->create(['phone' => '(11) 91111-1111']);

    $this->assertDatabaseHas('patient_phone_history', [
        'patient_id' => $patient->id,
        'phone' => '+5511911111111',
        'source' => 'registration',
    ]);
});

test('editing a patient phone appends a history row and a timeline event', function () {
    $patient = Patient::factory()->create(['phone' => '(11) 91111-1111']);
    expect(PatientPhoneHistory::where('patient_id', $patient->id)->count())->toBe(1);

    $patient->update(['phone' => '(11) 92222-2222']);

    expect(PatientPhoneHistory::where('patient_id', $patient->id)->count())->toBe(2)
        ->and($patient->refresh()->phone)->toBe('(11) 92222-2222'); // current number stays on the patient

    $this->assertDatabaseHas('patient_phone_history', [
        'patient_id' => $patient->id,
        'phone' => '+5511922222222',
        'source' => 'edit',
    ]);
    $this->assertDatabaseHas('timeline_events', [
        'patient_id' => $patient->id,
        'title' => 'Telefone alterado',
    ]);
});

test('reformatting a phone to the same number does not append history', function () {
    $patient = Patient::factory()->create(['phone' => '(11) 91111-1111']);

    $patient->update(['phone' => '11911111111']); // same E.164, just reformatted

    expect(PatientPhoneHistory::where('patient_id', $patient->id)->count())->toBe(1);
});

test('a webhook lead seeds its phone history with the lead source', function () {
    $secret = Clinic::current()->webhookSecret();

    $this->withHeaders(['X-Webhook-Secret' => $secret])
        ->postJson(route('webhooks.leads'), ['name' => 'Lead', 'phone' => '11911111111', 'source' => 'meta'])
        ->assertSuccessful();

    $patient = Patient::where('phone_e164', '+5511911111111')->first();
    $this->assertDatabaseHas('patient_phone_history', [
        'patient_id' => $patient->id,
        'source' => 'lead',
    ]);
});

test('a lead matching a patient OLD phone attaches to that patient with a note', function () {
    $secret = Clinic::current()->webhookSecret();
    $patient = Patient::factory()->create(['phone' => '(11) 91111-1111']);
    $patient->update(['phone' => '(11) 92222-2222']); // old number now only in history

    $this->withHeaders(['X-Webhook-Secret' => $secret])
        ->postJson(route('webhooks.leads'), ['name' => 'De novo', 'phone' => '11911111111', 'source' => 'meta'])
        ->assertSuccessful();

    expect(Patient::count())->toBe(1); // attached, not duplicated

    $this->assertDatabaseHas('timeline_events', [
        'patient_id' => $patient->id,
        'title' => 'Possível correspondência por telefone antigo',
    ]);
});

test('a current-phone match still wins without the old-phone note', function () {
    $secret = Clinic::current()->webhookSecret();
    $patient = Patient::factory()->create(['phone' => '(11) 91111-1111', 'email' => null]);

    $this->withHeaders(['X-Webhook-Secret' => $secret])
        ->postJson(route('webhooks.leads'), ['name' => 'X', 'phone' => '11911111111', 'email' => 'x@example.com', 'source' => 'meta'])
        ->assertSuccessful();

    expect(Patient::count())->toBe(1)
        ->and($patient->refresh()->email)->toBe('x@example.com');

    $this->assertDatabaseMissing('timeline_events', [
        'patient_id' => $patient->id,
        'title' => 'Possível correspondência por telefone antigo',
    ]);
});
