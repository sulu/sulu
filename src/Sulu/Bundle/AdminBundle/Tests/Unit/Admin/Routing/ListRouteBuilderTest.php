<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\Routing;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\Routing\ListRouteBuilder;

class ListRouteBuilderTest extends TestCase
{
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
                'Categories',
                ['table', 'column_list'],
                'sulu_category.add_form',
                'sulu_category.edit_form',
            ],
            [
                'sulu_tag.list',
                '/tags',
                'tags',
                'other_tags',
                'Tags',
                ['table'],
                'sulu_tag.add_form',
                'sulu_tag.edit_form',
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
        string $title,
        array $listAdapters,
        string $addRoute,
        string $editRoute
    ) {
        $route = (new ListRouteBuilder($name, $path))
            ->setResourceKey($resourceKey)
            ->setListKey($listKey)
            ->setTitle($title)
            ->addListAdapters($listAdapters)
            ->setAddRoute($addRoute)
            ->setEditRoute($editRoute)
            ->getRoute();

        $this->assertEquals($name, $route->getName());
        $this->assertEquals($path, $route->getPath());
        $this->assertEquals($resourceKey, $route->getOption('resourceKey'));
        $this->assertEquals($listKey, $route->getOption('listKey'));
        $this->assertEquals($title, $route->getOption('title'));
        $this->assertEquals($listAdapters, $route->getOption('adapters'));
        $this->assertEquals($addRoute, $route->getOption('addRoute'));
        $this->assertEquals($editRoute, $route->getOption('editRoute'));
        $this->assertEquals('sulu_admin.list', $route->getView());
    }

    public function testBuildListRouteAddingAdaptersTwice()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['table', 'column_list'])
            ->addListAdapters(['tree'])
            ->getRoute();

        $this->assertEquals(['table', 'column_list', 'tree'], $route->getOption('adapters'));
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

        $this->assertEquals(['de', 'en', 'nl', 'fr'], $route->getOption('locales'));
        $this->assertEquals('de', $route->getAttributeDefault('locale'));
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

    public function testBuildListWithRouterAttributesToFormStore()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addRouterAttributesToListStore(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToListStore(['locale'])
            ->getRoute();

        $this->assertEquals(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('routerAttributesToListStore')
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

        $this->assertEquals('sulu_role.parent_view', $route->getParent());
    }

    public function testBuildListSetTabTitle()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabTitle('sulu_role.title')
            ->getRoute();

        $this->assertEquals('sulu_role.title', $route->getOption('tabTitle'));
    }

    public function testBuildListSetTabOrder()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabOrder(5)
            ->getRoute();

        $this->assertEquals(5, $route->getOption('tabOrder'));
    }

    public function testBuildListSetTabCondition()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabCondition('state == 1')
            ->getRoute();

        $this->assertEquals('state == 1', $route->getOption('tabCondition'));
    }

    public function testBuildListSetBackRoute()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setBackRoute('sulu_category.edit_form')
            ->getRoute();

        $this->assertEquals('sulu_category.edit_form', $route->getOption('backRoute'));
    }

    public function testBuildAddToolbarActions()
    {
        $route = (new ListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addToolbarActions(['sulu_admin.add', 'sulu_admin.move'])
            ->addToolbarActions(['sulu_admin.delete'])
            ->getRoute();

        $this->assertEquals(['sulu_admin.add', 'sulu_admin.move', 'sulu_admin.delete'], $route->getOption('toolbarActions'));
    }
}
