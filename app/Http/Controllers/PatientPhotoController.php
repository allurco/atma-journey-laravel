<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a patient's photo. Tenancy is initialized by domain before this runs,
 * so Storage resolves to the tenant's own (suffixed) disk and route-model
 * binding only finds patients in this tenant — one clinic can never read
 * another's patient photo. Authenticated because the photo is PII (LGPD).
 */
class PatientPhotoController extends Controller
{
    public function __invoke(Patient $patient): StreamedResponse
    {
        abort_if(
            $patient->photo_path === null || ! Storage::disk('local')->exists($patient->photo_path),
            404,
        );

        return Storage::disk('local')->response($patient->photo_path);
    }
}
