<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use App\Central\Models\Tenant;
use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

final class TenancyManager
{
    private ?Tenant $currentTenant = null;
    private ?string $previousDefaultConnection = null;

    public function setCurrentTenant(?Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    public function getCurrentTenant(): ?Tenant
    {
        return $this->currentTenant;
    }

    public function currentId(): ?string
    {
        return $this->currentTenant?->getKey();
    }

    public function runForTenant(Tenant $tenant, Closure $callback): mixed
    {
        $previousTenant = $this->currentTenant;
        $this->previousDefaultConnection = Config::get('database.default');

        $this->setCurrentTenant($tenant);
        $this->configureTenantConnection($tenant);
        Config::set('database.default', 'tenant');
        DB::purge('tenant');
        DB::reconnect('tenant');

        try {
            return $callback();
        } finally {
            $this->setCurrentTenant($previousTenant);
            DB::disconnect('tenant');
            Config::set('database.default', $this->previousDefaultConnection);
        }
    }

    private function configureTenantConnection(Tenant $tenant): void
    {
        $baseConnection = (array) Config::get('database.connections.tenant', []);

        $connection = array_merge($baseConnection, [
            'driver' => $tenant->database_driver ?: ($baseConnection['driver'] ?? 'pgsql'),
            'host' => $tenant->database_host ?: ($baseConnection['host'] ?? env('DB_HOST', '127.0.0.1')),
            'port' => $tenant->database_port ?: ($baseConnection['port'] ?? env('DB_PORT', '5432')),
            'database' => $tenant->database_name,
            'username' => $tenant->database_username ?: ($baseConnection['username'] ?? env('DB_USERNAME', '')),
            'password' => $tenant->database_password ?: ($baseConnection['password'] ?? env('DB_PASSWORD', '')),
        ]);

        Config::set('database.connections.tenant', $connection);
    }
}
