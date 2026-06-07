<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PatientDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams an uploaded patient document. Tenancy is initialized by domain before
 * this runs, so Storage resolves to the tenant's own (suffixed) disk and
 * route-model binding only finds documents in this tenant — one clinic can never
 * read another's. Authenticated because clinical documents are PII (LGPD).
 */
class PatientDocumentController extends Controller
{
    public function __invoke(PatientDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->response($document->file_path);
    }
}
