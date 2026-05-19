<?php

declare(strict_types=1);

namespace App\Core\Actions;

abstract class BaseAction
{
    abstract public function handle(mixed ...$arguments): mixed;
}

