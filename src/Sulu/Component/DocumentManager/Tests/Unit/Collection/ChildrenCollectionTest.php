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
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Collection\ChildrenCollection;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ChildrenCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->childNode = $this->prophesize(NodeInterface::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);

        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->collection = new ChildrenCollection(
            $this->parentNode->reveal(),
            $this->dispatcher->reveal(),
            'fr'
        );
    }

    /**
     * It should be iterable.
     */
    public function testIterable()
    {
        $children = new \ArrayIterator([
            $this->childNode->reveal(),
        ]);
        $this->parentNode->getNodes()->willReturn($children);

        $this->dispatcher->dispatch(Events::HYDRATE, Argument::type('Sulu\Component\DocumentManager\Event\HydrateEvent'))->will(function ($args) {
            $args[1]->setDocument(new \stdClass());
        });

        $results = [];

        foreach ($this->collection as $document) {
            $results[] = $document;
        }

        $this->assertCount(1, $results);
        $this->assertContainsOnlyInstancesOf('stdClass', $results);
    }
}
