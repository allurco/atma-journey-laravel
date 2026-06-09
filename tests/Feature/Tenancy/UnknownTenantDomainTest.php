<?php

declare(strict_types=1);

/**
 * An unregistered subdomain is a visitor mistyping a clinic slug — not a server fault.
 * Tenancy identification fails for it, and that must render a clean 404 rather than a 500.
 */
it('returns 404 for a request to an unregistered tenant subdomain', function () {
    $this->get('http://nao-existe-clinica.atma.test/login')->assertNotFound();
});
