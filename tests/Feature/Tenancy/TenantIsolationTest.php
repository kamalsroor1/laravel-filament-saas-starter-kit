<?php

declare(strict_types=1);

use App\Central\Models\Tenant;
use App\Core\Tenancy\TenancyManager;
use App\Models\User;

it('tenant scope prevents cross-tenant data access', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $tenancy = app(TenancyManager::class);

    $userA = $tenancy->runForTenant($tenantA, fn (): User => User::factory()->create());
    $userB = $tenancy->runForTenant($tenantB, fn (): User => User::factory()->create());

    $visibleInA = $tenancy->runForTenant(
        $tenantA,
        fn (): ?User => User::query()->find($userB->getKey()),
    );
    $visibleInB = $tenancy->runForTenant(
        $tenantB,
        fn (): ?User => User::query()->find($userA->getKey()),
    );

    expect($visibleInA)->toBeNull()
        ->and($visibleInB)->toBeNull();
});

