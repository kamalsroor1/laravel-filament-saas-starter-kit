<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Tenancy\DTOs\ResolveDomainDTO;
use App\Core\Tenancy\Services\DomainRoutingService;

final class ResolveDomainAction extends BaseAction
{
    public function __construct(
        private readonly DomainRoutingService $domainRoutingService,
    ) {
    }

    public function handle(mixed ...$arguments): ?ResolveDomainDTO
    {
        $dto = $arguments[0];

        return $this->domainRoutingService->resolve($dto->domain);
    }
}

