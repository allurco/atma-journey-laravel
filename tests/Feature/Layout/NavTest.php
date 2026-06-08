<?php

declare(strict_types=1);

use App\Models\User;

test('the sidebar no longer shows a Prontuário placeholder', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pacientes')
        ->assertSee('Agenda')
        ->assertDontSee('em breve'); // the standalone Prontuário placeholder is gone
});

test('the sidebar carries the collapsible-preference wiring', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('navCollapsed'); // localStorage-backed collapse preference
});
