<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Comonent\DocumentManager\tests\Unit\Collection;

use PHPCR\NodeInterface;
use PHPCR\Query\QueryResultInterface;
use PHPCR\Query\RowInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Collection\QueryResultCollection;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class QueryResultCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
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
    public function testIterable()
    {
        $results = new \ArrayIterator([
            $this->row1->reveal(),
            $this->row2->reveal(),
        ]);

        $this->row1->getNode('s')->willReturn($this->node1->reveal());
        $this->row2->getNode('s')->willReturn($this->node2->reveal());

        $this->queryResult->getRows()->willReturn($results);

        $this->dispatcher->dispatch(Events::HYDRATE, Argument::type('Sulu\Component\DocumentManager\Event\HydrateEvent'))->will(function ($args) {
            $args[1]->setDocument(new \stdClass());
        });

        $results = [];

        foreach ($this->collection as $document) {
            $results[] = $document;
        }

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf('stdClass', $results);
    }
}
