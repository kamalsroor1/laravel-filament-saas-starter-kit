<?php

declare(strict_types=1);

namespace App\Core\Module;

use App\Core\Module\Contracts\ModuleInterface;
use App\Core\Module\DTOs\ModuleConfigDTO;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class ModuleManager implements ModuleInterface
{
    private const CACHE_KEY = 'modules:config';

    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private readonly ModuleLoader $moduleLoader,
        private readonly Application $app,
    ) {
    }

    public function isEnabled(string $alias): bool
    {
        return $this->getEnabled()
            ->contains(fn (ModuleConfigDTO $module): bool => $module->alias === strtolower($alias));
    }

    /**
     * @return Collection<int, ModuleConfigDTO>
     */
    public function getAll(): Collection
    {
        /** @var array<int, array<string, mixed>> $cached */
        $cached = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->moduleLoader
                ->loadAll()
                ->map(fn (ModuleConfigDTO $module): array => $module->toArray())
                ->all(),
        );

        return collect($cached)
            ->map(fn (array $module): ModuleConfigDTO => ModuleConfigDTO::fromArray($module));
    }

    /**
     * @return Collection<int, ModuleConfigDTO>
     */
    public function getEnabled(): Collection
    {
        $saasEnabled = (bool) config('modules.saas_enabled', true);

        return $this->getAll()->filter(function (ModuleConfigDTO $module) use ($saasEnabled): bool {
            if (! $module->enabled) {
                return false;
            }

            if (! (bool) config('modules.modules.'.$module->alias, true)) {
                return false;
            }

            if ($module->saas_only && ! $saasEnabled) {
                return false;
            }

            foreach ($module->requires as $requiredModuleAlias) {
                $requiredAlias = strtolower((string) $requiredModuleAlias);
                if (! (bool) config('modules.modules.'.$requiredAlias, true)) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    public function boot(): void
    {
        $this->getEnabled()->each(function (ModuleConfigDTO $module): void {
            foreach ($module->providers as $provider) {
                $this->app->register($provider);
            }
        });
    }
}

