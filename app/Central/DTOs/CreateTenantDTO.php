<?php

declare(strict_types=1);

namespace App\Central\DTOs;

use App\Central\Enums\TenantStatus;
use App\Core\DTOs\BaseDTO;
use Carbon\CarbonImmutable;

final readonly class CreateTenantDTO extends BaseDTO
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $domain,
        public TenantStatus $status = TenantStatus::Trial,
        public array $settings = [],
        public ?CarbonImmutable $trial_ends_at = null,
    ) {
    }
}

