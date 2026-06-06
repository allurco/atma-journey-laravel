<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Real-HTTP tenancy integration tests
|--------------------------------------------------------------------------
|
| These tests deliberately use the base `Tests\TestCase` (NOT TenantTestCase),
| because TenantTestCase pre-initializes tenancy in setUp(). Here tenancy must
| be initialized *only* by the real `web`-group middleware
| (App\Http\Middleware\InitializeTenancyForWeb), so the tests exercise the same
| request path a browser does — closing the actingAs()/Livewire::test() blind
| spot that hid three production 500s (session split across DBs, Livewire routes
| without tenancy, file upload without tenancy).
|
*/

uses(TestCase::class, RefreshDatabase::class);

/**
 * Provision a tenant on its own domain with an admin user created *inside* the
 * tenant database (where `users` live), mirroring routes/tenant.php conventions.
 *
 * @return array{0: Tenant, 1: string, 2: User}
 */
function provisionTenantWithAdmin(string $email): array
{
    $slug = 'http-'.Str::lower(Str::random(12));

    $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug]);
    $domain = $slug.'.localhost';
    $tenant->domains()->create(['domain' => $domain]);

    /** @var User $user */
    $user = $tenant->run(fn (): User => User::factory()->admin()->create(['email' => $email]));

    // Provisioning leaves the default DB connection repointed at the tenant database
    // (stancl swaps it during run()). Reset that state so the upcoming HTTP requests can
    // ONLY reach the tenant DB if the real `web`-group middleware re-initializes tenancy —
    // otherwise this test would pass on leaked connection config even with the middleware
    // removed, and would not guard the regression.
    tenancy()->end();
    DB::purge('tenant');
    DB::purge((string) config('database.default'));

    return [$tenant, $domain, $user];
}

it('resolves the tenant user on a web+auth route through the real middleware stack', function () {
    // Headline guard for the universal-tenancy regression: a generic web+auth route
    // (no per-route tenancy patching) must run with the tenant DB active so auth
    // resolves the tenant user, returning 200 — not 500.
    [$tenant, $domain, $user] = provisionTenantWithAdmin('probe@clinica.test');

    // Drive request hosts onto the tenant domain so InitializeTenancyByDomain matches it.
    URL::forceRootUrl('http://'.$domain);

    // A bare web+auth route registered the way any new authenticated route would be.
    Route::middleware(['web', 'auth'])->get('__probe', fn (): string => (string) auth()->id());

    // Log the admin in over real HTTP (Fortify POST /login).
    $this->post(route('login.store'), [
        'email' => 'probe@clinica.test',
        'password' => 'password',
    ])->assertSessionHasNoErrors();
    $this->assertAuthenticated();

    // GET the probe on the tenant domain: it must resolve the tenant user's id (200),
    // proving the web group initialized tenancy before auth ran.
    $response = $this->get('http://'.$domain.'/__probe');
    $response->assertOk();
    expect($response->getContent())->toBe((string) $user->getKey());

    $tenant->delete();
});

it('persists the session across requests with the database driver (session-split regression)', function () {
    // The session-split bug: sessions are stored on the `central` connection while the
    // auth user lives in the tenant DB. Drive the database session driver explicitly and
    // assert a SECOND authenticated HTTP request is still authenticated.
    config(['session.driver' => 'database']);

    [$tenant, $domain] = provisionTenantWithAdmin('persist@clinica.test');

    URL::forceRootUrl('http://'.$domain);

    // First request: log in over HTTP. Fortify redirects to the protected dashboard.
    $this->post(route('login.store'), [
        'email' => 'persist@clinica.test',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    // Second request to a protected tenant route: still authenticated (200), NOT bounced
    // to /login — the session survived across requests despite the central/tenant DB split.
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText('login');
    $this->assertAuthenticated();

    $tenant->delete();
});
