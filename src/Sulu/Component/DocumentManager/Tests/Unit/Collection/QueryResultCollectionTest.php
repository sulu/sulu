<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Comonent\DocumentManager\tests\Unit\Collection;

use PHPCR\NodeInterface;
use PHPCR\Query\QueryResultInterface;
use PHPCR\Query\RowInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\DocumentManager\Collection\QueryResultCollection;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class QueryResultCollectionTest extends TestCase
{
    use ProphecyTrait;

    public function setUp(): void
    {
        $this->queryResult = $this->prophesize(QueryResultInterface::class);
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->collection = new QueryResultCollection(
            $this->queryResult->reveal(),
            $this->dispatcher->reveal(),
            'fr',
            [],
            's'
        );

        $this->row1 = $this->prophesize(RowInterface::class);
        $this->row2 = $this->prophesize(RowInterface::class);
        $this->node1 = $this->prophesize(NodeInterface::class);
        $this->node2 = $this->prophesize(NodeInterface::class);
    }

    /**
     * It should be iterable.
     */
    public function testIterable(): void
    {
        $results = new \ArrayIterator([
            $this->row1->reveal(),
            $this->row2->reveal(),
        ]);

        $this->row1->getNode('s')->willReturn($this->node1->reveal());
        $this->row2->getNode('s')->willReturn($this->node2->reveal());

        $this->queryResult->getRows()->willReturn($results);

        $this->dispatcher->dispatch(Argument::type('Sulu\Component\DocumentManager\Event\HydrateEvent'), Events::HYDRATE)->will(function($args) {
            $args[0]->setDocument(new \stdClass());

            return $args[0];
        });

        $results = [];

        foreach ($this->collection as $document) {
            $results[] = $document;
        }

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf('stdClass', $results);
    }
}
