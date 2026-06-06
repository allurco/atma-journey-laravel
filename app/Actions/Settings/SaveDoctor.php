<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\Doctor;

/**
 * Upserts a doctor and synchronizes its many-to-many specialties pivot.
 */
class SaveDoctor
{
    public function __invoke(SaveDoctorData $data): Doctor
    {
        $attributes = [
            'name' => $data->name,
            'crm' => $data->crm,
            'phone' => $data->phone,
            'email' => $data->email,
        ];

        $doctor = $data->id !== null
            ? tap(Doctor::findOrFail($data->id))->update($attributes)
            : Doctor::create($attributes);

        $doctor->specialties()->sync($data->specialtyIds);

        return $doctor;
    }
}
