<?php

declare(strict_types=1);

namespace App\Console\Commands\Tenancy;

use App\Central\Actions\CreateTenantAction;
use App\Central\DTOs\CreateTenantDTO;
use App\Central\Enums\TenantStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class CreateTenantCommand extends Command
{
    protected $signature = 'tenant:create
        {name : Tenant name}
        {email : Tenant email}
        {domain : Tenant domain}
        {--db-name= : Tenant database name}
        {--db-host= : Tenant database host}
        {--db-port= : Tenant database port}
        {--db-user= : Tenant database username}
        {--db-pass= : Tenant database password}';

    protected $description = 'Create a new tenant, provision a PostgreSQL database, then run tenant migrations and seeders.';

    public function handle(CreateTenantAction $action): int
    {
        $name = (string) $this->argument('name');
        $email = (string) $this->argument('email');
        $domain = (string) $this->argument('domain');

        $databaseName = (string) ($this->option('db-name') ?: ('tenant_'.Str::lower(Str::random(10))));

        $dto = new CreateTenantDTO(
            name: $name,
            email: $email,
            domain: $domain,
            database_name: $databaseName,
            database_connection: 'tenant',
            database_driver: 'pgsql',
            database_host: (string) ($this->option('db-host') ?: env('DB_HOST', '127.0.0.1')),
            database_port: (int) ($this->option('db-port') ?: env('DB_PORT', 5432)),
            database_username: (string) ($this->option('db-user') ?: env('DB_USERNAME', '')),
            database_password: (string) ($this->option('db-pass') ?: env('DB_PASSWORD', '')),
            status: TenantStatus::Trial,
            settings: [],
            trial_ends_at: null,
        );

        $tenant = $action->handle($dto);

        $this->info('Tenant created successfully.');
        $this->line('Tenant ID: '.$tenant->id);
        $this->line('Tenant DB: '.$tenant->database_name);

        return self::SUCCESS;
    }
}

