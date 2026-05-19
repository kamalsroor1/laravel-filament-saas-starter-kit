<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Services;

use App\Core\Tenancy\DTOs\ResolveDomainDTO;
use App\Core\Tenancy\Models\DomainMapping;
use App\Core\Tenancy\Repositories\DomainRepository;
use Illuminate\Support\Facades\Cache;

final class DomainRoutingService
{
    public function __construct(
        private readonly DomainRepository $domainRepository,
    ) {
    }

    public function resolve(string $host): ?ResolveDomainDTO
    {
        $normalizedHost = strtolower($host);
        $cacheKey = 'domain_lookup_'.md5($normalizedHost);

        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return ResolveDomainDTO::fromArray($cached);
        }

        $mapping = $this->resolveMapping($normalizedHost);
        if ($mapping === null) {
            return null;
        }

        $dto = new ResolveDomainDTO(
            domain: $normalizedHost,
            tenant_id: (string) $mapping->tenant_id,
            project_identifier: (string) $mapping->project_identifier,
            is_custom_domain: (bool) $mapping->is_custom_domain,
        );

        Cache::put($cacheKey, $dto->toArray(), now()->addMinutes(10));
        Cache::put(
            sprintf('tenant_%s_domain_%s', $dto->tenant_id, md5($normalizedHost)),
            $dto->toArray(),
            now()->addMinutes(10),
        );

        return $dto;
    }

    private function resolveMapping(string $host): ?DomainMapping
    {
        $customDomain = $this->domainRepository->findByCustomDomain($host);
        if ($customDomain !== null) {
            return $customDomain;
        }

        $centralDomain = (string) config('app.central_domain', env('CENTRAL_DOMAIN', 'localhost'));

        return $this->domainRepository->findByProjectSubdomain($host, $centralDomain);
    }
}
