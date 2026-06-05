<?php

declare(strict_types=1);

namespace App\Livewire\Onboarding;

use App\Actions\Tenancy\RegisterClinic;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest', ['title' => 'Criar conta da clínica'])]
class Register extends Component
{
    public string $clinic_name = '';

    public string $slug = '';

    public string $admin_name = '';

    public string $admin_email = '';

    public string $admin_password = '';

    public string $admin_password_confirmation = '';

    public function register(RegisterClinic $registerClinic): void
    {
        $tenant = $registerClinic([
            'clinic_name' => $this->clinic_name,
            'slug' => $this->slug,
            'admin_name' => $this->admin_name,
            'admin_email' => $this->admin_email,
            'admin_password' => $this->admin_password,
            'admin_password_confirmation' => $this->admin_password_confirmation,
        ]);

        $domain = $tenant->domains()->firstOrFail()->domain;

        $this->redirect('https://'.$domain.'/login');
    }

    public function render(): View
    {
        return view('livewire.onboarding.register');
    }
}
