<?php

declare(strict_types=1);

namespace App\Console\Commands\Tenancy;

use App\Central\Models\Tenant;
use App\Core\Tenancy\Contracts\TenantProvisioningInterface;
use Illuminate\Console\Command;

final class SeedTenantsCommand extends Command
{
    protected $signature = 'tenants:seed
        {--class=Database\\Seeders\\Tenant\\TenantDatabaseSeeder : Seeder class}
        {--tenant-id= : Run seeder for one tenant only}';

    protected $description = 'Run tenant seeders for all tenants (or a specific tenant).';

    public function handle(TenantProvisioningInterface $provisioningService): int
    {
        $tenantId = $this->option('tenant-id');
        $seederClass = (string) $this->option('class');

        $query = Tenant::query();
        if (is_string($tenantId) && $tenantId !== '') {
            $query->whereKey($tenantId);
        }

        $tenants = $query->get();

        foreach ($tenants as $tenant) {
            $this->line('Seeding tenant: '.$tenant->id.' ('.$tenant->database_name.')');
            $provisioningService->seedTenant($tenant, $seederClass);
        }

        $this->info('Tenant seeding completed.');

        return self::SUCCESS;
    }
}
