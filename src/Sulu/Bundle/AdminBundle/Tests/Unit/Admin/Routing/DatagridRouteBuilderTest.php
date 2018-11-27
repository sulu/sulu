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
            ->getRoute();
    }

    public function provideBuildDatagridRoute()
    {
        return [
            [
                'sulu_category.datagrid',
                '/categories',
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
        string $title,
        array $datagridAdapters,
        string $addRoute,
        string $editRoute
    ) {
        $route = (new DatagridRouteBuilder($name, $path))
            ->setResourceKey($resourceKey)
            ->setTitle($title)
            ->addDatagridAdapters($datagridAdapters)
            ->setAddRoute($addRoute)
            ->setEditRoute($editRoute)
            ->getRoute();

        $this->assertEquals($name, $route->getName());
        $this->assertEquals($path, $route->getPath());
        $this->assertEquals($resourceKey, $route->getOption('resourceKey'));
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
            ->addDatagridAdapters(['table', 'column_list'])
            ->addDatagridAdapters(['tree'])
            ->getRoute();

        $this->assertEquals(['table', 'column_list', 'tree'], $route->getOption('adapters'));
    }

    public function testBuildDatagridWithLocales()
    {
        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles/:locale'))
            ->setResourceKey('roles')
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
            ->addDatagridAdapters(['table'])
            ->getRoute();
    }

    public function testBuildDatagridRouteWithSearch()
    {
        $route = (new DatagridRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
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
            ->addDatagridAdapters(['tree'])
            ->enableMoving()
            ->disableMoving()
            ->getRoute();

        $this->assertFalse($route->getOption('movable'));
    }
}
