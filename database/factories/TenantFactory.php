<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Central\Enums\TenantStatus;
use App\Central\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
            'domain' => fake()->unique()->domainName(),
            'status' => TenantStatus::Active,
            'settings' => ['locale' => 'en'],
            'trial_ends_at' => now()->addDays(14),
        ];
    }
}

