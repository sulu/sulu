<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\View;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\View\Badge;
use Sulu\Bundle\AdminBundle\Admin\View\ListItemAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewOverlayListViewBuilder;
use Sulu\Component\Security\Authentication\RoleInterface;

class ViewOverlayListViewBuilderTest extends TestCase
{
    public function testBuildViewOverlayListViewWithClone(): void
    {
        $routeBuilder = (new ViewOverlayListViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['table']);

        $this->assertNotSame($routeBuilder->getView(), $routeBuilder->getView());
    }

    public function testBuildViewOverlayListViewWithoutResourceKey(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/"setResourceKey"/');

        $route = (new ViewOverlayListViewBuilder('sulu_category.list', '/category'))
            ->getView();
    }

    public function testBuildViewOverlayListViewWithoutListAdapters(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/"addListAdapters"/');

        $route = (new ViewOverlayListViewBuilder('sulu_category.list', '/category'))
            ->setResourceKey('categories')
            ->setListKey('roles')
            ->getView();
    }

    /**
     * @return array<array{string, string, string, string, string, string, string, string[], string, string, string, array<string, mixed>}>
     */
    public static function provideBuildViewOverlayListView(): array
    {
        return [
            [
                'sulu_category.list',
                '/categories',
                'categories',
                'categories',
                'sulu_category.add_overlay',
                'sulu_category.edit_overlay',
                'Categories',
                ['table', 'column_list'],
                'sulu_category.add_category',
                'sulu_category.edit_category',
                'small',
                ['test1' => 'value1'],
            ],
            [
                'sulu_tag.list',
                '/tags',
                'tags',
                'other_tags',
                'sulu_tag.add_overlay',
                'sulu_tag.edit_overlay',
                'Tags',
                ['table'],
                'sulu_tag.add_tag',
                'sulu_tag.edit_tag',
                'large',
                ['test2' => 'value2'],
            ],
        ];
    }

