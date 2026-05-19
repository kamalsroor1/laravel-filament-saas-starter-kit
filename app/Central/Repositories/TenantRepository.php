<?php

declare(strict_types=1);

namespace App\Central\Repositories;

use App\Central\Models\Tenant;
use App\Core\Repositories\BaseRepository;

final class TenantRepository extends BaseRepository
{
    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }
}

