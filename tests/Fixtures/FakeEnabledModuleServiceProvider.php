<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Support\ServiceProvider;

final class FakeEnabledModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config()->set('tests.enabled_module_provider_registered', true);
    }
}

