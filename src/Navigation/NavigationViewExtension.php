<?php

declare(strict_types=1);

namespace Marwa\Framework\Navigation;

use Marwa\Framework\Views\Extension\AbstractViewExtension;

final class NavigationViewExtension extends AbstractViewExtension
{
    private ?NavigationRenderer $renderer = null;

    public function __construct(
        private ?MenuRegistry $menuRegistry = null
    ) {
    }

    public function register(): void
    {
        $this->renderer = new NavigationRenderer($this->menuRegistry);

        $this->addFunction('main_menu', fn (): string => $this->renderer()->renderMainMenu());

        $this->addFunction('menu_tree', fn (): array => $this->renderer()->tree());

        $this->addFunction('menu_item', fn (array $item): string => $this->renderer()->renderMenuItem($item));

        $this->addFunction('menu_section', fn (array $section): string => $this->renderer()->renderSection($section));

        $this->addFunction('menu_sections', fn (array $sections): string => $this->renderer()->renderSections($sections));

        $this->addFunction('is_menu_active', fn (string $url): bool => $this->renderer()->isActive($url));
    }

    public function renderer(): NavigationRenderer
    {
        return $this->renderer ?? new NavigationRenderer();
    }

    public function setCurrentUrl(string $url): self
    {
        if ($this->renderer !== null) {
            $this->renderer->setCurrentUrl($url);
        }

        return $this;
    }

    public static function createWithGlobals(): self
    {
        global $mainMenu;

        return new self($mainMenu ?? null);
    }
}