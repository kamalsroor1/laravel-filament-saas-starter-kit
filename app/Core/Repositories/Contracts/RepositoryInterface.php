<?php

declare(strict_types=1);

namespace App\Core\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    public function findById(string|int $id): ?Model;

    public function findAll(): Collection;

    public function create(array $data): Model;

    public function update(string|int $id, array $data): Model;

    public function delete(string|int $id): bool;
}

