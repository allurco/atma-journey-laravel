<?php

declare(strict_types=1);

namespace App\Livewire\Doctor;

use App\Models\Appointment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The doctor's daily worklist — who they see today, by time, with status. Scoped
 * to the logged-in doctor (users.doctor_id); an admin viewing it sees all. No
 * financial values.
 */
#[Title('Meu dia')]
#[Layout('components.layouts.tenant')]
class Dashboard extends Component
{
    #[Url]
    public string $date = '';

    public function mount(): void
    {
        if ($this->date === '') {
            $this->date = Carbon::now()->format('Y-m-d');
        }
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

    public function render(): View
    {
        $doctorId = auth()->user()?->doctor_id;

        $appointments = Appointment::query()
            ->visible()
            ->with(['patient', 'doctor'])
            ->where('date', $this->date)
            ->when($doctorId !== null, fn ($query) => $query->where('doctor_id', $doctorId))
            ->orderBy('start_time')
            ->get();

        return view('livewire.doctor.dashboard', [
            'appointments' => $appointments,
            'dateLabel' => Carbon::parse($this->date)->format('d/m/Y'),
        ]);
    }
}
