<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content;

use Sulu\Bundle\ContentBundle\Content\InternalLinksContainer;
use Sulu\Component\Content\Query\ContentQueryBuilder;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Symfony\Component\HttpKernel\Log\NullLogger;

class InternalLinksContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InternalLinksContainer
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

    protected function setUp()
    {
        $this->executor = $this->prophesize(ContentQueryExecutor::class);
        $this->builder = $this->prophesize(ContentQueryBuilder::class);
    }

    public function testGetDataDraftAndPublished()
    {
        $this->container = new InternalLinksContainer(
            [2, 3, 1],
            $this->executor->reveal(),
            $this->builder->reveal(),
            [],
            new NullLogger(),
            'default',
            'en',
            true
        );

        $this->builder->init(['ids' => [2, 3, 1], 'properties' => [], 'published' => false])->shouldBeCalled();
        $this->executor->execute('default', ['en'], $this->builder)->willReturn(
            [['uuid' => 1], ['uuid' => 2], ['uuid' => 3]]
        );

        $this->container->getData();
    }

    public function testGetDataOnlyPublished()
    {
        $this->container = new InternalLinksContainer(
            [2, 3, 1],
            $this->executor->reveal(),
            $this->builder->reveal(),
            [],
            new NullLogger(),
            'default',
            'en',
            false
        );

        $this->builder->init(['ids' => [2, 3, 1], 'properties' => [], 'published' => true])->shouldBeCalled();
        $this->executor->execute('default', ['en'], $this->builder)->willReturn(
            [['uuid' => 1], ['uuid' => 2], ['uuid' => 3]]
        );

        $this->container->getData();
    }

    public function testGetDataOrder()
    {
        $this->executor->execute('default', ['en'], $this->builder)->willReturn(
            [['uuid' => 1], ['uuid' => 2], ['uuid' => 3]]
        );

        $this->container = new InternalLinksContainer(
            [2, 3, 1],
            $this->executor->reveal(),
            $this->builder->reveal(),
            [],
            new NullLogger(),
            'default',
            'en',
            false
        );

        $result = $this->container->getData();
        $this->assertEquals([['uuid' => 2], ['uuid' => 3], ['uuid' => 1]], $result);
    }
}
