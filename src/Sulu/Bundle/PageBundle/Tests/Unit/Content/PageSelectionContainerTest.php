<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Content;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\PageBundle\Content\PageSelectionContainer;
use Sulu\Component\Content\Query\ContentQueryBuilder;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Sulu\Component\Security\Authentication\UserInterface;

class PageSelectionContainerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var PageSelectionContainer
     */
    private $container;

    /**
     * @var ContentQueryExecutor
     */
    private $executor;

    /**
     * @var ContentQueryBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->executor = $this->prophesize(ContentQueryExecutor::class);
        $this->builder = $this->prophesize(ContentQueryBuilder::class);
    }

    public function testGetDataDraftAndPublished()
    {
        $this->container = new PageSelectionContainer(
            [2, 3, 1],
            $this->executor->reveal(),
            $this->builder->reveal(),
            [],
            'default',
            'en',
            true
        );

        $this->builder->init(['ids' => [2, 3, 1], 'properties' => [], 'published' => false])->shouldBeCalled();
        $this->executor->execute('default', ['en'], $this->builder, true, -1, null, null, false, null)->willReturn(
            [['id' => 1], ['id' => 2], ['id' => 3]]
        );

        $this->container->getData();
    }

    public function testGetDataWithPermissions()
    {
        $this->container = new PageSelectionContainer(
            [2, 3, 1],
            $this->executor->reveal(),
            $this->builder->reveal(),
            [],
            'default',
            'en',
            true,
            64
        );

        $this->builder->init(['ids' => [2, 3, 1], 'properties' => [], 'published' => false])->shouldBeCalled();
        $this->executor->execute('default', ['en'], $this->builder, true, -1, null, null, false, 64)->willReturn(
            [['id' => 1], ['id' => 2], ['id' => 3]]
        );

        $this->container->getData();
    }

    public function testGetDataOnlyPublished()
    {
        $this->container = new PageSelectionContainer(
            [2, 3, 1],
            $this->executor->reveal(),
            $this->builder->reveal(),
            [],
            'default',
            'en',
            false
        );

        $this->builder->init(['ids' => [2, 3, 1], 'properties' => [], 'published' => true])->shouldBeCalled();
        $this->executor->execute('default', ['en'], $this->builder, true, -1, null, null, false, null)->willReturn(
            [['id' => 1], ['id' => 2], ['id' => 3]]
        );

        $this->container->getData();
    }

    public function testGetDataOrder()
    {
        $this->executor->execute('default', ['en'], $this->builder, true, -1, null, null, false, null)->willReturn([
            ['id' => 1, 'path' => 'phpcr/path/1'],
            ['id' => 2],
            ['id' => 3],
        ]);

        $this->container = new PageSelectionContainer(
            [2, 3, 1],
            $this->executor->reveal(),
            $this->builder->reveal(),
            [],
            'default',
            'en',
            false
        );

        $result = $this->container->getData();
        $this->assertEquals([['id' => 2], ['id' => 3], ['id' => 1, 'path' => 'phpcr/path/1']], $result);
    }

    public function testGetDataWithUser()
    {
        $user = $this->prophesize(UserInterface::class);

        $this->container = new PageSelectionContainer(
            [2, 3, 1],
            $this->executor->reveal(),
            $this->builder->reveal(),
            [],
            'default',
            'en',
            true,
            $user->reveal()
        );

        $this->builder->init(['ids' => [2, 3, 1], 'properties' => [], 'published' => false])->shouldBeCalled();
        $this->executor
             ->execute('default', ['en'], $this->builder, true, -1, null, null, false, $user->reveal())
             ->willReturn([['id' => 1], ['id' => 2], ['id' => 3]]
        );

        $this->container->getData();
    }

    public function testGetDataWithoutPathParameter()
    {
        $this->executor->execute('default', ['en'], $this->builder, true, -1, null, null, false, null)->willReturn([
            ['id' => 1, 'path' => 'phpcr/path/1'],
            ['id' => 2],
        ]);

        $this->container = new PageSelectionContainer(
            [1, 2],
            $this->executor->reveal(),
            $this->builder->reveal(),
            [],
            'default',
            'en',
            false,
            null,
            ['path' => false]
        );

        $result = $this->container->getData();
        $this->assertEquals([['id' => 1], ['id' => 2]], $result);
    }
}
