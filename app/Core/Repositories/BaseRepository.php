<?php

declare(strict_types=1);

namespace App\Core\Repositories;

use App\Core\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

abstract class BaseRepository implements RepositoryInterface
{
    public function __construct(
        protected readonly Model $model,
    ) {
    }

    public function findById(string|int $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    public function findAll(): Collection
    {
        return $this->model->newQuery()->get();
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(string|int $id, array $data): Model
    {
        $record = $this->model->newQuery()->find($id);

        if ($record === null) {
            throw new ModelNotFoundException(sprintf(
                '%s record with key [%s] was not found.',
                $this->model::class,
                (string) $id,
            ));
        }

        $record->fill($data);
        $record->save();

        return $record->fresh() ?? $record;
    }

    public function delete(string|int $id): bool
    {
        $record = $this->model->newQuery()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->delete();
    }
}

