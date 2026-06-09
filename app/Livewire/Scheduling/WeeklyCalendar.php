<?php

declare(strict_types=1);

namespace App\Livewire\Scheduling;

use App\Actions\Scheduling\ScheduleAppointment;
use App\Actions\Scheduling\ScheduleAppointmentData;
use App\Actions\Scheduling\TransitionAppointment;
use App\Enums\AppointmentStatus;
use App\Exceptions\SchedulingConflictException;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorShift;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\SpecialCondition;
use App\Models\Specialty;
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

    /**
     * Active doctor/procedure filters (multi-select). Empty = show everything.
     *
     * @var list<int|string>
     */
    public array $filterDoctorIds = [];

    /** @var list<int|string> */
    public array $filterProcedureIds = [];

    public ?int $detailAppointmentId = null;

    public bool $showBooking = false;

    public ?int $bookPatientId = null;

    public ?int $bookDoctorId = null;

    public ?int $bookProcedureId = null;

    public string $bookServiceType = '';

    /** Unit (unidade) the visit happens at. */
    public string $bookUnit = '';

    /** Free-form scheduling note (observação). */
    public string $bookNotes = '';

    /**
     * The selected patient's care-need tags — pre-loaded from the patient, edited
     * here, and synced back on booking.
     *
     * @var list<int|string>
     */
    public array $bookSpecialConditionIds = [];

    public string $bookDate = '';

    public string $bookStartTime = '08:00';

    public string $bookEndTime = '09:00';

    /** Active calendar view: 'day' (resource lanes, default) or 'week' (the grid). */
    #[Url]
    public string $view = 'day';

    /** The day (Y-m-d) shown in the resource day view. */
    #[Url]
    public string $dayDate = '';

    /** Day view: narrow the doctor lanes to one specialty. Null = all. */
    public ?int $filterSpecialtyId = null;

    public bool $showShiftForm = false;

    public ?int $shiftDoctorId = null;

    public string $shiftStartTime = '08:00';

    public string $shiftEndTime = '12:00';

    public function mount(): void
    {
        if ($this->weekStart === '') {
            $this->weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        }

        if ($this->dayDate === '') {
            $this->dayDate = Carbon::now()->format('Y-m-d');
        }

        if ($this->agendarPatientId !== null) {
            $this->openBooking();
            $this->bookPatientId = $this->agendarPatientId;
            $this->loadPatientConditions();
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
        $this->dayDate = Carbon::now()->format('Y-m-d');
    }

    public function showWeek(): void
    {
        $this->view = 'week';
    }

    public function showDay(): void
    {
        $this->view = 'day';
    }

    public function previousDay(): void
    {
        $this->dayDate = Carbon::parse($this->dayDate)->subDay()->format('Y-m-d');
    }

    public function nextDay(): void
    {
        $this->dayDate = Carbon::parse($this->dayDate)->addDay()->format('Y-m-d');
    }

    public function openShiftForm(?int $doctorId = null): void
    {
        $this->authorize('manage-scheduling');

        $this->reset(['shiftDoctorId', 'shiftStartTime', 'shiftEndTime']);
        $this->resetErrorBag(['shiftDoctorId', 'shiftStartTime', 'shiftEndTime']);
        $this->shiftDoctorId = $doctorId;
        $this->shiftStartTime = '08:00';
        $this->shiftEndTime = '12:00';
        $this->showShiftForm = true;
    }

    public function saveShift(): void
    {
        $this->authorize('manage-scheduling');

        $validated = $this->validate([
            'shiftDoctorId' => ['required', Rule::exists('doctors', 'id')],
            'shiftStartTime' => ['required', 'string'],
            'shiftEndTime' => ['required', 'string', 'after:shiftStartTime'],
        ], [
            'shiftEndTime.after' => 'O fim deve ser depois do início.',
        ]);

        DoctorShift::create([
            'doctor_id' => (int) $validated['shiftDoctorId'],
            'date' => $this->dayDate,
            'start_time' => $validated['shiftStartTime'],
            'end_time' => $validated['shiftEndTime'],
        ]);

        $this->showShiftForm = false;
        $this->reset(['shiftDoctorId', 'shiftStartTime', 'shiftEndTime']);
    }

    public function cancelShiftForm(): void
    {
        $this->showShiftForm = false;
        $this->reset(['shiftDoctorId', 'shiftStartTime', 'shiftEndTime']);
    }

    public function removeShift(int $shiftId): void
    {
        $this->authorize('manage-scheduling');

        DoctorShift::whereKey($shiftId)->delete();
    }

    public function openDetail(int $appointmentId): void
    {
        $this->detailAppointmentId = $appointmentId;
    }

    public function closeDetail(): void
    {
        $this->detailAppointmentId = null;
    }

    public function transitionAppointment(int $appointmentId, string $status, TransitionAppointment $transitionAppointment): void
    {
        $this->authorize('manage-scheduling');

        $transitionAppointment(
            Appointment::findOrFail($appointmentId),
            AppointmentStatus::from($status),
        );

        $this->detailAppointmentId = null;
    }

    public function openBooking(?string $date = null, ?string $time = null, ?int $doctorId = null): void
    {
        $this->authorize('manage-scheduling');

        $this->resetBooking();
        $this->bookDate = $date ?? $this->monday()->format('Y-m-d');
        $this->bookStartTime = $time ?? '08:00';
        $this->bookEndTime = $this->computeEndTime($this->bookStartTime, $this->procedureDuration());
        $this->bookDoctorId = $doctorId;
        $this->showBooking = true;
    }

    public function updatedBookPatientId(): void
    {
        $this->loadPatientConditions();
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
            'bookUnit' => ['nullable', 'string', 'max:255'],
            'bookNotes' => ['nullable', 'string', 'max:2000'],
            'bookSpecialConditionIds' => ['array'],
            'bookSpecialConditionIds.*' => [Rule::exists('special_conditions', 'id')],
            'bookDate' => ['required', 'date'],
            'bookStartTime' => ['required', 'string'],
            'bookEndTime' => ['required', 'string'],
        ]);

        try {
            app(ScheduleAppointment::class)(new ScheduleAppointmentData(
                patientId: (int) $validated['bookPatientId'],
                date: $validated['bookDate'],
                startTime: $validated['bookStartTime'],
                endTime: $validated['bookEndTime'],
                doctorId: $this->bookDoctorId,
                procedureId: $this->bookProcedureId,
                serviceType: $validated['bookServiceType'] ?: null,
                unit: $validated['bookUnit'] ?: null,
                notes: $validated['bookNotes'] ?: null,
                specialConditionIds: array_map('intval', $this->bookSpecialConditionIds),
            ));
        } catch (SchedulingConflictException $e) {
            // The doctor's availability gate refused this slot — surface it on the
            // time field and keep the form open so the user can adjust.
            $this->addError('bookStartTime', $e->getMessage());

            return;
        }

        $this->showBooking = false;
        $this->resetBooking();
    }

    public function cancelBooking(): void
    {
        $this->showBooking = false;
        $this->resetBooking();
    }

    public function clearFilters(): void
    {
        $this->reset(['filterDoctorIds', 'filterProcedureIds']);
    }

    public function render(): View
    {
        $monday = $this->monday();
        $friday = $monday->copy()->addDays(4);

        /** @var Collection<int, Appointment> $loaded */
        $loaded = Appointment::query()
            ->visible()
            ->with('patient.specialConditions')
            ->whereBetween('date', [$monday->format('Y-m-d'), $friday->format('Y-m-d')])
            ->when($this->filterDoctorIds !== [], fn ($query) => $query->whereIn('doctor_id', $this->filterDoctorIds))
            ->when($this->filterProcedureIds !== [], fn ($query) => $query->whereIn('procedure_id', $this->filterProcedureIds))
            ->when($this->filterSpecialtyId !== null, fn ($query) => $query->whereHas(
                'doctor.specialties',
                fn ($specialties) => $specialties->whereKey($this->filterSpecialtyId),
            ))
            ->get();

        // Smart filter options — only doctors/procedures that actually have appointments.
        $doctorIdsWithAppointments = Appointment::query()->visible()->whereNotNull('doctor_id')->distinct()->pluck('doctor_id')->all();
        $procedureIdsWithAppointments = Appointment::query()->visible()->whereNotNull('procedure_id')->distinct()->pluck('procedure_id')->all();

        $appointments = $loaded->groupBy(
            fn (Appointment $appointment): string => $appointment->date->format('Y-m-d').'|'.$appointment->start_time,
        );

        $weekDays = collect(range(0, 4))->map(fn (int $offset): Carbon => $monday->copy()->addDays($offset));

        $day = Carbon::parse($this->dayDate);

        return view('livewire.scheduling.weekly-calendar', [
            'weekDays' => $weekDays,
            'timeSlots' => $this->timeSlots(),
            'appointments' => $appointments,
            'weekLabel' => $monday->format('d/m').' – '.$friday->format('d/m/Y'),
            'dayLabel' => $day->locale('pt_BR')->isoFormat('dddd, D [de] MMMM'),
            'dayLanes' => $this->view === 'day' ? $this->dayLanes() : collect(),
            'specialties' => Specialty::where('active', true)->orderBy('name')->get(['id', 'name']),
            'shiftDoctors' => $this->showShiftForm ? Doctor::where('active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'patients' => $this->showBooking ? Patient::orderBy('name')->get(['id', 'name']) : collect(),
            'doctors' => $this->showBooking ? Doctor::where('active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'procedures' => $this->showBooking ? Procedure::where('active', true)->orderBy('name')->get(['id', 'name', 'duration']) : collect(),
            'specialConditions' => $this->showBooking ? SpecialCondition::active()->orderBy('name')->get(['id', 'name']) : collect(),
            'bookingPatient' => $this->showBooking && $this->bookPatientId !== null
                ? Patient::with('specialConditions')->find($this->bookPatientId)
                : null,
            'filterDoctors' => $doctorIdsWithAppointments === [] ? collect() : Doctor::whereIn('id', $doctorIdsWithAppointments)->orderBy('name')->get(['id', 'name']),
            'filterProcedures' => $procedureIdsWithAppointments === [] ? collect() : Procedure::whereIn('id', $procedureIdsWithAppointments)->orderBy('name')->get(['id', 'name']),
            'canManage' => Gate::allows('manage-scheduling'),
            'detailAppointment' => $this->detailAppointmentId !== null
                ? Appointment::with(['patient.specialConditions', 'doctor'])->find($this->detailAppointmentId)
                : null,
        ]);
    }

    /**
     * The resource day view's lanes: one per active doctor (optionally narrowed to a
     * specialty), each carrying their dated shifts and, per hourly slot, whether it
     * sits inside a shift (bookable) and which appointments fill it.
     *
     * @return Collection<int, array{doctor: Doctor, shifts: Collection<int, DoctorShift>, slots: array<string, array{inShift: bool, appointments: Collection<int, Appointment>}>}>
     */
    private function dayLanes(): Collection
    {
        $doctors = Doctor::query()
            ->where('active', true)
            ->when($this->filterSpecialtyId !== null, fn ($query) => $query->whereHas(
                'specialties',
                fn ($specialties) => $specialties->whereKey($this->filterSpecialtyId),
            ))
            ->orderBy('name')
            ->get();

        $shifts = DoctorShift::query()
            ->where('date', $this->dayDate)
            ->get()
            ->groupBy('doctor_id');

        $appointments = Appointment::query()
            ->visible()
            ->with('patient.specialConditions')
            ->where('date', $this->dayDate)
            ->whereNotNull('doctor_id')
            ->get()
            ->groupBy('doctor_id');

        return $doctors->map(function (Doctor $doctor) use ($shifts, $appointments): array {
            /** @var Collection<int, DoctorShift> $doctorShifts */
            $doctorShifts = $shifts->get($doctor->id, collect());
            /** @var Collection<int, Appointment> $doctorAppointments */
            $doctorAppointments = $appointments->get($doctor->id, collect());

            $slots = [];

            foreach ($this->timeSlots() as $slot) {
                $slotStart = $this->toMinutes($slot);
                $slotEnd = $slotStart + 60;

                $slots[$slot] = [
                    'inShift' => $doctorShifts->contains(
                        fn (DoctorShift $shift): bool => $this->toMinutes($shift->start_time) <= $slotStart
                            && $slotEnd <= $this->toMinutes($shift->end_time),
                    ),
                    'appointments' => $doctorAppointments->filter(
                        fn (Appointment $appointment): bool => $this->toMinutes($appointment->start_time) >= $slotStart
                            && $this->toMinutes($appointment->start_time) < $slotEnd,
                    )->values(),
                ];
            }

            return ['doctor' => $doctor, 'shifts' => $doctorShifts, 'slots' => $slots];
        });
    }

    private function toMinutes(string $clock): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $clock));

        return ($hours * 60) + $minutes;
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
            'bookServiceType', 'bookUnit', 'bookNotes', 'bookSpecialConditionIds',
            'bookDate', 'bookStartTime', 'bookEndTime',
        ]);
        $this->bookStartTime = '08:00';
        $this->bookEndTime = '09:00';
    }

    /**
     * Mirror the selected patient's current care-need tags into the form so editing
     * doesn't silently drop the ones they already carry.
     */
    private function loadPatientConditions(): void
    {
        $patient = $this->bookPatientId !== null ? Patient::find($this->bookPatientId) : null;

        $this->bookSpecialConditionIds = $patient !== null
            ? $patient->specialConditions->pluck('id')->map(fn (int $id): string => (string) $id)->all()
            : [];
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
