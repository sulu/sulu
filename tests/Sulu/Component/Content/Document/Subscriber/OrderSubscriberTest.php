<?php

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;

use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Content\Document\Subscriber\OrderSubscriber;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\Content\Document\Subscriber\SubscriberTestCase;

class OrderSubscriberTest extends SubscriberTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->subscriber = new OrderSubscriber($this->encoder->reveal());
        $this->persistEvent->getDocument()->willReturn(new TestOrderDocument(null));
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
    }

    /**
     * It should set the order on the document.
     */
    public function testPersistOrder()
    {
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->parentNode->getNodes()->willReturn(array(
            $this->node->reveal()
        ));
        $this->persistEvent->getAccessor()->willReturn($this->accessor->reveal());
        $this->accessor->set('suluOrder', 20)->shouldBeCalled();
        $this->subscriber->handlePersist($this->persistEvent->reveal());
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
