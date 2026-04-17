<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use Marwa\Support\File;
use Marwa\Support\Str;
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

                $requiredModules[] = Str::lower($slug);
            }
        }

        return array_values(array_unique($requiredModules));
    }
}
