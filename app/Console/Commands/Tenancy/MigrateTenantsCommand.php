<?php

declare(strict_types=1);

namespace App\Console\Commands\Tenancy;

use App\Central\Models\Tenant;
use App\Core\Tenancy\Contracts\TenantProvisioningInterface;
use Illuminate\Console\Command;

final class MigrateTenantsCommand extends Command
{
    protected $signature = 'tenants:migrate {--tenant-id= : Run migration for one tenant only}';

    protected $description = 'Run tenant migrations for all tenants (or a specific tenant).';

    public function handle(TenantProvisioningInterface $provisioningService): int
    {
        $tenantId = $this->option('tenant-id');

        $query = Tenant::query();
        if (is_string($tenantId) && $tenantId !== '') {
            $query->whereKey($tenantId);
        }

        $tenants = $query->get();

        foreach ($tenants as $tenant) {
            $this->line('Migrating tenant: '.$tenant->id.' ('.$tenant->database_name.')');
            $provisioningService->migrateTenant($tenant);
        }

        $this->info('Tenant migrations completed.');

        return self::SUCCESS;
    }
}
