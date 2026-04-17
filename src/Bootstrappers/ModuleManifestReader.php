<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use Marwa\Module\Module;

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

        if (!is_file($jsonManifest)) {
            return [];
        }

        $contents = file_get_contents($jsonManifest);

        if ($contents === false) {
            return [];
        }

        $manifest = json_decode($contents, true);

        return is_array($manifest) ? $manifest : [];
    }

    /**
     * @return list<string>
     */
    public function getRequiredModules(Module $module): array
    {
        $manifest = $this->read($module);
        $requiredModules = [];

        foreach (['requires', 'dependencies'] as $key) {
            $values = $manifest[$key] ?? [];

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

                $requiredModules[] = strtolower($slug);
            }
        }

        return array_values(array_unique($requiredModules));
    }
}
