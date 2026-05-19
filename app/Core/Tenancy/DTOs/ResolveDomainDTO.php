<?php

declare(strict_types=1);

namespace App\Core\Tenancy\DTOs;

use App\Core\DTOs\BaseDTO;

final readonly class ResolveDomainDTO extends BaseDTO
{
    public function __construct(
        public string $domain,
        public ?string $tenant_id = null,
        public ?string $project_identifier = null,
        public ?bool $is_custom_domain = null,
    ) {
    }
}
