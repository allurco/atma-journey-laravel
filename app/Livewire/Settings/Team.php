<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

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

    public string $password = '';

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

    public function save(): void
    {
        $this->authorize('manage-users');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        // Last-admin protection: never demote the clinic's only active admin.
        if ($this->editingId !== null && $validated['role'] === UserRole::Staff->value) {
            $user = User::findOrFail($this->editingId);

            if ($user->isAdmin() && $this->isLastActiveAdmin($user)) {
                $this->addError('role', 'A clínica precisa de pelo menos um administrador.');

                return;
            }
        }

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if ($validated['password'] !== null && $validated['password'] !== '') {
            $attributes['password'] = $validated['password'];
        }

        if ($this->editingId !== null) {
            User::findOrFail($this->editingId)->update($attributes);
        } else {
            User::create($attributes + ['active' => true]);
        }

        $this->showForm = false;
        $this->resetForm();
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
        $this->reset(['editingId', 'name', 'email', 'password']);
        $this->role = 'staff';
    }
}
