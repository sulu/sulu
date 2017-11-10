<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit;

use Sulu\Component\Content\ContentTypeManager;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContentTypeManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ContentTypeManagerInterface
     */
    protected $manager;

    public function setUp()
    {
        $this->container = $this->getMock(ContainerInterface::class);
        $this->manager = new ContentTypeManager($this->container);

        $this->manager->mapAliasToServiceId('content_1.alias', 'content_1.service.id');
        $this->manager->mapAliasToServiceId('content_2.alias', 'content_2.service.id');
    }

    public function testGetContentType()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('content_1.service.id');

        $this->manager->get('content_1.alias');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage has not been registered
     */
    public function testGetContentTypeNotRegistered()
    {
        $this->manager->get('invalid.alias');
    }

    public function provideHas()
    {
        return [
            ['content_1.alias', true],
            ['invalid.alias', false],
        ];
    }

    /**
     * @dataProvider provideHas
     */
    public function testHas($alias, $expected)
    {
        $res = $this->manager->has($alias);
        $this->assertEquals($expected, $res);
    }
}
