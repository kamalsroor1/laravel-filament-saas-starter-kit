<?php

declare(strict_types=1);

namespace App\Core\Module\DTOs;

use App\Core\DTOs\BaseDTO;

final readonly class ModuleConfigDTO extends BaseDTO
{
    /**
     * @param  array<int, string>  $providers
     * @param  array<int, string>  $requires
     */
    public function __construct(
        public string $name,
        public string $alias,
        public bool $enabled = true,
        public array $providers = [],
        public array $requires = [],
        public bool $saas_only = false,
    ) {
    }
}

