<?php

declare(strict_types=1);

namespace App\Core\Services;

use Psr\Log\LoggerInterface;

abstract class BaseService
{
    public function __construct(
        protected readonly LoggerInterface $logger,
    ) {
    }
}

