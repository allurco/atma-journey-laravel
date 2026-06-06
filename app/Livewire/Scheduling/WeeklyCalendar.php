<?php

declare(strict_types=1);

namespace App\Livewire\Scheduling;

use App\Models\Appointment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Agenda')]
#[Layout('components.layouts.tenant')]
class WeeklyCalendar extends Component
{
    /** Monday (Y-m-d) of the displayed week. */
    #[Url]
    public string $weekStart = '';

    public function mount(): void
    {
        if ($this->weekStart === '') {
            $this->weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        }
    }

    public function previousWeek(): void
    {
        $this->weekStart = $this->monday()->subWeek()->format('Y-m-d');
    }

    public function nextWeek(): void
    {
        $this->weekStart = $this->monday()->addWeek()->format('Y-m-d');
    }

    public function today(): void
    {
        $this->weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    public function render(): View
    {
        $monday = $this->monday();
        $friday = $monday->copy()->addDays(4);

        /** @var Collection<int, Appointment> $loaded */
        $loaded = Appointment::query()
            ->visible()
            ->with('patient')
            ->whereBetween('date', [$monday->format('Y-m-d'), $friday->format('Y-m-d')])
            ->get();

        $appointments = $loaded->groupBy(
            fn (Appointment $appointment): string => $appointment->date->format('Y-m-d').'|'.$appointment->start_time,
        );

        $weekDays = collect(range(0, 4))->map(fn (int $offset): Carbon => $monday->copy()->addDays($offset));

        return view('livewire.scheduling.weekly-calendar', [
            'weekDays' => $weekDays,
            'timeSlots' => $this->timeSlots(),
            'appointments' => $appointments,
            'weekLabel' => $monday->format('d/m').' – '.$friday->format('d/m/Y'),
        ]);
    }

    private function monday(): Carbon
    {
        return Carbon::parse($this->weekStart)->startOfWeek(Carbon::MONDAY);
    }

    /**
     * @return list<string>
     */
    private function timeSlots(): array
    {
        return collect(range(8, 18))
            ->map(fn (int $hour): string => sprintf('%02d:00', $hour))
            ->all();
    }
}
