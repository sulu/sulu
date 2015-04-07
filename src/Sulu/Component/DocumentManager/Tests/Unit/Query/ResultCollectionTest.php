<?php

namespace Sulu\Comonent\DocumentManager\Tests\Unit\Query;

use PHPCR\Query\QueryResultInterface;
use Sulu\Component\DocumentManager\Query\ResultCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Events;
use Prophecy\Argument;
use PHPCR\Query\RowInterface;

class ResultCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->queryResult = $this->prophesize(QueryResultInterface::class);
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->collection = new ResultCollection(
            $this->queryResult->reveal(),
            $this->dispatcher->reveal(),
            'fr',
            's'
        );

        $this->row1 = $this->prophesize(RowInterface::class);
        $this->row2 = $this->prophesize(RowInterface::class);
        $this->node1 = $this->prophesize(NodeInterface::class);
        $this->node2 = $this->prophesize(NodeInterface::class);
    }

    /**
     * It should be iterable
     */
    public function testIterable()
    {
        $results = new \ArrayIterator(array(
            $this->row1->reveal(),
            $this->row2->reveal()
        ));

        $this->row1->getNode('s')->willReturn($this->node1->reveal());
        $this->row2->getNode('s')->willReturn($this->node2->reveal());

        $this->queryResult->getRows()->willReturn($results);

        $this->dispatcher->dispatch(Events::HYDRATE, Argument::type('Sulu\Component\DocumentManager\Event\HydrateEvent'))->will(function ($args) {
            $args[1]->setDocument(new \stdClass);
        });

        $results = array();

        foreach ($this->collection as $document) {
            $results[] = $document;
        }

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf('stdClass', $results);
    }
}
