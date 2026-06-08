<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Actions\Users\InviteUser;
use App\Actions\Users\InviteUserData;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Equipe')]
#[Layout('components.layouts.tenant')]
class Team extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'staff';

    public function create(): void
    {
        $this->authorize('manage-users');

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $userId): void
    {
        $this->authorize('manage-users');

        $user = User::findOrFail($userId);
        $this->resetForm();
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->showForm = true;
    }

    /**
     * Edit an existing member, or invite a new one (same invitation flow as Médicos).
     */
    public function save(InviteUser $inviteUser): void
    {
        $this->authorize('manage-users');

        if ($this->editingId !== null) {
            $this->updateMember();

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $inviteUser(new InviteUserData(
            name: $validated['name'],
            email: $validated['email'],
            role: UserRole::from($validated['role']),
        ));

        $this->showForm = false;
        $this->resetForm();
    }

    public function resendInvitation(int $userId, InviteUser $inviteUser): void
    {
        $this->authorize('manage-users');

        $user = User::findOrFail($userId);

        if ($user->invitation_accepted_at === null) {
            $inviteUser->send($user);
        }
    }

    public function revokeInvitation(int $userId): void
    {
        $this->authorize('manage-users');

        User::query()
            ->whereKey($userId)
            ->whereNull('invitation_accepted_at')
            ->delete();
    }

    public function toggleActive(int $userId): void
    {
        $this->authorize('manage-users');

        $user = User::findOrFail($userId);

        // Last-admin protection: never deactivate the clinic's only active admin.
        if ($user->active && $user->isAdmin() && $this->isLastActiveAdmin($user)) {
            return;
        }

        $user->update(['active' => ! $user->active]);
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render(): View
    {
        return view('livewire.settings.team', [
            'users' => User::orderBy('name')->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    private function updateMember(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $user = User::findOrFail($this->editingId);

        // Last-admin protection: never demote the clinic's only active admin.
        if ($user->isAdmin() && $validated['role'] !== UserRole::Admin->value && $this->isLastActiveAdmin($user)) {
            $this->addError('role', 'A clínica precisa de pelo menos um administrador.');

            return;
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        $this->showForm = false;
        $this->resetForm();
    }

    private function isLastActiveAdmin(User $user): bool
    {
        return User::query()
            ->where('role', UserRole::Admin)
            ->where('active', true)
            ->whereKeyNot($user->id)
            ->doesntExist();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email']);
        $this->role = 'staff';
    }
}
