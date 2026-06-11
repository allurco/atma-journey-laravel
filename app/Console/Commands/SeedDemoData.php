<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

/**
 * Seeds a presentation-ready demo dataset into a chosen tenant database. Pick the
 * clinic interactively, or pass its id/slug. Safe to run against a live demo
 * tenant; `--fresh` wipes the demo-owned tables first for a clean rehearsal.
 */
class SeedDemoData extends Command
{
    protected $signature = 'demo:seed
        {tenant? : Id ou slug do tenant (clínica). Se omitido, escolha na lista.}
        {--fresh : Apaga os dados de demonstração existentes antes de semear.}';

    protected $description = 'Popula um tenant com dados de demonstração para o pitch (pipeline, agenda, orçamentos, KPIs).';

    public function handle(): int
    {
        $tenant = $this->resolveTenant();

        if ($tenant === null) {
            $this->error('Nenhum tenant encontrado. Crie uma clínica primeiro (cadastro/self-serve).');

            return self::FAILURE;
        }

        $fresh = (bool) $this->option('fresh');

        $this->newLine();
        $this->warn("  Tenant escolhido: {$tenant->name} ({$tenant->slug})");
        if ($fresh) {
            $this->warn('  --fresh: os dados de demonstração existentes neste tenant serão APAGADOS.');
        }

        if (! confirm(label: 'Semear dados de demonstração neste tenant?', default: true)) {
            $this->info('Cancelado.');

            return self::SUCCESS;
        }

        $tenant->run(function () use ($fresh): void {
            $seeder = new DemoSeeder;
            $seeder->fresh = $fresh;
            $seeder->setContainer($this->laravel);
            $seeder->setCommand($this);
            $seeder->run();
        });

        return self::SUCCESS;
    }

    /**
     * The tenant from the argument (id or slug), or an interactive picker.
     */
    private function resolveTenant(): ?Tenant
    {
        $arg = $this->argument('tenant');

        if (is_string($arg) && $arg !== '') {
            $tenant = Tenant::query()->where('id', $arg)->orWhere('slug', $arg)->first();

            return $tenant instanceof Tenant ? $tenant : null;
        }

        // Narrow the stancl base-tenant query results to our concrete model.
        $tenants = [];
        foreach (Tenant::query()->orderBy('name')->get() as $tenant) {
            if ($tenant instanceof Tenant) {
                $tenants[] = $tenant;
            }
        }

        if ($tenants === []) {
            return null;
        }

        if (count($tenants) === 1) {
            return $tenants[0];
        }

        $options = [];
        foreach ($tenants as $tenant) {
            $options[(string) $tenant->getKey()] = "{$tenant->name} ({$tenant->slug})";
        }

        $id = select(
            label: 'Em qual clínica (tenant) semear os dados de demonstração?',
            options: $options,
        );

        foreach ($tenants as $tenant) {
            if ((string) $tenant->getKey() === $id) {
                return $tenant;
            }
        }

        return null;
    }
}
