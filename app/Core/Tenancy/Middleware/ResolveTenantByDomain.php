<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Middleware;

use App\Central\Models\Tenant;
use App\Core\Tenancy\Actions\ResolveDomainAction;
use App\Core\Tenancy\DTOs\ResolveDomainDTO;
use App\Core\Tenancy\TenancyManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenantByDomain
{
    public function __construct(
        private readonly ResolveDomainAction $resolveDomainAction,
        private readonly TenancyManager $tenancyManager,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $resolvedDomain = $this->resolveDomainAction->handle(
            new ResolveDomainDTO(domain: (string) $request->getHost()),
        );

        if ($resolvedDomain === null || $resolvedDomain->tenant_id === null) {
            abort(404);
        }

        $tenant = Tenant::query()->find($resolvedDomain->tenant_id);
        if ($tenant === null) {
            abort(404);
        }

        $request->attributes->set('project_identifier', $resolvedDomain->project_identifier);
        $request->attributes->set('resolved_domain', $resolvedDomain->domain);
        $request->attributes->set('is_custom_domain', $resolvedDomain->is_custom_domain);

        return $this->tenancyManager->runForTenant(
            $tenant,
            fn (): Response => $next($request),
        );
    }
}

