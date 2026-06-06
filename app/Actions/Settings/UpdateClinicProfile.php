<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\Clinic;
use App\Models\Tenant;

final class UpdateClinicProfile
{
    public function __invoke(UpdateClinicProfileData $data): Clinic
    {
        $clinic = Clinic::current();
        $nameChanged = $clinic->name !== $data->name;

        $clinic->update([
            'name' => $data->name,
            'cnpj' => $data->cnpj,
            'email' => $data->email,
            'phone' => $data->phone,
            'address' => $data->address,
        ]);

        if ($nameChanged) {
            $this->syncCentralName($data->name);
        }

        return $clinic;
    }

    /**
     * Keep the central tenants.name in sync (a denormalized copy used for routing,
     * the sidebar, and billing). The Tenant record lives in the central database,
     * so the update runs in the central context.
     */
    private function syncCentralName(string $name): void
    {
        $tenantId = tenant('id');

        tenancy()->central(function () use ($tenantId, $name): void {
            Tenant::query()->whereKey($tenantId)->update(['name' => $name]);
        });
    }
}
