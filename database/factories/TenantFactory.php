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
            'database_connection' => 'tenant',
            'database_driver' => 'pgsql',
            'database_host' => env('DB_HOST', '127.0.0.1'),
            'database_port' => (int) env('DB_PORT', 5432),
            'database_name' => 'tenant_'.strtolower(fake()->unique()->bothify('??######')),
            'database_username' => env('DB_USERNAME'),
            'database_password' => env('DB_PASSWORD'),
            'status' => TenantStatus::Active,
            'settings' => ['locale' => 'en'],
            'trial_ends_at' => now()->addDays(14),
        ];
    }
}
