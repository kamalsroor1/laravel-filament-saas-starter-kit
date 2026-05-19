<?php

declare(strict_types=1);

use App\Central\Actions\CreateTenantAction;
use App\Central\DTOs\CreateTenantDTO;
use App\Central\Enums\TenantStatus;
use App\Central\Events\TenantCreated;
use App\Central\Models\Tenant;
use App\Core\Tenancy\Contracts\TenantProvisioningInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery\MockInterface;

it('creates tenant and fires tenant created event', function (): void {
    Event::fake();
    $this->mock(TenantProvisioningInterface::class, function (MockInterface $mock): void {
        $mock->shouldReceive('createDatabase')->once();
        $mock->shouldReceive('migrateAndSeedTenant')->once();
    });

    $dto = new CreateTenantDTO(
        name: 'Acme Inc',
        email: 'ops@acme.test',
        domain: 'acme.localhost',
        database_name: database_path('tenant_'.Str::uuid().'.sqlite'),
        database_driver: 'sqlite',
        status: TenantStatus::Trial,
        settings: ['timezone' => 'UTC'],
        trial_ends_at: CarbonImmutable::parse('2026-06-01 00:00:00'),
    );

    $tenant = app(CreateTenantAction::class)->handle($dto);

    expect($tenant)->toBeInstanceOf(Tenant::class)
        ->and($tenant->exists)->toBeTrue()
        ->and($tenant->domain)->toBe('acme.localhost');

    Event::assertDispatched(TenantCreated::class);
});
