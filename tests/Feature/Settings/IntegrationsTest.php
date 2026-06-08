<?php

declare(strict_types=1);

use App\Livewire\Settings\Integrations;
use App\Models\Clinic;
use App\Models\User;
use Livewire\Livewire;

test('an admin sees the lead webhook url and secret', function () {
    $this->actingAs(User::factory()->admin()->create());
    $secret = Clinic::current()->webhookSecret();

    $this->get(route('integracoes'))
        ->assertOk()
        ->assertSee('webhooks/leads')
        ->assertSee($secret);
});

test('staff cannot view the integrations settings', function () {
    $this->actingAs(User::factory()->staff()->create());

    $this->get(route('integracoes'))->assertForbidden();
});

test('a guest is redirected from the integrations settings', function () {
    $this->get(route('integracoes'))->assertRedirect(route('login'));
});

test('regenerating the secret rotates it and invalidates the old one', function () {
    $this->actingAs(User::factory()->admin()->create());
    $old = Clinic::current()->webhookSecret();

    Livewire::test(Integrations::class)
        ->call('regenerate')
        ->assertHasNoErrors();

    $new = (string) Clinic::current()->fresh()->webhook_secret;
    expect($new)->not->toBe($old)->not->toBe('');

    // the old secret no longer authenticates the webhook...
    $this->withHeaders(['X-Webhook-Secret' => $old])
        ->postJson(route('webhooks.leads'), ['name' => 'X', 'phone' => '11911111111'])
        ->assertStatus(401);

    // ...and the new one does.
    $this->withHeaders(['X-Webhook-Secret' => $new])
        ->postJson(route('webhooks.leads'), ['name' => 'X', 'phone' => '11911111111'])
        ->assertSuccessful();
});

test('staff cannot regenerate the secret', function () {
    $this->actingAs(User::factory()->staff()->create());

    Livewire::test(Integrations::class)
        ->call('regenerate')
        ->assertForbidden();
});
