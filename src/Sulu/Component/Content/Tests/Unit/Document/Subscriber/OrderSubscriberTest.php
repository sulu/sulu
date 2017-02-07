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

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\Content\Document\Subscriber\OrderSubscriber;
use Sulu\Component\DocumentManager\Event\ReorderEvent;

class OrderSubscriberTest extends SubscriberTestCase
{
    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var OrderSubscriber
     */
    private $subscriber;

    /**
     * @var OrderBehavior
     */
    private $document;

    public function setUp()
    {
        parent::setUp();

        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->subscriber = new OrderSubscriber($this->documentInspector->reveal(), $this->propertyEncoder->reveal());

        $this->document = $this->prophesize(OrderBehavior::class);

        $this->propertyEncoder->systemName('order')->willReturn('sulu:order');
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
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
        $childDocument1 = $this->prophesize(OrderBehavior::class);
        $childDocument2 = new \stdClass();
        $childDocument3 = $this->prophesize(OrderBehavior::class);
        $childDocument4 = $this->prophesize(OrderBehavior::class);

        $childNode1 = $this->prophesize(NodeInterface::class);
        $childNode2 = $this->prophesize(NodeInterface::class);
        $childNode3 = $this->prophesize(NodeInterface::class);
        $childNode4 = $this->prophesize(NodeInterface::class);

        $this->documentInspector->getNode($childDocument1->reveal())->willReturn($childNode1);
        $this->documentInspector->getNode($childDocument2)->willReturn($childNode2);
        $this->documentInspector->getNode($childDocument3->reveal())->willReturn($childNode3);
        $this->documentInspector->getNode($childDocument4->reveal())->willReturn($childNode4);

        $this->documentInspector->getParent($document->reveal())->willReturn($parentDocument->reveal());
        $this->documentInspector->getChildren($parentDocument->reveal())->willReturn([
            $childDocument1->reveal(),
            $childDocument2,
            $childDocument3->reveal(),
            $childDocument4->reveal(),
        ]);

        $reorderEvent = $this->prophesize(ReorderEvent::class);
        $reorderEvent->getDocument()->willReturn($document);

        $childNode1->setProperty('sulu:order', 10)->shouldBeCalled();
        $childNode2->setProperty('sulu:order', Argument::any())->shouldNotBeCalled();
        $childNode3->setProperty('sulu:order', 20)->shouldBeCalled();
        $childNode4->setProperty('sulu:order', 30)->shouldBeCalled();

        $childDocument1->setSuluOrder(10)->shouldBeCalled();
        $childDocument3->setSuluOrder(20)->shouldBeCalled();
        $childDocument4->setSuluOrder(30)->shouldBeCalled();

        $this->subscriber->handleReorder($reorderEvent->reveal());
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
        $this->documentInspector->getParent(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * It should return early on REORDER if the document has no parent (i.e. if the document is the root document and this shoouldn't really happen).
     */
    public function testReorderNoParent()
    {
        $document = $this->prophesize(OrderBehavior::class);

        $reorderEvent = $this->prophesize(ReorderEvent::class);
        $reorderEvent->getDocument()->willReturn($document->reveal());
        $this->documentInspector->getParent($document->reveal())->willReturn(null);

        $this->subscriber->handleReorder($reorderEvent->reveal());
        $this->documentInspector->getChildren(Argument::any())->shouldNotHaveBeenCalled();
    }

    public function testPersistOrder()
    {
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->parentNode->getNodes()->willReturn([
            $this->node->reveal(),
        ]);
        $this->document->getSuluOrder()->willReturn(null);
        $this->document->setSuluOrder(20)->shouldBeCalled();
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testPersistOrderWithExistingOrder()
    {
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->parentNode->getNodes()->willReturn([
            $this->node->reveal(),
        ]);
        $this->document->getSuluOrder()->willReturn(10);
        $this->document->setSuluOrder(Argument::any())->shouldNotBeCalled();
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }
}