    /**
     * @param string[] $listAdapters
     * @param array<string, mixed> $requestParameters
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideBuildViewOverlayListView')]
    public function testBuildViewOverlayListView(
        string $name,
        string $path,
        string $resourceKey,
        string $listKey,
        string $addOverlayView,
        string $editOverlayView,
        string $title,
        array $listAdapters,
        string $addOverlayTitle,
        string $editOverlayTitle,
        string $overlaySize,
        array $requestParameters
    ): void {
        $route = (new ViewOverlayListViewBuilder($name, $path))
            ->setResourceKey($resourceKey)
            ->setListKey($listKey)
            ->setAddOverlayView($addOverlayView)
            ->setEditOverlayView($editOverlayView)
            ->setTitle($title)
            ->addListAdapters($listAdapters)
            ->setAddOverlayTitle($addOverlayTitle)
            ->setEditOverlayTitle($editOverlayTitle)
            ->setOverlaySize($overlaySize)
            ->addRequestParameters($requestParameters)
            ->getView();

        $this->assertEquals($name, $route->getName());
        $this->assertEquals($path, $route->getPath());
        $this->assertEquals($resourceKey, $route->getOption('resourceKey'));
        $this->assertEquals($listKey, $route->getOption('listKey'));
        $this->assertEquals($addOverlayView, $route->getOption('addOverlayView'));
        $this->assertEquals($editOverlayView, $route->getOption('editOverlayView'));
        $this->assertEquals($title, $route->getOption('title'));
        $this->assertEquals($listAdapters, $route->getOption('adapters'));
        $this->assertEquals($addOverlayTitle, $route->getOption('addOverlayTitle'));
        $this->assertEquals($editOverlayTitle, $route->getOption('editOverlayTitle'));
        $this->assertEquals($overlaySize, $route->getOption('overlaySize'));
        $this->assertEquals($requestParameters, $route->getOption('requestParameters'));
        $this->assertEquals('sulu_admin.view_overlay_list', $route->getType());
    }

    public function testBuildViewOverlayListViewAddingAdaptersTwice(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['table', 'column_list'])
            ->addListAdapters(['tree'])
            ->getView();

        $this->assertEquals(['table', 'column_list', 'tree'], $route->getOption('adapters'));
    }

    public function testBuildListWithLocales(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['table'])
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->setDefaultLocale('de')
            ->getView();

        $this->assertEquals(['de', 'en', 'nl', 'fr'], $route->getOption('locales'));
        $this->assertEquals('de', $route->getAttributeDefault('locale'));
    }

    public function testBuildListWithLocalesWithoutLocalePlaceholder(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('":locale"');

        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['table'])
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->setDefaultLocale('de')
            ->getView();
    }

    public function testBuildListWithoutLocalesWithLocalePlaceholder(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('":locale"');

        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['table'])
            ->getView();
    }

    public function testBuildListWithoutListKey(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('"listKey"');

        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->addListAdapters(['table'])
            ->getView();
    }

    public function testBuildViewOverlayListViewWithSearch(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->disableSearching()
            ->enableSearching()
            ->getView();

        $this->assertTrue($route->getOption('searchable'));
    }

    public function testBuildViewOverlayListViewWithoutSearch(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->enableSearching()
            ->disableSearching()
            ->getView();

        $this->assertFalse($route->getOption('searchable'));
    }

    public function testBuildViewOverlayListViewWithSelection(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->disableSelection()
            ->enableSelection()
            ->getView();

        $this->assertTrue($route->getOption('selectable'));
    }

    public function testBuildViewOverlayListViewWithoutSelection(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->enableSelection()
            ->disableSelection()
            ->getView();

        $this->assertFalse($route->getOption('selectable'));
    }

    public function testBuildListWithRouterAttributesToListRequest(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addRouterAttributesToListRequest(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToListRequest(['locale'])
            ->getView();

        $this->assertEquals(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('routerAttributesToListRequest')
        );
    }

    public function testBuildWithResourceStorePropertiesToListRequest(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addResourceStorePropertiesToListRequest(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addResourceStorePropertiesToListRequest(['locale'])
            ->getView();

        $this->assertEquals(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('resourceStorePropertiesToListRequest')
        );
    }

    public function testBuildListSetParent(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setParent('sulu_role.parent_view')
            ->getView();

        $this->assertEquals('sulu_role.parent_view', $route->getParent());
    }

    public function testBuildListSetOption(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setParent('sulu_role.parent_view')
            ->setOption('listKey', 'overridden_roles')
            ->getView();

        $this->assertEquals('overridden_roles', $route->getOption('listKey'));
    }

    public function testBuildListSetTabTitle(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabTitle('sulu_role.title')
            ->getView();

        $this->assertEquals('sulu_role.title', $route->getOption('tabTitle'));
    }

    public function testBuildListSetTabOrder(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabOrder(5)
            ->getView();

        $this->assertEquals(5, $route->getOption('tabOrder'));
    }

    public function testBuildListSetTabPriority(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabPriority(5)
            ->getView();

        $this->assertEquals(5, $route->getOption('tabPriority'));
    }

    public function testBuildListSetTabCondition(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabCondition('state == 1')
            ->getView();

        $this->assertEquals('state == 1', $route->getOption('tabCondition'));
    }

    public function testBuildListSetBackView(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setBackView('sulu_category.edit_form')
            ->getView();

        $this->assertEquals('sulu_category.edit_form', $route->getOption('backView'));
    }

    public function testBuildListSetItemDisabledCondition(): void
    {
        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setItemDisabledCondition('(_permissions && !_permissions.delete)')
            ->getView();

        $this->assertSame('(_permissions && !_permissions.delete)', $route->getOption('itemDisabledCondition'));
    }

    public function testBuildAddToolbarActions(): void
    {
        $saveToolbarAction = new ToolbarAction('sulu_admin.save');
        $typesToolbarAction = new ToolbarAction('sulu_admin.types');
        $deleteToolbarAction = new ToolbarAction('sulu_admin.delete');

        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addToolbarActions([$saveToolbarAction, $typesToolbarAction])
            ->addToolbarActions([$deleteToolbarAction])
            ->getView();

        $this->assertEquals(
            [$saveToolbarAction, $typesToolbarAction, $deleteToolbarAction],
            $route->getOption('toolbarActions')
        );
    }

    public function testBuildAddItemActions(): void
    {
        $linkItemAction = new ListItemAction('sulu_admin.link');
        $exportItemAction = new ListItemAction('sulu_admin.export');
        $downloadItemAction = new ListItemAction('sulu_admin.download');

        $route = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addItemActions([$linkItemAction, $exportItemAction])
            ->addItemActions([$downloadItemAction])
            ->getView();

        $this->assertEquals(
            [$linkItemAction, $exportItemAction, $downloadItemAction],
            $route->getOption('itemActions')
        );
    }

    public function testBuildAddTabBadge(): void
    {
        $fooBadge = new Badge('sulu_foo.get_foo_badge');
        $barBadge = new Badge('sulu_bar.get_bar_badge');
        $bazBadge = (new Badge('sulu_baz.get_baz_badge', '/total', 'value != 0'))
            ->addRequestParameters([
                'limit' => 0,
                'entityClass' => 'Sulu\Bundle\BazBundle\Entity\Baz',
            ])
            ->addRouterAttributesToRequest([
                'locale',
                'id' => 'entityId',
            ]);

        $view = (new ViewOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addTabBadges([$fooBadge, 'abc' => $barBadge])
            ->addTabBadges(['abc' => $bazBadge])
            ->getView();

        $this->assertEquals(
            [
                $fooBadge,
                'abc' => $bazBadge,
            ],
            $view->getOption('tabBadges')
        );
    }
}
