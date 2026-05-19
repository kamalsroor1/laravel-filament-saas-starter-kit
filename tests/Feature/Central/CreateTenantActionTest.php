<?php

declare(strict_types=1);

use App\Central\Actions\CreateTenantAction;
use App\Central\DTOs\CreateTenantDTO;
use App\Central\Enums\TenantStatus;
use App\Central\Events\TenantCreated;
use App\Central\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

it('creates tenant and fires tenant created event', function (): void {
    Event::fake();

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
