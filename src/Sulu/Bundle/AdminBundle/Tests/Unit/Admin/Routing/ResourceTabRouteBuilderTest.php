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
use Sulu\Bundle\AdminBundle\Admin\Routing\ResourceTabRouteBuilder;

class ResourceTabRouteBuilderTest extends TestCase
{
    public function testBuildResourceTabRouteWithClone()
    {
        $routeBuilder = (new ResourceTabRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles');

        $this->assertNotSame($routeBuilder->getRoute(), $routeBuilder->getRoute());
    }

    public function testBuildResourceTabRouteWithoutResourceKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('/"setResourceKey"/');

        $route = (new ResourceTabRouteBuilder('sulu_category.datagrid', '/category'))
            ->getRoute();
    }

    public function provideBuildResourceTabRoute()
    {
        return [
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                'sulu_category.datagrid',
            ],
            [
                'sulu_tag.edit_form',
                '/tags/:id',
                'tags',
                null,
            ],
        ];
    }

    /**
     * @dataProvider provideBuildResourceTabRoute
     */
    public function testBuildResourceTabRoute(
        string $name,
        string $path,
        string $resourceKey,
        ?string $backRoute
    ) {
        $routeBuilder = (new ResourceTabRouteBuilder($name, $path))
            ->setResourceKey($resourceKey);

        if ($backRoute) {
            $routeBuilder->setBackRoute($backRoute);
        }

        $route = $routeBuilder->getRoute();

        $this->assertEquals($name, $route->getName());
        $this->assertEquals($path, $route->getPath());
        $this->assertEquals($resourceKey, $route->getOption('resourceKey'));
        $this->assertEquals($backRoute, $route->getOption('backRoute'));
        $this->assertEquals('sulu_admin.resource_tabs', $route->getView());
    }

    public function testBuildResourceTabWithLocales()
    {
        $route = (new ResourceTabRouteBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setResourceKey('roles')
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getRoute();

        $this->assertEquals(['de', 'en', 'nl', 'fr'], $route->getOption('locales'));
    }

    public function testBuildResourceTabWithLocalesWithoutLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $route = (new ResourceTabRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getRoute();
    }

    public function testBuildResourceTabWithoutLocalesWithLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $route = (new ResourceTabRouteBuilder('sulu_role.datagrid', '/roles/:locale'))
            ->setResourceKey('roles')
            ->getRoute();
    }
}
