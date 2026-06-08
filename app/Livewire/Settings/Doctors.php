<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Actions\Settings\SaveDoctor;
use App\Actions\Settings\SaveDoctorData;
use App\Actions\Users\InviteUser;
use App\Actions\Users\InviteUserData;
use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Médicos')]
#[Layout('components.layouts.tenant')]
class Doctors extends Component
{
    public string $name = '';

    public string $crm = '';

    public string $phone = '';

    public string $email = '';

    /**
     * @var array<int, int>
     */
    public array $selectedSpecialties = [];

    public ?int $editingId = null;

    public function save(SaveDoctor $saveDoctor): void
    {
        $this->authorize('manage-clinic-settings');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'crm' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'selectedSpecialties' => ['array'],
            'selectedSpecialties.*' => ['integer', 'exists:specialties,id'],
        ]);

        $saveDoctor(new SaveDoctorData(
            name: $validated['name'],
            crm: $validated['crm'],
            phone: $validated['phone'] !== '' ? $validated['phone'] : null,
            email: $validated['email'] !== '' ? $validated['email'] : null,
            specialtyIds: array_map('intval', $this->selectedSpecialties),
            id: $this->editingId,
        ));

        $this->reset('name', 'crm', 'phone', 'email', 'selectedSpecialties', 'editingId');
    }

    public function edit(Doctor $doctor): void
    {
        $this->editingId = $doctor->id;
        $this->name = $doctor->name;
        $this->crm = $doctor->crm;
        $this->phone = $doctor->phone ?? '';
        $this->email = $doctor->email ?? '';
        $this->selectedSpecialties = $doctor->specialties()->pluck('specialties.id')->all();
    }

    public function toggle(Doctor $doctor): void
    {
        $this->authorize('manage-clinic-settings');

        $doctor->update(['active' => ! $doctor->active]);
    }

    public function cancel(): void
    {
        $this->reset('name', 'crm', 'phone', 'email', 'selectedSpecialties', 'editingId');
    }

    /**
     * Invite the doctor to log in (or resend if already invited). Creates a
     * pending doctor-user linked to this practitioner and e-mails the link.
     */
    public function inviteDoctor(int $doctorId, InviteUser $inviteUser): void
    {
        $this->authorize('manage-clinic-settings');

        $doctor = Doctor::findOrFail($doctorId);

        if ($doctor->email === null || $doctor->email === '') {
            return;
        }

        $user = User::query()->where('doctor_id', $doctor->id)->first();

        if ($user === null) {
            $inviteUser(new InviteUserData(
                name: $doctor->name,
                email: $doctor->email,
                role: UserRole::Doctor,
                doctorId: $doctor->id,
            ));

            return;
        }

        // Already has a login — resend only while the invitation is still open.
        if ($user->invitationStatus() !== InvitationStatus::Accepted) {
            $inviteUser->send($user);
        }
    }

    /**
     * Cancel a pending invitation — removes the doctor-user that has not yet accepted.
     */
    public function revokeInvitation(int $doctorId): void
    {
        $this->authorize('manage-clinic-settings');

        User::query()
            ->where('doctor_id', $doctorId)
            ->whereNull('invitation_accepted_at')
            ->delete();
    }

    public function render(): View
    {
        return view('livewire.settings.doctors', [
            'doctors' => Doctor::with('specialties')->orderBy('name')->get(),
            'specialties' => Specialty::where('active', true)->orderBy('name')->get(),
            'doctorUsers' => User::query()->whereNotNull('doctor_id')->get()->keyBy('doctor_id'),
        ]);
    }
}
