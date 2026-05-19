<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Core\Module\ModuleServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    ModuleServiceProvider::class,
    HorizonServiceProvider::class,
    TelescopeServiceProvider::class,
];
