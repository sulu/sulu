<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\ContentTypeManager;

class ContentTypeManagerTest extends TestCase
{
    protected $container;

    public function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')->getMock();
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

    public function testGetContentTypeNotRegistered()
    {
        $this->expectExceptionMessage('has not been registered');
        $this->expectException(\InvalidArgumentException::class);
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

    public function testAll()
    {
        $this->assertEquals(
            [
                'content_1.alias',
                'content_2.alias',
            ],
            $this->manager->getAll()
        );
    }
}
