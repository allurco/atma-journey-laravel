<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Models\DoctorShift;
use Illuminate\Support\Carbon;

/**
 * Stamps one availability block across an inclusive date range, optionally skipping
 * weekends, deduping days that already carry the identical shift. Returns how many
 * shifts were newly created. An inverted range (to before from) is a no-op.
 */
class CreateDoctorShifts
{
    public function __invoke(CreateDoctorShiftsData $data): int
    {
        $from = Carbon::parse($data->fromDate)->startOfDay();
        $to = Carbon::parse($data->toDate)->startOfDay();

        if ($to->lessThan($from)) {
            return 0;
        }

        $created = 0;

        for ($date = $from->copy(); $date->lessThanOrEqualTo($to); $date->addDay()) {
            if ($data->skipWeekends && $date->isWeekend()) {
                continue;
            }

            $shift = DoctorShift::firstOrCreate([
                'doctor_id' => $data->doctorId,
                'date' => $date->format('Y-m-d'),
                'start_time' => $data->startTime,
                'end_time' => $data->endTime,
            ]);

            if ($shift->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }
}
