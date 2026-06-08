<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a blank document template. Tenancy is initialized by domain before this
 * runs, so Storage and route-model binding are tenant-scoped — one clinic can
 * never read another's templates. Authenticated (any tenant user), since the
 * front desk needs to preview a template before sending it.
 */
class DocumentTemplateController extends Controller
{
    public function __invoke(DocumentTemplate $template): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($template->file_path), 404);

        return Storage::disk('local')->response($template->file_path);
    }
}
