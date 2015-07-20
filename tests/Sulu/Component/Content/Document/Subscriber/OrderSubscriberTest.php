<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Prophecy\Argument;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;

class OrderSubscriberTest extends SubscriberTestCase
{
    /**
     * @var OrderSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        parent::setUp();

        $this->subscriber = new OrderSubscriber($this->encoder->reveal());
        $this->hydrateEvent->getDocument()->willReturn(new TestOrderDocument(10));
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
    }

    public function testPersist()
    {
        $document = $this->prophesize(OrderBehavior::class);
        $this->persistEvent->getDocument()->willReturn($document);
        $this->encoder->systemName('order')->willReturn('sys:order');

        $node1 = $this->prophesize(NodeInterface::class);
        $node2 = $this->prophesize(NodeInterface::class);
        $node3 = $this->prophesize(NodeInterface::class);

        $parentNode = $this->prophesize(NodeInterface::class);
        $parentNode->getNodes()->willReturn(array($node1, $node2, $node3));
        $this->node->hasProperty('sys:order')->willReturn(false);
        $this->node->getParent()->willReturn($parentNode);
        $this->node->setProperty('sys:order', 40, PropertyType::LONG)->shouldBeCalled();
        $this->node->getPropertyValueWithDefault('sys:order', null)->willReturn(40);
        $this->accessor->set('suluOrder', 40)->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testReorder()
    {
        $document = $this->prophesize(OrderBehavior::class);
        $reorderEvent = $this->prophesize(ReorderEvent::class);
        $reorderEvent->getDocument()->willReturn($document);
        $reorderEvent->getNode()->willReturn($this->node);
        $reorderEvent->getAccessor()->willReturn($this->accessor);
        $this->encoder->systemName('order')->willReturn('sys:order');

        $node2 = $this->prophesize(NodeInterface::class);
        $node3 = $this->prophesize(NodeInterface::class);

        $parentNode = $this->prophesize(NodeInterface::class);
        $parentNode->getNodes()->willReturn(array($this->node, $node2, $node3));
        $this->node->getParent()->willReturn($parentNode);
        $this->node->setProperty('sys:order', 10, PropertyType::LONG)->shouldBeCalled();
        $node2->setProperty('sys:order', 20, PropertyType::LONG)->shouldBeCalled();
        $node3->setProperty('sys:order', 30, PropertyType::LONG)->shouldBeCalled();
        $this->node->getPropertyValueWithDefault('sys:order', null)->willReturn(40);
        $this->accessor->set('suluOrder', 40)->shouldBeCalled();

        $this->subscriber->handleReorder($reorderEvent->reveal());
    }

    /**
     * It should set the order on the document.
     */
    public function testHydrateOrder()
    {
        $this->encoder->systemName('order')->willReturn('sys:order');
        $this->node->getPropertyValueWithDefault('sys:order', null)->willReturn(50);
        $this->accessor->set('suluOrder', 50)->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }
}

class TestOrderDocument implements OrderBehavior
{
    private $suluOrder;

    public function __construct($order)
    {
        $this->suluOrder = $order;
    }

    public function getSuluOrder()
    {
        return $this->suluOrder;
    }
}
