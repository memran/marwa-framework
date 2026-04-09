<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

use Twig\Extension\AbstractExtension;

interface ViewExtensionInterface
{
    public function register(): void;
}
