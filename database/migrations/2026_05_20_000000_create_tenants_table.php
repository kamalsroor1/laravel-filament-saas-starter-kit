<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('email');
            $table->string('domain')->unique();
            $table->string('status')->index();
            $table->string('database_connection')->default('tenant');
            $table->string('database_driver')->default('pgsql');
            $table->string('database_host')->nullable();
            $table->unsignedInteger('database_port')->nullable();
            $table->string('database_name');
            $table->string('database_username')->nullable();
            $table->string('database_password')->nullable();
            $table->jsonb('settings')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
