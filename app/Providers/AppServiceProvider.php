<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Tenancy\Scopes\TenantScope;
use App\Core\Tenancy\TenancyManager;
use App\Core\Tenancy\Contracts\TenantProvisioningInterface;
use App\Core\Tenancy\Services\TenantProvisioningService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenancyManager::class);
        $this->app->singleton(TenantScope::class);
        $this->app->bind(TenantProvisioningInterface::class, TenantProvisioningService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
