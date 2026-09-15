<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use Marwa\Module\Module;
use Marwa\Support\File;
use Marwa\Support\Str;

final class ModuleManifestReader
{
    /**
     * @return array<string, mixed>
     */
    public function read(Module $module): array
    {
        $phpManifest = $module->basePath() . DIRECTORY_SEPARATOR . 'manifest.php';

        if (is_file($phpManifest)) {
            $manifest = require $phpManifest;

            return is_array($manifest) ? $manifest : [];
        }

        $jsonManifest = $module->basePath() . DIRECTORY_SEPARATOR . 'manifest.json';

        if (!File::exists($jsonManifest)) {
            return [];
        }

        try {
            $manifest = json_decode(File::get($jsonManifest), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        return is_array($manifest) ? $manifest : [];
    }

    /**
     * Required module slugs declared in the hydrated manifest.
     *
     * Uses the manifest already loaded into the module object so bootstrap
     * does not re-require every manifest.php on the compiled-cache path.
     *
     * @return list<string>
     */
    public function getRequiredModules(Module $module): array
    {
        $requiredModules = [];

        foreach (['requires', 'dependencies'] as $key) {
            $values = $module->manifest()[$key] ?? [];

            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $value) {
                if (!is_string($value)) {
                    continue;
                }

                $slug = trim($value);

                if ($slug === '') {
                    continue;
                }

                $requiredModules[] = Str::lower($slug);
            }
        }

        return array_values(array_unique($requiredModules));
    }
}
