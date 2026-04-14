<?php

declare(strict_types=1);

namespace Marwa\Framework\Navigation;

use Marwa\Framework\Exceptions\MenuConfigurationException;

final class MenuRegistry
{
    /**
     * @var array<string, array{
     *     name:string,
     *     label:string,
     *     url:string,
     *     parent:?string,
     *     order:int,
     *     icon:?string,
     *     visible:bool|callable
     * }>
     */
    private array $items = [];

    /**
     * @param array<string, mixed> $item
     */
    public function add(array $item): void
    {
        $normalized = $this->normalizeItem($item);

        if (isset($this->items[$normalized['name']])) {
            throw new MenuConfigurationException(sprintf(
                'Menu item [%s] is already registered.',
                $normalized['name']
            ));
        }

        $this->items[$normalized['name']] = $normalized;
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    public function addMany(array $items): void
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * @return list<array{name:string,label:string,url:string,parent:?string,order:int,icon:?string}>
     */
    public function all(): array
    {
        $items = [];
        $visibleNames = [];

        foreach ($this->items as $item) {
            if (!$this->isVisible($item)) {
                continue;
            }

            $publicItem = $this->toPublicItem($item);
            $items[] = $publicItem;
            $visibleNames[$publicItem['name']] = true;
        }

        $items = array_values(array_filter(
            $items,
            static fn (array $item): bool => $item['parent'] === null || isset($visibleNames[$item['parent']])
        ));

        if ($items === []) {
            return [];
        }

        usort($items, $this->sortPublicItems(...));

        return $items;
    }

    /**
     * @return list<array{name:string,label:string,url:string,parent:?string,order:int,icon:?string,children:list<array<string, mixed>>}>
     */
    public function tree(): array
    {
        $visibleItems = [];

        foreach ($this->items as $item) {
            if (!$this->isVisible($item)) {
                continue;
            }

            $publicItem = $this->toPublicItem($item);
            $publicItem['children'] = [];
            $visibleItems[$publicItem['name']] = $publicItem;
        }

        if ($visibleItems === []) {
            return [];
        }

        $tree = [];

        foreach ($visibleItems as $name => $item) {
            $parent = $item['parent'];

            if ($parent === null) {
                $tree[$name] = $item;
                continue;
            }

            if (!isset($visibleItems[$parent])) {
                continue;
            }

            $visibleItems[$parent]['children'][] = $item;
        }

        foreach ($visibleItems as $name => $item) {
            if (isset($tree[$name])) {
                $tree[$name] = $item;
            }
        }

        $tree = array_values($tree);
        $this->sortTree($tree);

        return $tree;
    }

    /**
     * @param array<string, mixed> $item
     * @return array{
     *     name:string,
     *     label:string,
     *     url:string,
     *     parent:?string,
     *     order:int,
     *     icon:?string,
     *     visible:bool|callable
     * }
     */
    private function normalizeItem(array $item): array
    {
        $name = is_string($item['name'] ?? null) ? trim($item['name']) : '';
        $label = is_string($item['label'] ?? null) ? trim($item['label']) : '';
        $url = is_string($item['url'] ?? null) ? trim($item['url']) : '';
        $parent = is_string($item['parent'] ?? null) ? trim($item['parent']) : null;
        $icon = is_string($item['icon'] ?? null) ? trim($item['icon']) : null;
        $order = $item['order'] ?? 0;
        $visible = $item['visible'] ?? true;

        if ($name === '') {
            throw new MenuConfigurationException('Menu item [name] is required.');
        }

        if ($label === '') {
            throw new MenuConfigurationException(sprintf(
                'Menu item [%s] must define a non-empty label.',
                $name
            ));
        }

        if ($url === '') {
            throw new MenuConfigurationException(sprintf(
                'Menu item [%s] must define a non-empty url.',
                $name
            ));
        }

        if (!is_int($order)) {
            throw new MenuConfigurationException(sprintf(
                'Menu item [%s] must use an integer order value.',
                $name
            ));
        }

        if (!is_bool($visible) && !is_callable($visible)) {
            throw new MenuConfigurationException(sprintf(
                'Menu item [%s] visible must be a boolean or callable.',
                $name
            ));
        }

        return [
            'name' => $name,
            'label' => $label,
            'url' => $url,
            'parent' => $parent !== '' ? $parent : null,
            'order' => $order,
            'icon' => $icon !== '' ? $icon : null,
            'visible' => $visible,
        ];
    }

    /**
     * @param array{name:string,label:string,url:string,parent:?string,order:int,icon:?string,visible:bool|callable} $item
     * @return array{name:string,label:string,url:string,parent:?string,order:int,icon:?string}
     */
    private function toPublicItem(array $item): array
    {
        unset($item['visible']);

        return $item;
    }

    /**
     * @param array{name:string,label:string,url:string,parent:?string,order:int,icon:?string,visible:bool|callable} $item
     */
    private function isVisible(array $item): bool
    {
        if (is_bool($item['visible'])) {
            return $item['visible'];
        }

        try {
            return (bool) ($item['visible'])($this->toPublicItem($item));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param array{name:string,label:string,url:string,parent:?string,order:int,icon:?string} $left
     * @param array{name:string,label:string,url:string,parent:?string,order:int,icon:?string} $right
     */
    private function sortPublicItems(array $left, array $right): int
    {
        $orderComparison = $left['order'] <=> $right['order'];

        if ($orderComparison !== 0) {
            return $orderComparison;
        }

        $labelComparison = strcmp($left['label'], $right['label']);

        if ($labelComparison !== 0) {
            return $labelComparison;
        }

        return strcmp($left['name'], $right['name']);
    }

    /**
     * @param list<array{name:string,label:string,url:string,parent:?string,order:int,icon:?string,children:list<array<string, mixed>>}> $items
     */
    private function sortTree(array &$items): void
    {
        usort($items, $this->sortTreeItems(...));

        foreach ($items as &$item) {
            if ($item['children'] !== []) {
                $this->sortTree($item['children']);
            }
        }
    }

    /**
     * @param array{name:string,label:string,url:string,parent:?string,order:int,icon:?string,children:list<array<string, mixed>>} $left
     * @param array{name:string,label:string,url:string,parent:?string,order:int,icon:?string,children:list<array<string, mixed>>} $right
     */
    private function sortTreeItems(array $left, array $right): int
    {
        return $this->sortPublicItems($left, $right);
    }
}
