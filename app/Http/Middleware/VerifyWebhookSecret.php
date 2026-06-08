<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Clinic;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates an inbound webhook against the current clinic's `webhook_secret`
 * (constant-time). Tenancy is already initialized by domain, so `Clinic::current()`
 * resolves this clinic — one clinic's secret can never authenticate another's.
 */
class VerifyWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = (string) $request->header('X-Webhook-Secret', '');
        $expected = Clinic::current()->webhook_secret;

        abort_if($expected === null || ! hash_equals($expected, $provided), 401, 'Invalid webhook secret.');

        return $next($request);
    }
}
