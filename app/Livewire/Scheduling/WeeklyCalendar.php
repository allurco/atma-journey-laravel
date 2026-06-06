<?php

declare(strict_types=1);

namespace App\Livewire\Scheduling;

use App\Actions\Scheduling\ScheduleAppointment;
use App\Actions\Scheduling\ScheduleAppointmentData;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Procedure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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

    /** When the agenda is opened from a patient detail, pre-open booking for them. */
    #[Url(as: 'novo')]
    public ?int $agendarPatientId = null;

    public bool $showBooking = false;

    public ?int $bookPatientId = null;

    public ?int $bookDoctorId = null;

    public ?int $bookProcedureId = null;

    public string $bookServiceType = '';

    public string $bookDate = '';

    public string $bookStartTime = '08:00';

    public string $bookEndTime = '09:00';

    public function mount(): void
    {
        if ($this->weekStart === '') {
            $this->weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        }

        if ($this->agendarPatientId !== null) {
            $this->openBooking();
            $this->bookPatientId = $this->agendarPatientId;
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

    public function openBooking(?string $date = null, ?string $time = null): void
    {
        $this->authorize('manage-scheduling');

        $this->resetBooking();
        $this->bookDate = $date ?? $this->monday()->format('Y-m-d');
        $this->bookStartTime = $time ?? '08:00';
        $this->bookEndTime = $this->computeEndTime($this->bookStartTime, $this->procedureDuration());
        $this->showBooking = true;
    }

    public function updatedBookProcedureId(): void
    {
        $procedure = $this->bookProcedureId !== null ? Procedure::find($this->bookProcedureId) : null;

        if ($procedure !== null) {
            $this->bookServiceType = $procedure->name;
        }

        $this->bookEndTime = $this->computeEndTime($this->bookStartTime, $this->procedureDuration());
    }

    public function updatedBookStartTime(): void
    {
        $this->bookEndTime = $this->computeEndTime($this->bookStartTime, $this->procedureDuration());
    }

    public function book(): void
    {
        $this->authorize('manage-scheduling');

        $validated = $this->validate([
            'bookPatientId' => ['required', Rule::exists('patients', 'id')],
            'bookDoctorId' => ['nullable', Rule::exists('doctors', 'id')],
            'bookProcedureId' => ['nullable', Rule::exists('procedures', 'id')],
            'bookServiceType' => ['nullable', 'string', 'max:255'],
            'bookDate' => ['required', 'date'],
            'bookStartTime' => ['required', 'string'],
            'bookEndTime' => ['required', 'string'],
        ]);

        app(ScheduleAppointment::class)(new ScheduleAppointmentData(
            patientId: (int) $validated['bookPatientId'],
            date: $validated['bookDate'],
            startTime: $validated['bookStartTime'],
            endTime: $validated['bookEndTime'],
            doctorId: $this->bookDoctorId,
            procedureId: $this->bookProcedureId,
            serviceType: $validated['bookServiceType'] ?: null,
        ));

        $this->showBooking = false;
        $this->resetBooking();
    }

    public function cancelBooking(): void
    {
        $this->showBooking = false;
        $this->resetBooking();
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
            'patients' => Patient::orderBy('name')->get(['id', 'name']),
            'doctors' => Doctor::where('active', true)->orderBy('name')->get(['id', 'name']),
            'procedures' => Procedure::where('active', true)->orderBy('name')->get(['id', 'name', 'duration']),
            'canManage' => Gate::allows('manage-scheduling'),
        ]);
    }

    private function monday(): Carbon
    {
        return Carbon::parse($this->weekStart)->startOfWeek(Carbon::MONDAY);
    }

    /** Duration (minutes) of the selected procedure, or the 60-minute default. */
    private function procedureDuration(): int
    {
        $procedure = $this->bookProcedureId !== null ? Procedure::find($this->bookProcedureId) : null;

        return $procedure !== null && $procedure->duration > 0 ? $procedure->duration : 60;
    }

    private function computeEndTime(string $startTime, int $durationMinutes): string
    {
        return Carbon::createFromFormat('H:i', $startTime)->addMinutes($durationMinutes)->format('H:i');
    }

    private function resetBooking(): void
    {
        $this->reset([
            'bookPatientId', 'bookDoctorId', 'bookProcedureId',
            'bookServiceType', 'bookDate', 'bookStartTime', 'bookEndTime',
        ]);
        $this->bookStartTime = '08:00';
        $this->bookEndTime = '09:00';
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
