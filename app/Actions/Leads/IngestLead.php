<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\ContactType;
use App\Enums\PatientStatus;
use App\Enums\PhoneHistorySource;
use App\Enums\PipelineStage;
use App\Enums\TimelineEventType;
use App\Events\LeadReceived;
use App\Models\LeadIngestion;
use App\Models\Patient;
use App\Models\PatientPhoneHistory;
use App\Models\PipelineCard;
use Illuminate\Support\Carbon;

/**
 * The single ingestion path for a lead. Dedups by the canonical phone
 * (`phone_e164`): a new number becomes a `lead` patient at `primeiro_contato`;
 * a known number records a re-contact + enriches empty fields, never a
 * duplicate. Idempotent on `external_id`. Every hit is audited in
 * `lead_ingestions`. (Historical-phone matching arrives in PRD-8 card 2.)
 */
final class IngestLead
{
    public function __construct(private AppendTimelineEvent $appendTimelineEvent) {}

    public function __invoke(IngestLeadData $data): ?Patient
    {
        // Idempotency — a repeated external_id takes no action beyond an audit row.
        if ($data->externalId !== null) {
            $prior = LeadIngestion::query()->where('external_id', $data->externalId)->first();

            if ($prior !== null) {
                $this->audit($data, $prior->patient_id, matched: true);

                return $prior->patient;
            }
        }

        // Dedup on the current canonical phone first, then fall back to a number the
        // patient held in the past (telecoms recycle numbers, so a historical-only
        // match is surfaced with a note rather than silently trusted).
        $patient = Patient::query()->where('phone_e164', $data->phoneE164)->first();
        $historicalMatch = false;

        if ($patient === null) {
            $prior = PatientPhoneHistory::query()
                ->where('phone', $data->phoneE164)
                ->latest('recorded_at')
                ->first();

            if ($prior?->patient !== null) {
                $patient = $prior->patient;
                $historicalMatch = true;
            }
        }

        $matched = $patient !== null;

        if ($patient === null) {
            $patient = new Patient([
                'name' => $data->name,
                'phone' => $data->phoneE164,
                'email' => $data->email,
                'status' => PatientStatus::Lead,
                'lead_source' => $data->source,
            ]);
            $patient->phoneHistorySource = PhoneHistorySource::Lead;
            $patient->save();

            PipelineCard::updateOrCreate(
                ['patient_id' => $patient->id],
                [
                    'stage' => PipelineStage::PrimeiroContato,
                    'treatment' => 'Novo lead',
                    'value' => 0,
                    'contact_type' => ContactType::Whatsapp,
                    'last_contact' => Carbon::now(),
                ],
            );
        } else {
            // Enrich only empty fields — never overwrite existing data, never change
            // an existing patient's status or stage.
            $patient->fill([
                'email' => $patient->email ?: $data->email,
                'lead_source' => $patient->lead_source ?: $data->source,
            ])->save();
        }

        ($this->appendTimelineEvent)(new AppendTimelineEventData(
            patientId: $patient->id,
            type: TimelineEventType::Nota,
            title: 'Lead recebido'.($data->source !== null ? ' via '.$data->source : ''),
            description: $data->message,
        ));

        if ($historicalMatch) {
            ($this->appendTimelineEvent)(new AppendTimelineEventData(
                patientId: $patient->id,
                type: TimelineEventType::Nota,
                title: 'Possível correspondência por telefone antigo',
                description: 'Lead recebido no número '.$data->phoneE164.', um número antigo deste paciente — confirme se é a mesma pessoa.',
            ));
        }

        $this->audit($data, $patient->id, $matched);

        if (! $matched) {
            LeadReceived::dispatch($patient, $data->source ?? 'webhook');
        }

        return $patient;
    }

    private function audit(IngestLeadData $data, ?int $patientId, bool $matched): void
    {
        LeadIngestion::create([
            'source' => $data->source,
            'external_id' => $data->externalId,
            'phone' => $data->phoneE164,
            'patient_id' => $patientId,
            'matched' => $matched,
            'payload' => $data->rawPayload,
            'received_at' => Carbon::now(),
        ]);
    }
}
