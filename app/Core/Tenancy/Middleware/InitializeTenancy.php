<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Middleware;

use App\Central\Models\Tenant;
use App\Core\Tenancy\TenancyManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class InitializeTenancy
{
    public function __construct(
        private readonly TenancyManager $tenancyManager,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower((string) $request->getHost());

        $tenant = Tenant::query()->where('domain', $host)->first();
        $this->tenancyManager->setCurrentTenant($tenant);

        return $next($request);
    }
}

