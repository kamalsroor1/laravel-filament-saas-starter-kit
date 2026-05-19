<?php

declare(strict_types=1);

namespace App\Core\DTOs;

use ReflectionClass;

abstract readonly class BaseDTO
{
    public static function fromArray(array $data): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new static();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $data)) {
                $arguments[] = $data[$name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            $arguments[] = null;
        }

        return new static(...$arguments);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

