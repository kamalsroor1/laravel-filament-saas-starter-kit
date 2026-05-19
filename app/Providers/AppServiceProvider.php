<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Tenancy\Scopes\TenantScope;
use App\Core\Tenancy\TenancyManager;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
