<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Mapping;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\ParentSubscriber;

class ParentSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HydrateEvent
     */
    private $hydrateEvent;

    /**
     * @var MoveEvent
     */
    private $moveEvent;

    /**
     * @var ParentBehavior
     */
    private $document;

    /**
     * @var \stdClass
     */
    private $notImplementing;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var NodeInterface
     */
    private $parentNode;

    /**
     * @var \stdClass
     */
    private $parentDocument;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var ParentSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->moveEvent = $this->prophesize(MoveEvent::class);
        $this->document = $this->prophesize(ParentBehavior::class);
        $this->notImplementing = new \stdClass();
        $this->node = $this->prophesize(NodeInterface::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->parentDocument = new \stdClass();
        $this->proxyFactory = $this->prophesize(ProxyFactory::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->documentManager = $this->prophesize(DocumentManager::class);

        $this->subscriber = new ParentSubscriber(
            $this->proxyFactory->reveal(),
            $this->inspector->reveal(),
            $this->documentManager->reveal()
        );

        $this->hydrateEvent->getNode()->willReturn($this->node);
    }

    public function testHydrateNotImplementing()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    public function testHydrateParent()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());
        $this->hydrateEvent->getOptions()->willReturn(['test' => true]);

        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->node->getDepth()->willReturn(2);

        $this->proxyFactory->createProxyForNode($this->document->reveal(), $this->parentNode->reveal(), ['test' => true])
            ->willReturn($this->parentDocument);
        $this->parentNode->hasProperty('jcr:uuid')->willReturn(true);

        $this->document->setParent($this->parentDocument)->shouldBeCalled();

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    public function testHydrateParentNoUuid()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());
        $this->hydrateEvent->getOptions()->willReturn(['test' => true]);

        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->node->getDepth()->willReturn(2);
        $this->parentNode->hasProperty('jcr:uuid')->willReturn(false);

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThrowExceptionRootNode()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());

        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->node->getDepth()->willReturn(0);

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    public function testMove()
    {
        $this->moveEvent->getDocument()->willReturn($this->document);
        $this->inspector->getNode($this->document)->willReturn($this->node);

        $this->node->getParent()->willReturn($this->parentNode);
        $this->parentNode->hasProperty('jcr:uuid')->willReturn(true);
        $this->document->setParent(Argument::any())->shouldBeCalled();

        $this->subscriber->handleMove($this->moveEvent->reveal());
    }

    public function testHandleChangeParent()
    {
        $persistEvent = $this->prophesize(PersistEvent::class);
        $persistEvent->getDocument()->willReturn($this->document->reveal());
        $persistEvent->getNode()->willReturn($this->node->reveal());
        $this->inspector->getNode($this->document->reveal())->willReturn($this->node->reveal());
        $this->node->getParent()->willReturn($this->parentNode->reveal());

        $newParentNode = $this->prophesize(NodeInterface::class);
        $newParentNode->getPath()->willReturn('/path/to/new/parent');
        $persistEvent->getParentNode()->willReturn($newParentNode->reveal());

        $this->documentManager->move($this->document->reveal(), '/path/to/new/parent')->shouldBeCalled();

        $this->subscriber->handleChangeParent($persistEvent->reveal());
    }

    public function testHandleChangeParentWithSameParent()
    {
        $persistEvent = $this->prophesize(PersistEvent::class);
        $persistEvent->getDocument()->willReturn($this->document->reveal());
        $persistEvent->getNode()->willReturn($this->node->reveal());
        $this->inspector->getNode($this->document->reveal())->willReturn($this->node->reveal());
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $persistEvent->getParentNode()->willReturn($this->parentNode->reveal());

        $this->documentManager->move(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->handleChangeParent($persistEvent->reveal());
    }

    public function testHandleChangeParentWithWrongDocument()
    {
        $persistEvent = $this->prophesize(PersistEvent::class);
        $persistEvent->getDocument(new \stdClass());

        $this->documentManager->move(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->handleChangeParent($persistEvent->reveal());
    }
}
