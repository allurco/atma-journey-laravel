<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks doctor users from non-clinical areas (dashboard, pipeline, finance, the
 * patient registry). Doctors work from "Meu dia" and open a patient's profile
 * from there — they never browse the back-office.
 */
class DenyDoctors
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->isDoctor() === true, 403);

        return $next($request);
    }
}
