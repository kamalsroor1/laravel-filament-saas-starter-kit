<?php

declare(strict_types=1);

namespace App\Central\Enums;

enum TenantStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Trial = 'trial';
    case Cancelled = 'cancelled';
}

