<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PatientDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a document to the patient on the public signing page — authenticated by
 * the single-use signing token in the URL (no session). Tenancy is resolved by
 * domain, so the token only matches a document in this clinic. Only pending
 * documents are served; once signed, the token is cleared and the link 404s.
 */
class SignDocumentFileController extends Controller
{
    public function __invoke(string $token): StreamedResponse
    {
        $document = PatientDocument::findBySignatureToken($token);

        abort_if($document === null || ! $document->isAwaitingSignature(), 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->response($document->file_path);
    }
}
