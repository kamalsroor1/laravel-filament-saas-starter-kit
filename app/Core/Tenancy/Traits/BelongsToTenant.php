<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Traits;

use App\Core\Tenancy\Scopes\TenantScope;
use App\Core\Tenancy\TenancyManager;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(app(TenantScope::class));

        static::creating(function ($model): void {
            if (! isset($model->tenant_id)) {
                $tenantId = app(TenancyManager::class)->currentId();
                if ($tenantId !== null) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }
}

