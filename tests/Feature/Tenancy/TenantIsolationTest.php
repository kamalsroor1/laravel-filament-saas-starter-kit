<?php

declare(strict_types=1);

use App\Central\Models\Tenant;
use App\Core\Tenancy\TenancyManager;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

it('tenant database isolation prevents cross-tenant data access', function (): void {
    $tenantADatabase = database_path('tenant_'.Str::uuid().'.sqlite');
    $tenantBDatabase = database_path('tenant_'.Str::uuid().'.sqlite');

    File::put($tenantADatabase, '');
    File::put($tenantBDatabase, '');

    $tenantA = Tenant::query()->create([
        'name' => 'Tenant A',
        'email' => 'a@test.local',
        'domain' => 'a.test.local',
        'database_connection' => 'tenant',
        'database_driver' => 'sqlite',
        'database_host' => null,
        'database_port' => null,
        'database_name' => $tenantADatabase,
        'database_username' => null,
        'database_password' => null,
        'status' => 'active',
        'settings' => [],
        'trial_ends_at' => null,
    ]);
    $tenantB = Tenant::query()->create([
        'name' => 'Tenant B',
        'email' => 'b@test.local',
        'domain' => 'b.test.local',
        'database_connection' => 'tenant',
        'database_driver' => 'sqlite',
        'database_host' => null,
        'database_port' => null,
        'database_name' => $tenantBDatabase,
        'database_username' => null,
        'database_password' => null,
        'status' => 'active',
        'settings' => [],
        'trial_ends_at' => null,
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

    $userA = $tenancy->runForTenant($tenantA, fn (): User => User::query()->create([
        'name' => 'User A',
        'email' => 'user-a@test.local',
        'password' => Hash::make('password'),
    ]));
    $userB = $tenancy->runForTenant($tenantB, fn (): User => User::query()->create([
        'name' => 'User B',
        'email' => 'user-b@test.local',
        'password' => Hash::make('password'),
    ]));

    $visibleInA = $tenancy->runForTenant(
        $tenantA,
        fn (): ?User => User::query()->where('email', $userB->email)->first(),
    );
    $visibleInB = $tenancy->runForTenant(
        $tenantB,
        fn (): ?User => User::query()->where('email', $userA->email)->first(),
    );

    expect($visibleInA)->toBeNull()
        ->and($visibleInB)->toBeNull();

    File::delete($tenantADatabase);
    File::delete($tenantBDatabase);
});
