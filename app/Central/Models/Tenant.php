<?php

declare(strict_types=1);

namespace App\Central\Models;

use App\Central\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Tenant extends Model
{
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'domain',
        'status',
        'database_connection',
        'database_driver',
        'database_host',
        'database_port',
        'database_name',
        'database_username',
        'database_password',
        'settings',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
            'status' => TenantStatus::class,
        ];
    }
}
