<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Contracts;

interface TenantAware
{
    public function tenantId(): ?string;
}

