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

use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\DocumentManager\Event\HydrateEvent;

class OrderSubscriberTest extends SubscriberTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->subscriber = new OrderSubscriber($this->encoder->reveal());
        $this->hydrateEvent->getDocument()->willReturn(new TestOrderDocument(10));
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
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
