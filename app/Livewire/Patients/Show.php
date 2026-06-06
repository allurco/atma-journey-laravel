<?php

declare(strict_types=1);

namespace App\Livewire\Patients;

use App\Models\Patient;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Paciente')]
#[Layout('components.layouts.tenant')]
class Show extends Component
{
    public Patient $patient;

    public function mount(Patient $patient): void
    {
        $this->patient = $patient;
    }

    public function render(): View
    {
        return view('livewire.patients.show');
    }
}
