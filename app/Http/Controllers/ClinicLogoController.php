<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Clinic;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the current tenant's clinic logo. Tenancy is initialized by domain
 * before this runs, so Storage resolves to the tenant's own (suffixed) disk —
 * one clinic can never read another's logo.
 */
class ClinicLogoController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $clinic = Clinic::current();

        abort_if(
            $clinic->logo_path === null || ! Storage::disk('local')->exists($clinic->logo_path),
            404,
        );

        return Storage::disk('local')->response($clinic->logo_path);
    }
}
