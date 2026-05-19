<?php

declare(strict_types=1);

namespace App\Core\Module;

use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleLoader::class);
        $this->app->singleton(ModuleManager::class);
    }

    public function boot(ModuleManager $moduleManager): void
    {
        $moduleManager->boot();
    }
}

