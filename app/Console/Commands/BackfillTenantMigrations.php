<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs tenant migration-state drift: when a `tenants:migrate` (or a tenant's
 * provisioning migrate) is killed mid-run, MySQL keeps the already-created table
 * (DDL can't roll back) but the `migrations` row is never written — so the next
 * migrate tries to re-create the table and fails with "table already exists".
 *
 * This records (backfills) any tenant migration whose table(s) already exist but
 * aren't in the `migrations` table. It never creates, drops, or alters a table —
 * it only writes the missing bookkeeping so `tenants:migrate` can proceed.
 */
class BackfillTenantMigrations extends Command
{
    protected $signature = 'tenants:backfill-migrations
        {--tenants=* : Restringe a tenants específicos (id). Vazio = todos.}
        {--pretend : Apenas relata o que faria; não grava nada.}';

    protected $description = 'Registra migrações de tenant cuja(s) tabela(s) já existem mas não estão em `migrations` (conserta drift de migrate interrompido).';

    public function handle(): int
    {
        $pretend = (bool) $this->option('pretend');

        /** @var array<int, string> $only */
        $only = (array) $this->option('tenants');

        $files = $this->tenantMigrationFiles();

        if ($files === []) {
            $this->warn('Nenhuma migração de tenant em '.database_path('migrations/tenant').'.');

            return self::SUCCESS;
        }

        $tenants = Tenant::query()
            ->when($only !== [], fn ($query) => $query->whereIn('id', $only))
            ->get();

        $total = 0;
        foreach ($tenants as $tenant) {
            if ($tenant instanceof Tenant) {
                $total += $this->backfillTenant($tenant, $files, $pretend);
            }
        }

        $this->info($pretend
            ? "Pré-visualização: {$total} migração(ões) seriam registradas."
            : "Concluído: {$total} migração(ões) registradas.");

        if (! $pretend && $total > 0) {
            $this->line('Agora rode: <fg=cyan>php artisan tenants:migrate --force</>');
        }

        return self::SUCCESS;
    }

    /**
     * Backfill one tenant, inside its database context. Returns how many migrations
     * were (or would be) recorded.
     *
     * @param  array<string, string>  $files  migration name => file path
     */
    private function backfillTenant(Tenant $tenant, array $files, bool $pretend): int
    {
        return (int) $tenant->run(function () use ($tenant, $files, $pretend): int {
            $key = (string) $tenant->getKey();

            if (! Schema::hasTable('migrations')) {
                $this->line("  [{$key}] sem tabela `migrations` — ignorado (o migrate normal a cria).");

                return 0;
            }

            $recorded = array_flip(DB::table('migrations')->pluck('migration')->all());

            $toRecord = [];
            foreach ($files as $name => $path) {
                if (isset($recorded[$name])) {
                    continue;
                }

                $tables = $this->tablesCreatedBy($path);

                if ($tables === []) {
                    $this->line("  [{$key}] {$name}: não cria tabela — deixado pendente.");

                    continue;
                }

                if ($this->allExist($tables)) {
                    $toRecord[] = $name;
                    $this->line("  [{$key}] {$name}: tabela(s) já existem → <fg=yellow>backfill</>.");
                } else {
                    $this->line("  [{$key}] {$name}: tabela ausente → pendente (o migrate normal cria).");
                }
            }

            if (! $pretend && $toRecord !== []) {
                $batch = (int) DB::table('migrations')->max('batch') + 1;
                DB::table('migrations')->insert(array_map(
                    fn (string $name): array => ['migration' => $name, 'batch' => $batch],
                    $toRecord,
                ));
            }

            return count($toRecord);
        });
    }

    /**
     * Tenant migration files, keyed by migration name (filename without `.php`),
     * sorted chronologically.
     *
     * @return array<string, string>
     */
    private function tenantMigrationFiles(): array
    {
        $files = glob(database_path('migrations/tenant').'/*.php') ?: [];
        sort($files);

        $map = [];
        foreach ($files as $path) {
            $map[basename($path, '.php')] = $path;
        }

        return $map;
    }

    /**
     * The table names a migration creates, parsed from its `Schema::create(...)`
     * calls (handles multi-table migrations like the permission tables).
     *
     * @return list<string>
     */
    private function tablesCreatedBy(string $path): array
    {
        preg_match_all(
            '/Schema::create\(\s*[\'"]([^\'"]+)[\'"]/',
            (string) file_get_contents($path),
            $matches,
        );

        return array_values(array_unique($matches[1]));
    }

    /**
     * @param  list<string>  $tables
     */
    private function allExist(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
