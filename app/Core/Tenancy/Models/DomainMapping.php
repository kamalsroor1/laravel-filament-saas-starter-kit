<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models;

use App\Central\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DomainMapping extends Model
{
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'project_identifier',
        'domain',
        'is_custom_domain',
    ];

    protected function casts(): array
    {
        return [
            'is_custom_domain' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

