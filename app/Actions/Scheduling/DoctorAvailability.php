<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Models\Appointment;
use App\Models\DoctorShift;
use Illuminate\Support\Collection;

/**
 * The single home for the agenda's time math: whether a window sits inside a
 * doctor's shift, whether it collides with another booking, and the free slots
 * left in a doctor's day (`shifts − non-cancelled appointments`). Both the
 * booking gate ({@see ScheduleAppointment}) and the resource grid read from here
 * so the rules never drift between the action and the UI.
 *
 * Times are wall-clock 'HH:mm' strings, compared in minutes-since-midnight.
 *
 * @phpstan-type Interval array{start: string, end: string}
 */
class DoctorAvailability
{
    /**
     * Does any of the doctor's shifts on $date fully contain the window?
     */
    public function isWithinShift(int $doctorId, string $date, string $start, string $end): bool
    {
        $from = $this->toMinutes($start);
        $to = $this->toMinutes($end);

        return $this->shiftsOn($doctorId, $date)
            ->contains(fn (DoctorShift $shift): bool => $this->toMinutes($shift->start_time) <= $from
                && $to <= $this->toMinutes($shift->end_time));
    }

    /**
     * Does a non-cancelled appointment for the doctor on $date overlap the window?
     */
    public function hasConflict(int $doctorId, string $date, string $start, string $end): bool
    {
        $from = $this->toMinutes($start);
        $to = $this->toMinutes($end);

        return $this->appointmentsOn($doctorId, $date)
            ->contains(fn (Appointment $appointment): bool => $this->toMinutes($appointment->start_time) < $to
                && $from < $this->toMinutes($appointment->end_time));
    }

    /**
     * The free windows left in the doctor's day: each shift minus the
     * non-cancelled appointments that overlap it, in chronological order.
     *
     * @return list<Interval>
     */
    public function freeIntervalsOn(int $doctorId, string $date): array
    {
        $booked = $this->appointmentsOn($doctorId, $date)
            ->map(fn (Appointment $appointment): array => [
                'start' => $this->toMinutes($appointment->start_time),
                'end' => $this->toMinutes($appointment->end_time),
            ])
            ->sortBy('start')
            ->values();

        $free = [];

        foreach ($this->shiftsOn($doctorId, $date)->sortBy('start_time') as $shift) {
            $cursor = $this->toMinutes($shift->start_time);
            $shiftEnd = $this->toMinutes($shift->end_time);

            foreach ($booked as $interval) {
                $start = max($interval['start'], $cursor);
                $end = min($interval['end'], $shiftEnd);

                if ($start >= $end) {
                    continue; // outside this shift
                }

                if ($start > $cursor) {
                    $free[] = ['start' => $this->toClock($cursor), 'end' => $this->toClock($start)];
                }

                $cursor = max($cursor, $end);
            }

            if ($cursor < $shiftEnd) {
                $free[] = ['start' => $this->toClock($cursor), 'end' => $this->toClock($shiftEnd)];
            }
        }

        return $free;
    }

    /**
     * @return Collection<int, DoctorShift>
     */
    private function shiftsOn(int $doctorId, string $date)
    {
        return DoctorShift::query()
            ->where('doctor_id', $doctorId)
            ->where('date', $date)
            ->get();
    }

    /**
     * @return Collection<int, Appointment>
     */
    private function appointmentsOn(int $doctorId, string $date)
    {
        return Appointment::query()
            ->visible()
            ->where('doctor_id', $doctorId)
            ->where('date', $date)
            ->get();
    }

    private function toMinutes(string $clock): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $clock));

        return ($hours * 60) + $minutes;
    }

    private function toClock(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
