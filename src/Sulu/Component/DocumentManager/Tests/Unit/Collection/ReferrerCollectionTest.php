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
use PHPCR\PropertyInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Collection\ReferrerCollection;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReferrerCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->reference = $this->prophesize(PropertyInterface::class);
        $this->referrerNode = $this->prophesize(NodeInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);

        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->collection = new ReferrerCollection(
            $this->node->reveal(),
            $this->dispatcher->reveal(),
            'fr'
        );
    }

    /**
     * It should be iterable.
     */
    public function testIterable()
    {
        $references = new \ArrayIterator([
            $this->reference->reveal(),
        ]);
        $this->node->getReferences()->willReturn($references);
        $this->reference->getParent()->willReturn($this->referrerNode->reveal());
        $this->referrerNode->getIdentifier()->willReturn('1234');

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
