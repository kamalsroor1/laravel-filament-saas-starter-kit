<?php

declare(strict_types=1);

namespace App\Central\Actions;

use App\Central\DTOs\CreateTenantDTO;
use App\Central\Events\TenantCreated;
use App\Central\Models\Tenant;
use App\Central\Repositories\TenantRepository;
use App\Core\Actions\BaseAction;
use Illuminate\Support\Facades\Event;

final class CreateTenantAction extends BaseAction
{
    public function __construct(
        private readonly TenantRepository $tenantRepository,
    ) {
    }

    public function handle(mixed ...$arguments): Tenant
    {
        /** @var CreateTenantDTO $dto */
        $dto = $arguments[0];

        /** @var Tenant $tenant */
        $tenant = $this->tenantRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'domain' => $dto->domain,
            'status' => $dto->status->value,
            'settings' => $dto->settings,
            'trial_ends_at' => $dto->trial_ends_at,
        ]);

        // Placeholder: default role seeding hook for central tenant onboarding.
        Event::dispatch(new TenantCreated($tenant));

        return $tenant;
    }
}

