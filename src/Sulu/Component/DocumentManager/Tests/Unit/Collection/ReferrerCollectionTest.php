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
use PHPCR\PropertyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Collection\ReferrerCollection;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReferrerCollectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PropertyInterface>
     */
    private $reference;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $referrerNode;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    private $dispatcher;

    /**
     * @var ReferrerCollection
     */
    private $collection;

    public function setUp(): void
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
    public function testIterable(): void
    {
        $references = new \ArrayIterator([
            $this->reference->reveal(),
        ]);
        $this->node->getReferences()->willReturn($references);
        $this->reference->getParent()->willReturn($this->referrerNode->reveal());
        $this->referrerNode->getIdentifier()->willReturn('1234');

        $this->dispatcher
            ->dispatch(Argument::type(HydrateEvent::class), Events::HYDRATE)
            ->will(function($args) {
                $args[0]->setDocument(new \stdClass());

                return $args[0];
            });

        $results = [];

        foreach ($this->collection as $document) {
            $results[] = $document;
        }

        $this->assertCount(1, $results);
        $this->assertContainsOnlyInstancesOf('stdClass', $results);
    }
}
