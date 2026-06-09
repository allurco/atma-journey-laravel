<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Actions\Scheduling\CreateDoctorShifts;
use App\Actions\Scheduling\CreateDoctorShiftsData;
use App\Models\Doctor;
use App\Models\DoctorShift;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The dedicated availability page: pick a doctor and a day, then drag on the
 * 24h vertical canvas to draw a shift. Releasing the drag opens a modal that can
 * copy the block across a date range (optionally skipping weekends) — recurrence
 * by bulk-copy. The drag geometry is client-side; the persistence is the TDD'd
 * {@see CreateDoctorShifts} action.
 */
#[Title('Disponibilidade')]
#[Layout('components.layouts.tenant')]
class Availability extends Component
{
    /** Pixels per hour on the vertical canvas — shared with the Alpine drawer. */
    public const HOUR_PX = 48;

    public ?int $selectedDoctorId = null;

    public string $date = '';

    public bool $showCopyModal = false;

    public string $drawStartTime = '';

    public string $drawEndTime = '';

    public string $copyFromDate = '';

    public string $copyToDate = '';

    public bool $skipWeekends = true;

    public function mount(): void
    {
        if ($this->date === '') {
            $this->date = Carbon::now()->format('Y-m-d');
        }

        $this->selectedDoctorId ??= Doctor::where('active', true)->orderBy('name')->value('id');
    }

    public function previousDay(): void
    {
        $this->date = Carbon::parse($this->date)->subDay()->format('Y-m-d');
    }

    public function nextDay(): void
    {
        $this->date = Carbon::parse($this->date)->addDay()->format('Y-m-d');
    }

    public function today(): void
    {
        $this->date = Carbon::now()->format('Y-m-d');
    }

    /**
     * Open the copy modal for a freshly drawn block. The times arrive snapped from
     * the Alpine drawer; the day range defaults to the single day being edited.
     */
    public function openDraw(string $start, string $end): void
    {
        $this->authorize('manage-scheduling');

        if ($this->selectedDoctorId === null) {
            $this->addError('selectedDoctorId', 'Selecione um médico primeiro.');

            return;
        }

        $this->resetErrorBag();
        $this->drawStartTime = $start;
        $this->drawEndTime = $end;
        $this->copyFromDate = $this->date;
        $this->copyToDate = $this->date;
        $this->skipWeekends = true;
        $this->showCopyModal = true;
    }

    public function saveDraw(CreateDoctorShifts $createDoctorShifts): void
    {
        $this->authorize('manage-scheduling');

        $validated = $this->validate([
            'selectedDoctorId' => ['required', Rule::exists('doctors', 'id')],
            'drawStartTime' => ['required', 'string'],
            'drawEndTime' => ['required', 'string', 'after:drawStartTime'],
            'copyFromDate' => ['required', 'date'],
            'copyToDate' => ['required', 'date', 'after_or_equal:copyFromDate'],
        ], [
            'drawEndTime.after' => 'O fim deve ser depois do início.',
            'copyToDate.after_or_equal' => 'A data final deve ser igual ou posterior à inicial.',
        ]);

        $createDoctorShifts(new CreateDoctorShiftsData(
            doctorId: (int) $validated['selectedDoctorId'],
            startTime: $validated['drawStartTime'],
            endTime: $validated['drawEndTime'],
            fromDate: $validated['copyFromDate'],
            toDate: $validated['copyToDate'],
            skipWeekends: $this->skipWeekends,
        ));

        $this->showCopyModal = false;
        $this->reset(['drawStartTime', 'drawEndTime', 'copyFromDate', 'copyToDate']);
    }

    public function cancelDraw(): void
    {
        $this->showCopyModal = false;
        $this->reset(['drawStartTime', 'drawEndTime', 'copyFromDate', 'copyToDate']);
    }

    public function removeShift(int $shiftId): void
    {
        $this->authorize('manage-scheduling');

        DoctorShift::whereKey($shiftId)->delete();
    }

    public function render(): View
    {
        $shifts = $this->selectedDoctorId !== null
            ? DoctorShift::query()
                ->where('doctor_id', $this->selectedDoctorId)
                ->where('date', $this->date)
                ->orderBy('start_time')
                ->get()
            : collect();

        $blocks = $shifts->map(fn (DoctorShift $shift): array => [
            'id' => $shift->id,
            'label' => $shift->start_time.'–'.$shift->end_time,
            'top' => $this->toMinutes($shift->start_time) * self::HOUR_PX / 60,
            'height' => ($this->toMinutes($shift->end_time) - $this->toMinutes($shift->start_time)) * self::HOUR_PX / 60,
        ]);

        return view('livewire.settings.availability', [
            'doctors' => Doctor::where('active', true)->orderBy('name')->get(['id', 'name']),
            'shiftBlocks' => $blocks,
            'hourPx' => self::HOUR_PX,
            'dayLabel' => Carbon::parse($this->date)->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY'),
        ]);
    }

    private function toMinutes(string $clock): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $clock));

        return ($hours * 60) + $minutes;
    }
}
