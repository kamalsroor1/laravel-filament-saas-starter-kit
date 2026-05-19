<?php

declare(strict_types=1);

use App\Central\Models\Tenant;
use App\Core\Tenancy\Models\DomainMapping;
use App\Core\Tenancy\TenancyManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

beforeEach(function (): void {
    config()->set('app.central_domain', 'example.com');
    Cache::flush();
});

it('resolves project by subdomain correctly', function (): void {
    $tenant = createDomainTestTenant('tenant-subdomain');

    DomainMapping::query()->create([
        'tenant_id' => $tenant->id,
        'project_identifier' => 'alpha',
        'domain' => 'alpha.example.com',
        'is_custom_domain' => false,
    ]);

    $response = $this->get('http://alpha.example.com/whoami');

    $response->assertOk()
        ->assertJson([
            'tenant_id' => $tenant->id,
            'project_identifier' => 'alpha',
            'resolved_domain' => 'alpha.example.com',
            'is_custom_domain' => false,
        ]);
});

it('resolves project by custom domain correctly', function (): void {
    $tenant = createDomainTestTenant('tenant-custom');

    DomainMapping::query()->create([
        'tenant_id' => $tenant->id,
        'project_identifier' => 'beta',
        'domain' => 'app.client-a.com',
        'is_custom_domain' => true,
    ]);

    $response = $this->get('http://app.client-a.com/whoami');

    $response->assertOk()
        ->assertJson([
            'tenant_id' => $tenant->id,
            'project_identifier' => 'beta',
            'resolved_domain' => 'app.client-a.com',
            'is_custom_domain' => true,
        ]);
});

it('custom domain takes priority over subdomain collision', function (): void {
    $subdomainTenant = createDomainTestTenant('tenant-subdomain-collision');
    $customDomainTenant = createDomainTestTenant('tenant-custom-collision');

    DomainMapping::query()->create([
        'tenant_id' => $subdomainTenant->id,
        'project_identifier' => 'gamma',
        'domain' => 'gamma-subdomain-record.example.com',
        'is_custom_domain' => false,
    ]);

    DomainMapping::query()->create([
        'tenant_id' => $customDomainTenant->id,
        'project_identifier' => 'custom-gamma',
        'domain' => 'gamma.example.com',
        'is_custom_domain' => true,
    ]);

    $response = $this->get('http://gamma.example.com/whoami');

    $response->assertOk()
        ->assertJson([
            'tenant_id' => $customDomainTenant->id,
            'project_identifier' => 'custom-gamma',
            'is_custom_domain' => true,
        ]);
});

it('unknown domain returns not found and no tenant context is booted', function (): void {
    $response = $this->get('http://unknown-domain.example.com/whoami');

    $response->assertNotFound();
    expect(app(TenancyManager::class)->getCurrentTenant())->toBeNull();
});

function createDomainTestTenant(string $prefix): Tenant
{
    return Tenant::query()->create([
        'name' => $prefix,
        'email' => $prefix.'@test.local',
        'domain' => $prefix.'.example.com',
        'database_connection' => 'tenant',
        'database_driver' => 'sqlite',
        'database_host' => null,
        'database_port' => null,
        'database_name' => database_path('tenant_'.Str::uuid().'.sqlite'),
        'database_username' => null,
        'database_password' => null,
        'status' => 'active',
        'settings' => [],
        'trial_ends_at' => null,
    ]);
}
