<?php

declare(strict_types=1);

use App\Models\User;

test('the configuracoes entry redirects to the account tab', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('configuracoes'))->assertRedirect(route('profile.edit'));
});

test('the settings shell renders the ported tab nav', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Configurações')
        ->assertSee('Conta')
        ->assertSee('Segurança')
        ->assertSee('Especialidades'); // catalog tab placeholder is present
});
