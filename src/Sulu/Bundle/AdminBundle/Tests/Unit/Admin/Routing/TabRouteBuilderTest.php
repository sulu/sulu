<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\Routing;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\Routing\TabRouteBuilder;

class TabRouteBuilderTest extends TestCase
{
    public function testBuildTabRouteWithClone()
    {
        $routeBuilder = (new TabRouteBuilder('sulu_role.add_form', '/roles'));

        $this->assertNotSame($routeBuilder->getRoute(), $routeBuilder->getRoute());
    }

    public function provideBuildTabRoute()
    {
        return [
            [
                'sulu_category.add_form',
                '/categories/add',
            ],
            [
                'sulu_tag.edit_form',
                '/tags/:id',
            ],
        ];
    }

    /**
     * @dataProvider provideBuildTabRoute
     */
    public function testBuildTabRoute(
        string $name,
        string $path
    ) {
        $routeBuilder = (new TabRouteBuilder($name, $path));
        $route = $routeBuilder->getRoute();

        $this->assertSame($name, $route->getName());
        $this->assertSame($path, $route->getPath());
        $this->assertSame('sulu_admin.tabs', $route->getView());
    }

    public function testBuildFormWithParent()
    {
        $route = (new TabRouteBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setParent('sulu_admin.test')
            ->getRoute();

        $this->assertSame('sulu_admin.test', $route->getParent());
    }

    public function testBuildFormWithOption()
    {
        $route = (new TabRouteBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setOption('resourceKey', 'test')
            ->getRoute();

        $this->assertSame('test', $route->getOption('resourceKey'));
    }
}
