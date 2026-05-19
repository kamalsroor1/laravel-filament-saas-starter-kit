<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_mappings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->index()->constrained('tenants')->cascadeOnDelete();
            $table->string('project_identifier');
            $table->string('domain')->unique();
            $table->boolean('is_custom_domain')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'project_identifier', 'is_custom_domain'], 'domain_mappings_tenant_project_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_mappings');
    }
};

