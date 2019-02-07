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
use Sulu\Bundle\AdminBundle\Admin\Routing\DatagridRouteBuilder;

class DatagridRouteBuilderTest extends TestCase
{
    public function testBuildDatagridRouteWithClone()
    {
        $routeBuilder = (new DatagridRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['table']);

        $this->assertNotSame($routeBuilder->getRoute(), $routeBuilder->getRoute());
    }

    public function testBuildDatagridRouteWithoutResourceKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('/"setResourceKey"/');

        $route = (new DatagridRouteBuilder('sulu_category.datagrid', '/category'))
            ->getRoute();
    }

    public function testBuildDatagridRouteWithoutDatagridAdapters()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('/"addDatagridAdapters"/');

        $route = (new DatagridRouteBuilder('sulu_category.datagrid', '/category'))
            ->setResourceKey('categories')
            ->setDatagridKey('roles')
            ->getRoute();
    }

    public function provideBuildDatagridRoute()
    {
        return [
            [
                'sulu_category.datagrid',
                '/categories',
                'categories',
                'categories',
                'Categories',
                ['table', 'column_list'],
                'sulu_category.add_form',
                'sulu_category.edit_form',
            ],
            [
                'sulu_tag.datagrid',
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
     * @dataProvider provideBuildDatagridRoute
     */
    public function testBuildDatagridRoute(
        string $name,
        string $path,
        string $resourceKey,
        string $datagridKey,
        string $title,
        array $datagridAdapters,
        string $addRoute,
        string $editRoute
    ) {
        $route = (new DatagridRouteBuilder($name, $path))
            ->setResourceKey($resourceKey)
            ->setDatagridKey($datagridKey)
            ->setTitle($title)
            ->addDatagridAdapters($datagridAdapters)
            ->setAddRoute($addRoute)
            ->setEditRoute($editRoute)
            ->getRoute();

        $this->assertEquals($name, $route->getName());
        $this->assertEquals($path, $route->getPath());
        $this->assertEquals($resourceKey, $route->getOption('resourceKey'));
        $this->assertEquals($datagridKey, $route->getOption('datagridKey'));
        $this->assertEquals($title, $route->getOption('title'));
        $this->assertEquals($datagridAdapters, $route->getOption('adapters'));
        $this->assertEquals($addRoute, $route->getOption('addRoute'));
        $this->assertEquals($editRoute, $route->getOption('editRoute'));
        $this->assertEquals('sulu_admin.datagrid', $route->getView());
    }

    public function testBuildDatagridRouteAddingAdaptersTwice()
    {
        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['table', 'column_list'])
            ->addDatagridAdapters(['tree'])
            ->getRoute();

        $this->assertEquals(['table', 'column_list', 'tree'], $route->getOption('adapters'));
    }

    public function testBuildDatagridWithLocales()
    {
        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['table'])
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->setDefaultLocale('de')
            ->getRoute();

        $this->assertEquals(['de', 'en', 'nl', 'fr'], $route->getOption('locales'));
        $this->assertEquals('de', $route->getAttributeDefault('locale'));
    }

    public function testBuildDatagridWithLocalesWithoutLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['table'])
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->setDefaultLocale('de')
            ->getRoute();
    }

    public function testBuildDatagridWithoutLocalesWithLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['table'])
            ->getRoute();
    }

    public function testBuildDatagridWithoutDatagridKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('"datagridKey"');

        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles/:locale'))
            ->setResourceKey('roles')
            ->addDatagridAdapters(['table'])
            ->getRoute();
    }

    public function testBuildDatagridRouteWithSearch()
    {
        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['tree'])
            ->disableSearching()
            ->enableSearching()
            ->getRoute();

        $this->assertTrue($route->getOption('searchable'));
    }

    public function testBuildDatagridRouteWithoutSearch()
    {
        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['tree'])
            ->enableSearching()
            ->disableSearching()
            ->getRoute();

        $this->assertFalse($route->getOption('searchable'));
    }

    public function testBuildDatagridRouteWithMoving()
    {
        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['tree'])
            ->disableMoving()
            ->enableMoving()
            ->getRoute();

        $this->assertTrue($route->getOption('movable'));
    }

    public function testBuildDatagridRouteWithoutMoving()
    {
        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['tree'])
            ->enableMoving()
            ->disableMoving()
            ->getRoute();

        $this->assertFalse($route->getOption('movable'));
    }

    public function testBuildDatagridWithRouterAttributesToFormStore()
    {
        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['tree'])
            ->addRouterAttributesToDatagridStore(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToDatagridStore(['locale'])
            ->getRoute();

        $this->assertEquals(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('routerAttributesToDatagridStore')
        );
    }

    public function testBuildDatagridSetParent()
    {
        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['tree'])
            ->setParent('sulu_role.parent_view')
            ->getRoute();

        $this->assertEquals('sulu_role.parent_view', $route->getParent());
    }

    public function testBuildDatagridSetTabTitle()
    {
        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['tree'])
            ->setTabTitle('sulu_role.title')
            ->getRoute();

        $this->assertEquals('sulu_role.title', $route->getOption('tabTitle'));
    }

    public function testBuildDatagridSetTabOrder()
    {
        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['tree'])
            ->setTabOrder(5)
            ->getRoute();

        $this->assertEquals(5, $route->getOption('tabOrder'));
    }

    public function testBuildDatagridSetTabCondition()
    {
        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->setDatagridKey('roles')
            ->addDatagridAdapters(['tree'])
            ->setTabCondition('state == 1')
            ->getRoute();

        $this->assertEquals('state == 1', $route->getOption('tabCondition'));
    }
}
