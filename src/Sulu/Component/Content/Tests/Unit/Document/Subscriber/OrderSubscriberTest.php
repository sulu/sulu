<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\Content\Document\Subscriber\OrderSubscriber;
use Sulu\Component\DocumentManager\Event\ReorderEvent;

class OrderSubscriberTest extends SubscriberTestCase
{
    /**
     * @var OrderSubscriber
     */
    private $subscriber;

    private $inspector;

    public function setUp()
    {
        parent::setUp();

        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->subscriber = new OrderSubscriber($this->inspector->reveal());
        $this->persistEvent->getDocument()->willReturn(new TestOrderDocument(null));
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
    }

    /**
     * It should set the sulu order on sibling documents of the persisted documents according to their natural order upon REORDER.
     * It should not take non-implementing documents into account when recalculating the orders.
     */
    public function testReorder()
    {
        $document = $this->prophesize(OrderBehavior::class);
        $parentDocument = $this->prophesize(OrderBehavior::class);
        $childDocument1 = new TestOrderDocument(0);
        $childDocument2 = new \stdClass();
        $childDocument3 = new TestOrderDocument(20);
        $childDocument4 = new TestOrderDocument(10);

        $this->inspector->getParent($document->reveal())->willReturn($parentDocument->reveal());
        $this->inspector->getChildren($parentDocument->reveal())->willReturn([
            $childDocument1,
            $childDocument2,
            $childDocument3,
            $childDocument4,
        ]);

        $reorderEvent = $this->prophesize(ReorderEvent::class);
        $reorderEvent->getDocument()->willReturn($document);

        $this->subscriber->handleReorder($reorderEvent->reveal());

        $this->assertEquals(10, $childDocument1->getSuluOrder());
        $this->assertEquals(20, $childDocument3->getSuluOrder());
        $this->assertEquals(30, $childDocument4->getSuluOrder());
    }

    /**
     * It should return early on REORDER if the document is not an instance of OrderBehavior.
     */
    public function testReorderNotImplementing()
    {
        $document = new \stdClass();

        $reorderEvent = $this->prophesize(ReorderEvent::class);
        $reorderEvent->getDocument()->willReturn($document);

        $this->subscriber->handleReorder($reorderEvent->reveal());
        $this->inspector->getParent(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * It should return early on REORDER if the document has no parent (i.e. if the document is the root document and this shoouldn't really happen).
     */
    public function testReorderNoParent()
    {
        $document = $this->prophesize(OrderBehavior::class);

        $reorderEvent = $this->prophesize(ReorderEvent::class);
        $reorderEvent->getDocument()->willReturn($document->reveal());
        $this->inspector->getParent($document->reveal())->willReturn(null);

        $this->subscriber->handleReorder($reorderEvent->reveal());
        $this->inspector->getChildren(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * It should set the order on the document.
     */
    public function testPersistOrder()
    {
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->parentNode->getNodes()->willReturn([
            $this->node->reveal(),
        ]);
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
