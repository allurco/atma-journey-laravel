<?php

declare(strict_types=1);

use App\Enums\PatientStatus;
use App\Livewire\Patients\Index;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('staff (not just admin) can create a patient', function () {
    $this->actingAs(User::factory()->staff()->create());

    Livewire::test(Index::class)
        ->call('create')
        ->set('name', 'João da Silva')
        ->set('phone', '(11) 98888-0000')
        ->call('save')
        ->assertHasNoErrors();

    expect(Patient::where('name', 'João da Silva')->exists())->toBeTrue();
});

test('name and phone are required', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->call('create')
        ->set('name', '')
        ->set('phone', '')
        ->call('save')
        ->assertHasErrors(['name', 'phone']);
});

test('cpf is encrypted at rest with a plaintext last4 and a blind-index hash', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->call('create')
        ->set('name', 'Maria Souza')
        ->set('phone', '(11) 97777-0000')
        ->set('cpf', '123.456.789-09')
        ->call('save')
        ->assertHasNoErrors();

    $patient = Patient::firstWhere('name', 'Maria Souza');
    expect($patient->cpf)->toBe('123.456.789-09')        // decrypts via the cast
        ->and($patient->cpf_last4)->toBe('8909')
        ->and($patient->cpf_hash)->toBe(Patient::hashCpf('12345678909'));

    // raw column is ciphertext, never the plaintext CPF
    $raw = DB::table('patients')->where('id', $patient->id)->value('cpf');
    expect($raw)->not->toContain('123.456.789-09')
        ->and($raw)->not->toBe('12345678909');
});

test('a duplicate cpf is rejected via the blind index', function () {
    $this->actingAs(User::factory()->admin()->create());
    Patient::factory()->create(['cpf' => '123.456.789-09']);

    Livewire::test(Index::class)
        ->call('create')
        ->set('name', 'Outro Paciente')
        ->set('phone', '(11) 96666-0000')
        ->set('cpf', '12345678909') // same digits, different formatting
        ->call('save')
        ->assertHasErrors('cpf');
});

test('the list searches by name, phone and cpf last4', function () {
    $this->actingAs(User::factory()->admin()->create());
    Patient::factory()->create(['name' => 'Ana Lima', 'phone' => '(11) 91111-2222', 'cpf' => '111.222.333-44']);
    Patient::factory()->create(['name' => 'Bruno Costa', 'phone' => '(21) 93333-4444', 'cpf' => '555.666.777-88']);

    Livewire::test(Index::class)
        ->set('search', 'Ana')->assertSee('Ana Lima')->assertDontSee('Bruno Costa')
        ->set('search', '3344')->assertSee('Ana Lima')->assertDontSee('Bruno Costa');
});

test('the list filters by status', function () {
    $this->actingAs(User::factory()->admin()->create());
    Patient::factory()->create(['name' => 'Paciente Ativo']);
    Patient::factory()->lead()->create(['name' => 'Paciente Lead']);

    Livewire::test(Index::class)
        ->set('statusFilter', PatientStatus::Lead->value)
        ->assertSee('Paciente Lead')
        ->assertDontSee('Paciente Ativo');
});

test('an admin can edit a patient', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create(['name' => 'Nome Antigo']);

    Livewire::test(Index::class)
        ->call('edit', $patient->id)
        ->set('name', 'Nome Novo')
        ->set('status', PatientStatus::Inativo->value)
        ->call('save')
        ->assertHasNoErrors();

    $patient->refresh();
    expect($patient->name)->toBe('Nome Novo')
        ->and($patient->status)->toBe(PatientStatus::Inativo);
});

test('scopeNeedingRecall returns active patients overdue for a visit', function () {
    Patient::factory()->create(['last_visit_date' => now()->subMonths(8)]);   // overdue
    Patient::factory()->create(['last_visit_date' => now()->subMonth()]);     // recent
    Patient::factory()->inactive()->create(['last_visit_date' => now()->subYear()]); // inactive
    Patient::factory()->create(['last_visit_date' => null]);                  // never visited

    expect(Patient::needingRecall()->count())->toBe(1);
});

test('patients are isolated per tenant', function () {
    Patient::factory()->create();
    expect(Patient::count())->toBe(1);

    $other = Tenant::create(['name' => 'Outra Clínica', 'slug' => 'outra-'.uniqid()]);
    $other->run(fn () => expect(Patient::count())->toBe(0));
    $other->delete();
});
