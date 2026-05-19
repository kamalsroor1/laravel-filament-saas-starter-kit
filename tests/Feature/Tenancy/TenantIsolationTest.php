<?php

declare(strict_types=1);

use App\Central\Models\Tenant;
use App\Core\Tenancy\TenancyManager;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

it('tenant database isolation prevents cross-tenant data access', function (): void {
    $tenantADatabase = database_path('tenant_'.Str::uuid().'.sqlite');
    $tenantBDatabase = database_path('tenant_'.Str::uuid().'.sqlite');

    File::put($tenantADatabase, '');
    File::put($tenantBDatabase, '');

    $tenantA = Tenant::factory()->create([
        'database_driver' => 'sqlite',
        'database_name' => $tenantADatabase,
    ]);
    $tenantB = Tenant::factory()->create([
        'database_driver' => 'sqlite',
        'database_name' => $tenantBDatabase,
    ]);
    $tenancy = app(TenancyManager::class);

    $createUsersTable = function (): void {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }
    };

    $tenancy->runForTenant($tenantA, $createUsersTable);
    $tenancy->runForTenant($tenantB, $createUsersTable);

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

    File::delete($tenantADatabase);
    File::delete($tenantBDatabase);
});
