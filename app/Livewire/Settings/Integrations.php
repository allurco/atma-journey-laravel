<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Clinic;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Integrações')]
#[Layout('components.layouts.tenant')]
class Integrations extends Component
{
    public string $secret = '';

    public bool $regenerated = false;

    public function mount(): void
    {
        // Page access is gated at the route (can:manage-clinic-settings); the
        // sensitive rotation re-checks in regenerate().
        $this->secret = Clinic::current()->webhookSecret();
    }

    public function regenerate(): void
    {
        $this->authorize('manage-clinic-settings');

        $this->secret = Clinic::current()->regenerateWebhookSecret();
        $this->regenerated = true;
    }

    public function render(): View
    {
        return view('livewire.settings.integrations', [
            'webhookUrl' => route('webhooks.leads'),
        ]);
    }
}
