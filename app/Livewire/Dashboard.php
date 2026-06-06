<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\PatientStatus;
use App\Enums\PipelineStage;
use App\Models\Patient;
use App\Models\PipelineCard;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Painel')]
#[Layout('components.layouts.tenant')]
class Dashboard extends Component
{
    public function render(): View
    {
        // The recall list — active patients overdue for a visit, most overdue first.
        // This is the differentiator made visible: "no patient is forgotten".
        $recalls = Patient::needingRecall()->orderBy('last_visit_date')->get();

        return view('livewire.dashboard', [
            'recalls' => $recalls,
            'recallCount' => $recalls->count(),
            'recoveredRevenue' => (float) Patient::where('status', PatientStatus::Ativo)->sum('ltv'),
            'pipelineValue' => (float) PipelineCard::where('stage', '!=', PipelineStage::Desistentes)->sum('value'),
            'missedTotal' => (int) Patient::sum('missed_appointments'),
        ]);
    }
}
