<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Mapping;

use PHPCR\NodeInterface;
use PHPCR\NodeType\NodeTypeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\ParentSubscriber;

class ParentSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<HydrateEvent>
     */
    private $hydrateEvent;

    /**
     * @var ObjectProphecy<MoveEvent>
     */
    private $moveEvent;

    /**
     * @var ObjectProphecy<ParentBehavior>
     */
    private $document;

    /**
     * @var \stdClass
     */
    private $notImplementing;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var ObjectProphecy<NodeTypeInterface>
     */
    private $primaryNodeType;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $parentNode;

    /**
     * @var \stdClass
     */
    private $parentDocument;

    /**
     * @var ObjectProphecy<ProxyFactory>
     */
    private $proxyFactory;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $inspector;

    /**
     * @var ObjectProphecy<DocumentManager>
     */
    private $documentManager;

    /**
     * @var ParentSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->moveEvent = $this->prophesize(MoveEvent::class);
        $this->document = $this->prophesize(ParentBehavior::class);
        $this->notImplementing = new \stdClass();
        $this->node = $this->prophesize(NodeInterface::class);
        $this->primaryNodeType = $this->prophesize(NodeTypeInterface::class);
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

        $this->node->getPrimaryNodeType()->willReturn($this->primaryNodeType->reveal());

        $this->hydrateEvent->getNode()->willReturn($this->node);
    }

    public function testHydrateNotImplementing(): void
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing)->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    public function testHydrateParent(): void
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

    public function testHydrateParentNoUuid(): void
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal())->shouldBeCalled();
        $this->hydrateEvent->getOptions()->willReturn(['test' => true])->shouldBeCalled();

        $this->node->getParent()->willReturn($this->parentNode->reveal())->shouldBeCalled();
        $this->node->getDepth()->willReturn(2)->shouldBeCalled();
        $this->parentNode->hasProperty('jcr:uuid')->willReturn(false)->shouldBeCalled();

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    public function testThrowExceptionRootNode(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());

        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->node->getDepth()->willReturn(0);
        $this->node->getPath()->willReturn('/');

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    public function testMove(): void
    {
        $this->moveEvent->getDocument()->willReturn($this->document);
        $this->inspector->getNode($this->document)->willReturn($this->node);

        $this->node->getParent()->willReturn($this->parentNode);
        $this->parentNode->hasProperty('jcr:uuid')->willReturn(true);
        $this->document->setParent(Argument::any())->shouldBeCalled();

        $this->subscriber->handleMove($this->moveEvent->reveal());
    }

    public function testHandleChangeParent(): void
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

    public function testHandleChangeParentWithSameParent(): void
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

    public function testHandleChangeParentWithWrongDocument(): void
    {
        $persistEvent = $this->prophesize(PersistEvent::class);
        $persistEvent->getDocument(new \stdClass());

        $this->documentManager->move(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->handleChangeParent($persistEvent->reveal());
    }
}
