<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Exceptions\MenuConfigurationException;
use Marwa\Framework\Navigation\MenuRegistry;
use PHPUnit\Framework\TestCase;

final class MenuRegistryTest extends TestCase
{
    public function testItBuildsASortedNestedTree(): void
    {
        $menu = new MenuRegistry();

        $menu->add([
            'name' => 'settings',
            'label' => 'Settings',
            'url' => '/settings',
            'order' => 30,
        ]);
        $menu->add([
            'name' => 'blog',
            'label' => 'Blog',
            'url' => '/blog',
            'order' => 20,
        ]);
        $menu->add([
            'name' => 'dashboard',
            'label' => 'Dashboard',
            'url' => '/dashboard',
            'order' => 10,
        ]);
        $menu->add([
            'name' => 'blog.posts',
            'label' => 'Posts',
            'url' => '/blog/posts',
            'parent' => 'blog',
            'order' => 20,
        ]);
        $menu->add([
            'name' => 'blog.categories',
            'label' => 'Categories',
            'url' => '/blog/categories',
            'parent' => 'blog',
            'order' => 10,
        ]);

        self::assertSame(['dashboard', 'blog', 'settings'], array_column($menu->tree(), 'name'));
        self::assertSame(
            ['blog.categories', 'blog.posts'],
            array_column($menu->tree()[1]['children'], 'name')
        );
    }

    public function testItRejectsDuplicateNames(): void
    {
        $menu = new MenuRegistry();
        $menu->add([
            'name' => 'blog',
            'label' => 'Blog',
            'url' => '/blog',
        ]);

        $this->expectException(MenuConfigurationException::class);
        $this->expectExceptionMessage('Menu item [blog] is already registered.');

        $menu->add([
            'name' => 'blog',
            'label' => 'Blog 2',
            'url' => '/blog-2',
        ]);
    }

    public function testItFiltersInvisibleItemsAndSkipsOrphans(): void
    {
        $menu = new MenuRegistry();

        $menu->add([
            'name' => 'dashboard',
            'label' => 'Dashboard',
            'url' => '/dashboard',
        ]);
        $menu->add([
            'name' => 'hidden',
            'label' => 'Hidden',
            'url' => '/hidden',
            'visible' => false,
        ]);
        $menu->add([
            'name' => 'conditional',
            'label' => 'Conditional',
            'url' => '/conditional',
            'visible' => static fn (): bool => true,
        ]);
        $menu->add([
            'name' => 'orphan',
            'label' => 'Orphan',
            'url' => '/orphan',
            'parent' => 'missing',
        ]);

        self::assertSame(['conditional', 'dashboard'], array_column($menu->all(), 'name'));
        self::assertSame(['conditional', 'dashboard'], array_column($menu->tree(), 'name'));
    }
}
