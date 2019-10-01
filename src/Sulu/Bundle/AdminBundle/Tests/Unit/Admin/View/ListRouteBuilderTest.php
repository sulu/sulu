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
use Sulu\Bundle\AdminBundle\Admin\View\ListRouteBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\TestBundle\Testing\ReadObjectAttributeTrait;

class ListRouteBuilderTest extends TestCase
{
    use ReadObjectAttributeTrait;

    public function testBuildListRouteWithClone()
    {
        $routeBuilder = (new ListRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['table']);

        $this->assertNotSame($routeBuilder->getRoute(), $routeBuilder->getRoute());
    }

    public function testBuildListRouteWithoutResourceKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('/"setResourceKey"/');

        $route = (new ListRouteBuilder('sulu_category.list', '/category'))
            ->getRoute();
    }

    public function testBuildListRouteWithoutListAdapters()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('/"addListAdapters"/');

        $route = (new ListRouteBuilder('sulu_category.list', '/category'))
            ->setResourceKey('categories')
            ->setListKey('roles')
            ->getRoute();
    }

    public function provideBuildListRoute()
    {
        return [
            [
                'sulu_category.list',
                '/categories',
                'categories',
                'categories',
                null,
                'Categories',
                ['table', 'column_list'],
                'sulu_category.add_form',
                'sulu_category.edit_form',
                'webspace',
            ],
            [
                'sulu_tag.list',
                '/tags',
                'tags',
                'other_tags',
                'contact_tags',
                'Tags',
                ['table'],
                'sulu_tag.add_form',
                'sulu_tag.edit_form',
                'locale',
            ],
        ];
    }

    /**
     * @dataProvider provideBuildListRoute
     */
    public function testBuildListRoute(
        string $name,
        string $path,
        string $resourceKey,
        string $listKey,
        ?string $userSettingsKey,
        string $title,
        array $listAdapters,
        string $addRoute,
        string $editRoute,
        string $rerenderAttribute
    ) {
        $routeBuilder = (new ListRouteBuilder($name, $path))
            ->setResourceKey($resourceKey)
            ->setListKey($listKey)
            ->setTitle($title)
            ->addListAdapters($listAdapters)
            ->setAddRoute($addRoute)
            ->setEditRoute($editRoute)
            ->addRerenderAttribute($rerenderAttribute);

        if ($userSettingsKey) {
            $routeBuilder->setUserSettingsKey($userSettingsKey);
        }

        $route = $routeBuilder->getRoute();

        $this->assertSame($name, $route->getName());
        $this->assertSame($path, $route->getPath());
        $this->assertSame([$rerenderAttribute], $this->readObjectAttribute($route, 'rerenderAttributes'));
        $this->assertSame($resourceKey, $route->getOption('resourceKey'));
        $this->assertSame($listKey, $route->getOption('listKey'));
        $this->assertSame($userSettingsKey, $route->getOption('userSettingsKey'));
        $this->assertSame($title, $route->getOption('title'));
        $this->assertSame($listAdapters, $route->getOption('adapters'));
        $this->assertSame($addRoute, $route->getOption('addRoute'));
        $this->assertSame($editRoute, $route->getOption('editRoute'));
        $this->assertSame('sulu_admin.list', $route->getView());
    }

    public function testBuildListRouteAddingAdaptersTwice()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['table', 'column_list'])
            ->addListAdapters(['tree'])
            ->getRoute();

        $this->assertSame(['table', 'column_list', 'tree'], $route->getOption('adapters'));
    }

    public function testBuildListWithLocales()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['table'])
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->setDefaultLocale('de')
            ->getRoute();

        $this->assertSame(['de', 'en', 'nl', 'fr'], $route->getOption('locales'));
        $this->assertSame('de', $route->getAttributeDefault('locale'));
    }

    public function testBuildListWithLocalesWithoutLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['table'])
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->setDefaultLocale('de')
            ->getRoute();
    }

    public function testBuildListWithoutLocalesWithLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $route = (new ListRouteBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['table'])
            ->getRoute();
    }

    public function testBuildListWithoutListKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('"listKey"');

        $route = (new ListRouteBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey('roles')
            ->addListAdapters(['table'])
            ->getRoute();
    }

    public function testBuildListRouteWithSearch()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->disableSearching()
            ->enableSearching()
            ->getRoute();

        $this->assertTrue($route->getOption('searchable'));
    }

    public function testBuildListRouteWithoutSearch()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->enableSearching()
            ->disableSearching()
            ->getRoute();

        $this->assertFalse($route->getOption('searchable'));
    }

    public function testBuildListWithRouterAttributesToListRequest()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addRouterAttributesToListRequest(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToListRequest(['locale'])
            ->getRoute();

        $this->assertSame(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('routerAttributesToListRequest')
        );
    }

    public function testBuildListWithRouterAttributesToListMetadata()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addRouterAttributesToListMetadata(['webspace' => 'webspaceId', 'parent' => 'parentId', 'id' => 1])
            ->addRouterAttributesToListMetadata(['locale'])
            ->getRoute();

        $this->assertSame(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'id' => 1, 'locale'],
            $route->getOption('routerAttributesToListMetadata')
        );
    }

    public function testBuildListWithResourceStorePropertiesToListRequest()
    {
        $route = (new ListRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addResourceStorePropertiesToListRequest(['id' => 'dimensionId', 'parent' => 'parentId'])
            ->addResourceStorePropertiesToListRequest(['locale'])
            ->getRoute();

        $this->assertSame(
            ['id' => 'dimensionId', 'parent' => 'parentId', 'locale'],
            $route->getOption('resourceStorePropertiesToListRequest')
        );
    }

    public function testBuildListSetParent()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setParent('sulu_role.parent_view')
            ->getRoute();

        $this->assertSame('sulu_role.parent_view', $route->getParent());
    }

    public function testBuildListSetOption()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setOption('resourceKey', 'test')
            ->getRoute();

        $this->assertSame('test', $route->getOption('resourceKey'));
    }

    public function testBuildListSetTabTitle()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabTitle('sulu_role.title')
            ->getRoute();

        $this->assertSame('sulu_role.title', $route->getOption('tabTitle'));
    }

    public function testBuildListSetTabOrder()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabOrder(5)
            ->getRoute();

        $this->assertSame(5, $route->getOption('tabOrder'));
    }

    public function testBuildListSetTabCondition()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabCondition('state == 1')
            ->getRoute();

        $this->assertSame('state == 1', $route->getOption('tabCondition'));
    }

    public function testBuildListSetBackRoute()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setBackRoute('sulu_category.edit_form')
            ->getRoute();

        $this->assertSame('sulu_category.edit_form', $route->getOption('backRoute'));
    }

    public function testBuildAddToolbarActions()
    {
        $saveToolbarAction = new ToolbarAction('sulu_admin.save');
        $typesToolbarAction = new ToolbarAction('sulu_admin.types');
        $deleteToolbarAction = new ToolbarAction('sulu_admin.delete');

        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addToolbarActions([$saveToolbarAction, $typesToolbarAction])
            ->addToolbarActions([$deleteToolbarAction])
            ->getRoute();

        $this->assertSame(
            [$saveToolbarAction, $typesToolbarAction, $deleteToolbarAction],
            $route->getOption('toolbarActions')
        );
    }
}
