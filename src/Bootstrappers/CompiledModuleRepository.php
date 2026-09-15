<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use Marwa\Module\Contracts\ModuleRepositoryInterface;
use Marwa\Module\Module;

/**
 * Repository backed by already-hydrated modules from the compiled cache.
 *
 * It lets the module registry boot without touching the filesystem or the
 * marwa-module manifest cache. `$forceRefresh` is intentionally ignored:
 * invalidation is decided by ModuleBootstrapCache before this repository
 * is used, and a refresh falls back to the regular discovery path.
 */
final class CompiledModuleRepository implements ModuleRepositoryInterface
{
    /**
     * @param array<string, Module> $modules
     */
    public function __construct(private array $modules) {}

    /**
     * @return array<string, Module>
     */
    public function all(bool $forceRefresh = false): array
    {
        return $this->modules;
    }
}
