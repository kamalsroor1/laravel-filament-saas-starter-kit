<?php

declare(strict_types=1);

namespace App\Core\Module;

use App\Core\Module\DTOs\ModuleConfigDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

final class ModuleLoader
{
    /**
     * @return Collection<int, ModuleConfigDTO>
     */
    public function loadAll(): Collection
    {
        $modulesPath = base_path('Modules');

        if (! File::isDirectory($modulesPath)) {
            return collect();
        }

        $jsonFiles = File::glob($modulesPath.'/*/module.json') ?: [];

        return collect($jsonFiles)
            ->map(fn (string $file): ModuleConfigDTO => $this->loadOne($file));
    }

    public function loadOne(string $moduleJsonPath): ModuleConfigDTO
    {
        $payload = json_decode((string) File::get($moduleJsonPath), true, 512, JSON_THROW_ON_ERROR);

        return ModuleConfigDTO::fromArray([
            'name' => (string) ($payload['name'] ?? ''),
            'alias' => strtolower((string) ($payload['alias'] ?? '')),
            'enabled' => (bool) ($payload['enabled'] ?? true),
            'providers' => (array) ($payload['providers'] ?? []),
            'requires' => (array) ($payload['requires'] ?? []),
            'saas_only' => (bool) ($payload['saas_only'] ?? false),
        ]);
    }
}

