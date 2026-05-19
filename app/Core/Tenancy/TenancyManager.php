<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use App\Central\Models\Tenant;
use Closure;

final class TenancyManager
{
    private ?Tenant $currentTenant = null;

    public function setCurrentTenant(?Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    public function getCurrentTenant(): ?Tenant
    {
        return $this->currentTenant;
    }

    public function currentId(): ?string
    {
        return $this->currentTenant?->getKey();
    }

    public function runForTenant(Tenant $tenant, Closure $callback): mixed
    {
        $previousTenant = $this->currentTenant;
        $this->setCurrentTenant($tenant);

        try {
            return $callback();
        } finally {
            $this->setCurrentTenant($previousTenant);
        }
    }
}

