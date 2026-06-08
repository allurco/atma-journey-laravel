<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\PhoneHistorySource;
use App\Enums\TimelineEventType;
use App\Models\Patient;
use App\Models\PatientPhoneHistory;
use Illuminate\Support\Carbon;

/**
 * Keeps the patient_phone_history log in sync: seeds the first number on create
 * and appends + records a timeline event whenever the (canonical) phone changes.
 */
class PatientObserver
{
    public function __construct(private AppendTimelineEvent $appendTimelineEvent) {}

    public function created(Patient $patient): void
    {
        if ($patient->phone_e164 === null) {
            return;
        }

        $this->record($patient, $patient->phoneHistorySource ?? PhoneHistorySource::Registration);
    }

    public function updated(Patient $patient): void
    {
        // Only when the canonical number actually changed — reformatting the same
        // number leaves phone_e164 untouched and records nothing.
        if (! $patient->wasChanged('phone_e164') || $patient->phone_e164 === null) {
            return;
        }

        $this->record($patient, PhoneHistorySource::Edit);

        ($this->appendTimelineEvent)(new AppendTimelineEventData(
            patientId: $patient->id,
            type: TimelineEventType::Nota,
            title: 'Telefone alterado',
            description: 'De '.$patient->getOriginal('phone').' para '.$patient->phone,
        ));
    }

    private function record(Patient $patient, PhoneHistorySource $source): void
    {
        PatientPhoneHistory::create([
            'patient_id' => $patient->id,
            'phone' => $patient->phone_e164,
            'source' => $source,
            'recorded_at' => Carbon::now(),
        ]);
    }
}
