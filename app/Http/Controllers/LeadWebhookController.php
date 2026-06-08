<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Leads\IngestLead;
use App\Actions\Leads\IngestLeadData;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Inbound lead webhook. Authenticated by the per-clinic secret (middleware) and
 * resolved to the tenant by domain. Accepts a provider-agnostic JSON payload and
 * hands it to {@see IngestLead}.
 */
class LeadWebhookController extends Controller
{
    public function __invoke(Request $request, IngestLead $ingestLead): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:2000'],
            'external_id' => ['nullable', 'string', 'max:255'],
        ]);

        $phoneE164 = Phone::normalize($validated['phone']);

        if ($phoneE164 === null) {
            throw ValidationException::withMessages(['phone' => 'Número de telefone inválido.']);
        }

        $ingestLead(new IngestLeadData(
            name: $validated['name'],
            phoneE164: $phoneE164,
            email: $validated['email'] ?? null,
            source: $validated['source'] ?? null,
            message: $validated['message'] ?? null,
            externalId: $validated['external_id'] ?? null,
            rawPayload: $request->all(),
        ));

        return response()->json(['status' => 'ok'], 202);
    }
}
