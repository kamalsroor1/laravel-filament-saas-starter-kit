<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Contracts;

use App\Central\Models\Tenant;

interface TenantProvisioningInterface
{
    public function createDatabase(Tenant $tenant): void;

    public function migrateTenant(Tenant $tenant): void;

    public function seedTenant(Tenant $tenant, string $seederClass = 'Database\\Seeders\\Tenant\\TenantDatabaseSeeder'): void;

    public function migrateAndSeedTenant(Tenant $tenant, string $seederClass = 'Database\\Seeders\\Tenant\\TenantDatabaseSeeder'): void;

    public function dropDatabaseIfExists(Tenant $tenant): void;
}

