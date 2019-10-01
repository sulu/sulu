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
use Sulu\Bundle\AdminBundle\Admin\View\RouteBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;

class ViewCollectionTest extends TestCase
{
    public function testGet()
    {
        $routeBuilder = new RouteBuilder('sulu_test', '/test', 'test');

        $viewCollection = new ViewCollection();
        $viewCollection->add($routeBuilder);

        $this->assertEquals($routeBuilder, $viewCollection->get('sulu_test'));
    }

    public function testHas()
    {
        $routeBuilder = new RouteBuilder('sulu_test', '/test', 'test');

        $viewCollection = new ViewCollection();
        $viewCollection->add($routeBuilder);

        $this->assertTrue($viewCollection->has('sulu_test'));
    }

    public function testHasNotExisting()
    {
        $viewCollection = new ViewCollection();

        $this->assertFalse($viewCollection->has('sulu_test'));
    }

    public function testAll()
    {
        $routeBuilder1 = new RouteBuilder('sulu_test_1', '/test', 'test');
        $routeBuilder2 = new RouteBuilder('sulu_test_2', '/test', 'test');

        $viewCollection = new ViewCollection();
        $viewCollection->add($routeBuilder1);
        $viewCollection->add($routeBuilder2);

        $routes = $viewCollection->all();

        $this->assertContains($routeBuilder1, $routes);
        $this->assertContains($routeBuilder2, $routes);
    }

    public function testGetNotExistingRoute()
    {
        $this->expectException(ViewNotFoundException::class);

        $viewCollection = new ViewCollection();
        $viewCollection->get('not-existing');
    }
}
