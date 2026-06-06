<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

it('renders the marketing landing page on the central domain', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('nenhum paciente é esquecido');
    $response->assertSee(route('signup'), escape: false);
});

it('redirects the central /login to the signup page', function () {
    $response = $this->get('/login');

    $response->assertRedirect('/signup');
});
