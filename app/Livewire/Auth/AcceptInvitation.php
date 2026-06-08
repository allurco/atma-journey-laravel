<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Aceitar convite')]
#[Layout('components.layouts.guest')]
class AcceptInvitation extends Component
{
    public string $token = '';

    public bool $valid = false;

    public string $userName = '';

    public string $clinicName = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->clinicName = (string) (tenant('name') ?? config('app.name'));

        $user = User::findByInvitationToken($token);

        if ($user !== null && $user->isInvitationPending()) {
            $this->valid = true;
            $this->userName = $user->name;
        }
    }

    public function accept(): void
    {
        $user = User::findByInvitationToken($this->token);

        if ($user === null || ! $user->isInvitationPending()) {
            $this->valid = false;

            return;
        }

        $this->validate([
            'password' => ['required', 'string', Password::min(8)],
            'passwordConfirmation' => ['required', 'same:password'],
        ]);

        $user->acceptInvitation($this->password);
        auth()->login($user);

        $this->redirectRoute('dashboard', navigate: false);
    }

    public function render(): View
    {
        return view('livewire.auth.accept-invitation');
    }
}
