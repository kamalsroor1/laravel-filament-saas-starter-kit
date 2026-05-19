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
        public string $database_name,
        public string $database_connection = 'tenant',
        public string $database_driver = 'pgsql',
        public ?string $database_host = null,
        public ?int $database_port = null,
        public ?string $database_username = null,
        public ?string $database_password = null,
        public TenantStatus $status = TenantStatus::Trial,
        public array $settings = [],
        public ?CarbonImmutable $trial_ends_at = null,
    ) {
    }
}
