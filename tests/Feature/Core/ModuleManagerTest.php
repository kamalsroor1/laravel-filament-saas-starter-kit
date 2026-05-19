<?php

declare(strict_types=1);

use App\Core\Module\ModuleManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::deleteDirectory(base_path('Modules'));
    File::makeDirectory(base_path('Modules/EnabledModule'), 0755, true);
    File::makeDirectory(base_path('Modules/DisabledModule'), 0755, true);
    File::makeDirectory(base_path('Modules/SaasOnlyModule'), 0755, true);

    File::put(base_path('Modules/EnabledModule/module.json'), json_encode([
        'name' => 'EnabledModule',
        'alias' => 'tenancy',
        'enabled' => true,
        'providers' => ['Tests\\Fixtures\\FakeEnabledModuleServiceProvider'],
        'requires' => [],
        'saas_only' => false,
    ], JSON_THROW_ON_ERROR));

    File::put(base_path('Modules/DisabledModule/module.json'), json_encode([
        'name' => 'DisabledModule',
        'alias' => 'billing',
        'enabled' => true,
        'providers' => ['Tests\\Fixtures\\FakeEnabledModuleServiceProvider'],
        'requires' => [],
        'saas_only' => false,
    ], JSON_THROW_ON_ERROR));

    File::put(base_path('Modules/SaasOnlyModule/module.json'), json_encode([
        'name' => 'SaasOnlyModule',
        'alias' => 'webhooks',
        'enabled' => true,
        'providers' => ['Tests\\Fixtures\\FakeEnabledModuleServiceProvider'],
        'requires' => [],
        'saas_only' => true,
    ], JSON_THROW_ON_ERROR));

    config()->set('modules.modules.tenancy', true);
    config()->set('modules.modules.billing', false);
    config()->set('modules.modules.webhooks', true);
    config()->set('modules.saas_enabled', true);
    config()->set('tests.enabled_module_provider_registered', false);

    Cache::forget('modules:config');
});

afterEach(function (): void {
    Cache::forget('modules:config');
    File::deleteDirectory(base_path('Modules'));
});

it('returns true for enabled module alias', function (): void {
    $manager = app(ModuleManager::class);

    expect($manager->isEnabled('tenancy'))->toBeTrue();
});

it('returns false for disabled module alias', function (): void {
    $manager = app(ModuleManager::class);

    expect($manager->isEnabled('billing'))->toBeFalse();
});

it('returns only enabled modules from getEnabled', function (): void {
    $manager = app(ModuleManager::class);

    $aliases = $manager->getEnabled()->pluck('alias')->values()->all();

    expect($aliases)->toBe(['tenancy', 'webhooks']);
});

it('registers providers for enabled modules only during boot', function (): void {
    $manager = app(ModuleManager::class);

    $manager->boot();

    expect((bool) config('tests.enabled_module_provider_registered'))->toBeTrue()
        ->and($manager->isEnabled('billing'))->toBeFalse();
});

it('disables saas only modules when saas mode is off', function (): void {
    config()->set('modules.saas_enabled', false);
    Cache::forget('modules:config');

    $manager = app(ModuleManager::class);

    expect($manager->isEnabled('webhooks'))->toBeFalse();
});

