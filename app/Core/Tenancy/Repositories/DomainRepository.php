<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Repositories;

use App\Core\Tenancy\Models\DomainMapping;

final class DomainRepository
{
    public function findByCustomDomain(string $domain): ?DomainMapping
    {
        return DomainMapping::query()
            ->where('domain', strtolower($domain))
            ->where('is_custom_domain', true)
            ->first();
    }

    public function findByProjectSubdomain(string $domain, string $centralDomain): ?DomainMapping
    {
        $normalizedDomain = strtolower($domain);
        $normalizedCentralDomain = strtolower($centralDomain);

        if (! str_ends_with($normalizedDomain, '.'.$normalizedCentralDomain)) {
            return null;
        }

        $projectIdentifier = (string) str($normalizedDomain)->before('.'.$normalizedCentralDomain);

        if ($projectIdentifier === '' || str_contains($projectIdentifier, '.')) {
            return null;
        }

        return DomainMapping::query()
            ->where('project_identifier', $projectIdentifier)
            ->where('is_custom_domain', false)
            ->first();
    }
}

