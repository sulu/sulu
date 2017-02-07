<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\DataFixtures;

use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DocumentFixtureLoaderTest extends \PHPUnit_Framework_TestCase
{
    private $container;
    private $loader;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->loader = new DocumentFixtureLoader($this->container->reveal());
    }

    /**
     * It should load, instantiate and order fixture classes
     * It should assign the container to classes implementing ContainerAwareInterface.
     */
    public function testLoad()
    {
        $fixtures = $this->loader->load([__DIR__ . '/fixtures']);
        $this->assertCount(3, $fixtures);
        $this->assertInstanceOf(fixtures\FoobarFixture::class, $fixtures[0]);
        $this->assertInstanceOf(fixtures\BarfooFixture::class, $fixtures[1]);
        $this->assertInstanceOf(fixtures\ContainerFixture::class, $fixtures[2]);

        $this->assertSame(
            $this->container->reveal(),
            $fixtures[2]->container
        );
    }
}
