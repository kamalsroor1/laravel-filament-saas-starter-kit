<?php

declare(strict_types=1);

namespace App\Core\Module\Contracts;

interface ModuleInterface
{
    public function boot(): void;
}

