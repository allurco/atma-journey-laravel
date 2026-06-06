<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

function makeTenantWithUser(string $slug, string $email): Tenant
{
    $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug]);
    $tenant->domains()->create(['domain' => $slug.'.localhost']);

    $tenant->run(function () use ($email): void {
        User::factory()->create(['email' => $email]);
    });

    return $tenant;
}

it('authenticates a user only on their own tenant domain', function () {
    $tenantA = makeTenantWithUser('clinica-a', 'admin@clinica-a.test');
    $tenantB = makeTenantWithUser('clinica-b', 'admin@clinica-b.test');

    // Tenant A's credentials on tenant B's domain are rejected.
    URL::forceRootUrl('http://clinica-b.localhost');
    $this->post(route('login.store'), [
        'email' => 'admin@clinica-a.test',
        'password' => 'password',
    ])->assertSessionHasErrors('email');
    $this->assertGuest();

    // The same credentials on tenant A's own domain succeed.
    URL::forceRootUrl('http://clinica-a.localhost');
    $this->post(route('login.store'), [
        'email' => 'admin@clinica-a.test',
        'password' => 'password',
    ])->assertSessionHasNoErrors();
    $this->assertAuthenticated();

    $tenantA->delete();
    $tenantB->delete();
});

it('does not expose clinic login on the central domain', function () {
    // Clinic login lives on tenant subdomains. On the central domain /login is not a
    // login form — it redirects to signup, so no clinic auth is exposed here.
    $this->get(route('login'))->assertRedirect(route('signup'));
});

it('does not offer tenant self-registration', function () {
    expect(Route::has('register'))->toBeFalse();
});
