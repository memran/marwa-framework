<?php

declare(strict_types=1);

namespace Marwa\Framework\Navigation;

use Marwa\Support\Str;

final class NavigationRenderer
{
    private string $currentUrl = '';

    public function __construct(
        private ?MenuRegistry $registry = null
    ) {}

    public function setCurrentUrl(string $url): self
    {
        $this->currentUrl = $url;

        return $this;
    }

    public function currentUrl(): string
    {
        return $this->currentUrl;
    }

    public function isActive(string $url): bool
    {
        if ($this->currentUrl === '' || $url === '') {
            return false;
        }

        return $this->currentUrl === $url || Str::startsWith($this->currentUrl, rtrim($url, '/') . '/');
    }

    /**
     * @return list<array{name:string,label:string,url:string,icon:?string,isActive:bool,children:list<array{name:string,label:string,url:string,icon:?string,isActive:bool}>}>
     */
    public function tree(): array
    {
        if ($this->registry === null) {
            return [];
        }

        $items = $this->registry->tree();

        return array_map(fn (array $item): array => $this->mapTreeItem($item), $items);
    }

    /**
     * @return list<array{name:string,label:string,url:string,icon:?string,isActive:bool,children:list<array{name:string,label:string,url:string,icon:?string,isActive:bool}>}>
     */
    public function mainMenu(): array
    {
        return $this->tree();
    }

    public function renderMainMenu(): string
    {
        $items = $this->mainMenu();

        return $this->renderMenu($items);
    }

    /**
     * @param list<array{name:string,label:string,url:string,icon:?string,isActive:bool,children:list<array<string, mixed>>}> $items
     */
    public function renderMenu(array $items): string
    {
        $html = '';

        foreach ($items as $item) {
            $html .= $this->renderMenuItem($item);
        }

        return $html;
    }

    /**
     * @param list<array{name:string,label:string,url:string,icon:?string,isActive:bool,children:list<array<string, mixed>>}> $sections
     */
    public function renderSections(array $sections): string
    {
        $html = '';

        foreach ($sections as $section) {
            $html .= $this->renderSection($section);
        }

        return $html;
    }

    /**
     * @param array{name:string,label:string,url:string,icon:?string,isActive:bool,children:list<array{name:string,label:string,url:string,icon:?string,isActive:bool}>} $item
     */
    public function renderDropdown(array $item): string
    {
        $isActive = $item['isActive'];
        $label = $item['label'];
        $url = $item['url'];
        $icon = $item['icon'] ?? '';
        /** @var list<array{name:string,label:string,url:string,icon:?string,isActive:bool}> $children */
        $children = $item['children'];

        $activeClass = $isActive ? ' active' : '';

        $html = '<div class="nav-itemdropdown">';
        $html .= '<a href="' . htmlspecialchars($url) . '" class="nav-link dropdown-toggle' . $activeClass . '" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">';

        if ($icon !== '') {
            $html .= '<i class="' . htmlspecialchars($icon) . '"></i> ';
        }

        $html .= htmlspecialchars($label) . '</a>';
        $html .= '<ul class="dropdown-menu">';

        foreach ($children as $child) {
            $childActive = $child['isActive'] ? ' active' : '';
            $childUrl = $child['url'];
            $childIcon = $child['icon'] ?? '';
            $childLabel = $child['label'];

            $html .= '<li><a href="' . htmlspecialchars($childUrl) . '" class="dropdown-item' . $childActive . '">';

            if ($childIcon !== '') {
                $html .= '<i class="' . htmlspecialchars($childIcon) . '"></i> ';
            }

            $html .= htmlspecialchars($childLabel) . '</a></li>';
        }

        $html .= '</ul></div>';

        return $html;
    }

    /**
     * @param array{name:string,label:string,url:string,icon:?string,isActive:bool,children:list<array{name:string,label:string,url:string,icon:?string,isActive:bool}>} $item
     */
    public function renderMenuItem(array $item): string
    {
        $isActive = $item['isActive'];
        $label = $item['label'];
        $url = $item['url'];
        $icon = $item['icon'] ?? '';
        /** @var list<array{name:string,label:string,url:string,icon:?string,isActive:bool}> $children */
        $children = $item['children'];

        if (!empty($children)) {
            return $this->renderDropdown($item);
        }

        $activeClass = $isActive ? ' active' : '';

        $html = '<a href="' . htmlspecialchars($url) . '" class="nav-link' . $activeClass . '">';

        if ($icon !== '') {
            $html .= '<i class="' . htmlspecialchars($icon) . '"></i> ';
        }

        $html .= htmlspecialchars($label) . '</a>';

        return $html;
    }

    /**
     * @param array{name:string,label:string,url:string,icon:?string,isActive:bool,children:list<array{name:string,label:string,url:string,icon:?string,isActive:bool}>} $section
     */
    public function renderSection(array $section): string
    {
        $label = $section['label'];
        $icon = $section['icon'] ?? '';
        /** @var list<array{name:string,label:string,url:string,icon:?string,isActive:bool}> $children */
        $children = $section['children'];

        $html = '<div class="mb-3">';

        if ($label !== '') {
            $html .= '<h6 class="sidebar-heading px-3 text-uppercase" style="font-size:0.75rem;font-weight:600;letter-spacing:0.05em;">';

            if ($icon !== '') {
                $html .= '<i class="' . htmlspecialchars($icon) . ' me-1"></i> ';
            }

            $html .= htmlspecialchars($label) . '</h6>';
        }

        $html .= '<ul class="nav flex-column">';

        foreach ($children as $child) {
            $childActive = $child['isActive'] ? ' active' : '';
            $childUrl = $child['url'];
            $childIcon = $child['icon'] ?? '';
            $childLabel = $child['label'];

            $html .= '<li class="nav-item">';
            $html .= '<a href="' . htmlspecialchars($childUrl) . '" class="nav-link' . $childActive . '">';

            if ($childIcon !== '') {
                $html .= '<i class="' . htmlspecialchars($childIcon) . '"></i> ';
            }

            $html .= htmlspecialchars($childLabel) . '</a>';
            $html .= '</li>';
        }

        $html .= '</ul></div>';

        return $html;
    }

    /**
     * @param array{name:string,label:string,url:string,parent:?string,order:int,icon:?string,children:list<array{name:string,label:string,url:string,icon:?string}>} $item
     * @return array{name:string,label:string,url:string,icon:?string,isActive:bool,children:list<array{name:string,label:string,url:string,icon:?string,isActive:bool}>}
     */
    private function mapTreeItem(array $item): array
    {
        /** @var list<array{name:string,label:string,url:string,icon:?string}> $children */
        $children = $item['children'];

        return [
            'name' => $item['name'],
            'label' => $item['label'],
            'url' => $item['url'],
            'icon' => $item['icon'],
            'isActive' => $this->isActive($item['url']),
            'children' => array_map(fn (array $child): array => $this->mapTreeChild($child), $children),
        ];
    }

    /**
     * @param array{name:string,label:string,url:string,icon:?string} $child
     * @return array{name:string,label:string,url:string,icon:?string,isActive:bool}
     */
    private function mapTreeChild(array $child): array
    {
        return [
            'name' => $child['name'],
            'label' => $child['label'],
            'url' => $child['url'],
            'icon' => $child['icon'],
            'isActive' => $this->isActive($child['url']),
        ];
    }
}

