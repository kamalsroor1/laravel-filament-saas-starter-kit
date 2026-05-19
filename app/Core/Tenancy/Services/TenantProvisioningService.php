<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Services;

use App\Central\Models\Tenant;
use App\Core\Tenancy\Contracts\TenantProvisioningInterface;
use App\Core\Tenancy\TenancyManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

final class TenantProvisioningService implements TenantProvisioningInterface
{
    public function __construct(
        private readonly TenancyManager $tenancyManager,
    ) {
    }

    public function createDatabase(Tenant $tenant): void
    {
        if ($tenant->database_driver !== 'pgsql') {
            throw new \InvalidArgumentException('Only pgsql tenant provisioning is supported.');
        }

        $databaseName = str_replace('"', '""', (string) $tenant->database_name);
        DB::statement(sprintf('CREATE DATABASE "%s"', $databaseName));
    }

    public function migrateTenant(Tenant $tenant): void
    {
        $this->tenancyManager->runForTenant($tenant, function (): void {
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        });
    }

    public function seedTenant(Tenant $tenant, string $seederClass = 'Database\\Seeders\\Tenant\\TenantDatabaseSeeder'): void
    {
        $this->tenancyManager->runForTenant($tenant, function () use ($seederClass): void {
            Artisan::call('db:seed', [
                '--database' => 'tenant',
                '--class' => $seederClass,
                '--force' => true,
            ]);
        });
    }

    public function migrateAndSeedTenant(Tenant $tenant, string $seederClass = 'Database\\Seeders\\Tenant\\TenantDatabaseSeeder'): void
    {
        $this->migrateTenant($tenant);
        $this->seedTenant($tenant, $seederClass);
    }

    public function dropDatabaseIfExists(Tenant $tenant): void
    {
        if ($tenant->database_driver !== 'pgsql') {
            return;
        }

        $databaseName = str_replace('"', '""', (string) $tenant->database_name);
        DB::statement(sprintf('DROP DATABASE IF EXISTS "%s"', $databaseName));
    }
}
