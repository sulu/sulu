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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Collection\ChildrenCollection;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ChildrenCollectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $childNode;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $parentNode;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    private $dispatcher;

    /**
     * @var ChildrenCollection
     */
    private $collection;

    public function setUp(): void
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
    public function testIterable(): void
    {
        $children = new \ArrayIterator([
            $this->childNode->reveal(),
        ]);
        $this->parentNode->getNodes()->willReturn($children);

        $this->dispatcher
            ->dispatch(Argument::type(HydrateEvent::class), Events::HYDRATE)
            ->will(function($args) {
                $args[0]->setDocument(new \stdClass());

                return $args[0];
            })
        ;

        $results = [];

        foreach ($this->collection as $document) {
            $results[] = $document;
        }

        $this->assertCount(1, $results);
        $this->assertContainsOnlyInstancesOf('stdClass', $results);
    }
}
