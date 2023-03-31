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
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContentTypeManagerTest extends TestCase
{
    protected $container;

    public function setUp(): void
    {
        $this->container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $this->manager = new ContentTypeManager($this->container);

        $this->manager->mapAliasToServiceId('content_1.alias', 'content_1.service.id');
        $this->manager->mapAliasToServiceId('content_2.alias', 'content_2.service.id');
    }

    public function testGetContentType(): void
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('content_1.service.id');

        $this->manager->get('content_1.alias');
    }

    public function testGetContentTypeNotRegistered(): void
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
    public function testHas($alias, $expected): void
    {
        $res = $this->manager->has($alias);
        $this->assertEquals($expected, $res);
    }

    public function testAll(): void
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
