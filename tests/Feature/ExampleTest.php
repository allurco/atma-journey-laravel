<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});
