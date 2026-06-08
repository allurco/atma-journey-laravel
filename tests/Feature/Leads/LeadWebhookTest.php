<?php

declare(strict_types=1);

use App\Enums\PatientStatus;
use App\Enums\PipelineStage;
use App\Events\LeadReceived;
use App\Models\Clinic;
use App\Models\Patient;
use Illuminate\Support\Facades\Event;

test('a valid lead creates a lead patient, a primeiro_contato card, a timeline event and fires LeadReceived', function () {
    Event::fake([LeadReceived::class]);
    $secret = Clinic::current()->webhookSecret();

    $this->withHeaders(['X-Webhook-Secret' => $secret])
        ->postJson(route('webhooks.leads'), [
            'name' => 'Maria Lead',
            'phone' => '(11) 98765-4321',
            'email' => 'maria@example.com',
            'source' => 'meta',
            'external_id' => 'meta-123',
        ])
        ->assertSuccessful();

    $patient = Patient::where('phone_e164', '+5511987654321')->first();
    expect($patient)->not->toBeNull()
        ->and($patient->name)->toBe('Maria Lead')
        ->and($patient->status)->toBe(PatientStatus::Lead)
        ->and($patient->lead_source)->toBe('meta');

    $this->assertDatabaseHas('pipeline_cards', [
        'patient_id' => $patient->id,
        'stage' => PipelineStage::PrimeiroContato->value,
    ]);
    $this->assertDatabaseHas('timeline_events', ['patient_id' => $patient->id]);
    $this->assertDatabaseHas('lead_ingestions', ['patient_id' => $patient->id, 'matched' => false]);
    Event::assertDispatched(LeadReceived::class);
});

test('a request with a bad secret is rejected', function () {
    Clinic::current()->webhookSecret();

    $this->withHeaders(['X-Webhook-Secret' => 'wrong-secret'])
        ->postJson(route('webhooks.leads'), ['name' => 'X', 'phone' => '11987654321'])
        ->assertStatus(401);

    expect(Patient::count())->toBe(0);
});

test('a lead with no usable phone is rejected', function () {
    $secret = Clinic::current()->webhookSecret();

    $this->withHeaders(['X-Webhook-Secret' => $secret])
        ->postJson(route('webhooks.leads'), ['name' => 'No Phone', 'phone' => '123'])
        ->assertStatus(422);

    expect(Patient::count())->toBe(0);
});

test('a lead matching an existing phone is not duplicated and enriches empty fields', function () {
    $secret = Clinic::current()->webhookSecret();
    $patient = Patient::factory()->create([
        'phone' => '(11) 98765-4321',
        'email' => null,
        'status' => PatientStatus::Ativo,
        'lead_source' => null,
    ]);

    $this->withHeaders(['X-Webhook-Secret' => $secret])
        ->postJson(route('webhooks.leads'), [
            'name' => 'Maria (de novo)',
            'phone' => '11987654321',
            'email' => 'maria@example.com',
            'source' => 'google',
        ])
        ->assertSuccessful();

    expect(Patient::where('phone_e164', '+5511987654321')->count())->toBe(1);

    $patient->refresh();
    expect($patient->email)->toBe('maria@example.com')          // enriched empty field
        ->and($patient->lead_source)->toBe('google')             // enriched empty field
        ->and($patient->status)->toBe(PatientStatus::Ativo);     // not yanked back to lead

    $this->assertDatabaseHas('timeline_events', ['patient_id' => $patient->id]);
});

test('a repeated external_id is idempotent', function () {
    $secret = Clinic::current()->webhookSecret();
    $payload = ['name' => 'Once', 'phone' => '11987654321', 'source' => 'meta', 'external_id' => 'dup-1'];

    $this->withHeaders(['X-Webhook-Secret' => $secret])->postJson(route('webhooks.leads'), $payload)->assertSuccessful();
    $this->withHeaders(['X-Webhook-Secret' => $secret])->postJson(route('webhooks.leads'), $payload)->assertSuccessful();

    expect(Patient::where('phone_e164', '+5511987654321')->count())->toBe(1);
});
