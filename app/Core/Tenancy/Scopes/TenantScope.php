<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Scopes;

use App\Core\Tenancy\TenancyManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TenantScope implements Scope
{
    public function __construct(
        private readonly TenancyManager $tenancyManager,
    ) {
    }

    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = $this->tenancyManager->currentId();

        if ($tenantId === null) {
            return;
        }

        $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
    }
}

