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
use Sulu\Bundle\AdminBundle\Admin\RouteCollection;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilder;
use Sulu\Bundle\AdminBundle\Exception\RouteNotFoundException;

class RouteCollectionTest extends TestCase
{
    public function testGet()
    {
        $routeBuilder = new RouteBuilder('sulu_test', '/test', 'test');

        $routeCollection = new RouteCollection();
        $routeCollection->add($routeBuilder);

        $this->assertEquals($routeBuilder, $routeCollection->get('sulu_test'));
    }

    public function testAll()
    {
        $routeBuilder1 = new RouteBuilder('sulu_test_1', '/test', 'test');
        $routeBuilder2 = new RouteBuilder('sulu_test_2', '/test', 'test');

        $routeCollection = new RouteCollection();
        $routeCollection->add($routeBuilder1);
        $routeCollection->add($routeBuilder2);

        $routes = $routeCollection->all();

        $this->assertContains($routeBuilder1, $routes);
        $this->assertContains($routeBuilder2, $routes);
    }

    public function testGetNotExistingRoute()
    {
        $this->expectException(RouteNotFoundException::class);

        $routeCollection = new RouteCollection();
        $routeCollection->get('not-existing');
    }
}
