<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\Util\PathHelper;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\RouteBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\Subscriber\RouteSubscriber;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

class RouteSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $documentInspector;

    /**
     * @var ObjectProphecy<SessionManagerInterface>
     */
    private $sessionManager;

    /**
     * @var ObjectProphecy<NodeManager>
     */
    private $nodeManager;

    /**
     * @var RouteSubscriber
     */
    private $routeSubscriber;

    public function setUp(): void
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);
        $this->nodeManager = $this->prophesize(NodeManager::class);

        $this->routeSubscriber = new RouteSubscriber(
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->sessionManager->reveal(),
            $this->nodeManager->reveal()
        );
    }

    public function testHydrate(): void
    {
        $hydrateEvent = $this->prophesize(HydrateEvent::class);
        $routeDocument = $this->prophesize(RouteBehavior::class);
        $routeNode = $this->prophesize(NodeInterface::class);

        $routeNode->getPropertyValue('sulu:history')->willReturn(true);

        $routeDocument->setHistory(true)->shouldBeCalled();

        $hydrateEvent->getDocument()->willReturn($routeDocument->reveal());
        $hydrateEvent->getNode()->willReturn($routeNode->reveal());

        $this->routeSubscriber->handleHydrate($hydrateEvent->reveal());
    }

    public function testHydrateWithWrongDocument(): void
    {
        $hydrateEvent = $this->prophesize(HydrateEvent::class);

        $hydrateEvent->getDocument()->willReturn(new \stdClass());
        $hydrateEvent->getNode()->shouldNotBeCalled();

        $this->routeSubscriber->handleHydrate($hydrateEvent->reveal());
    }

    public function testHandlePersist(): void
    {
        $persistEvent = $this->prophesize(PersistEvent::class);
        $routeDocument = $this->prophesize(RouteBehavior::class);
        $routeNode = $this->prophesize(NodeInterface::class);
        $targetDocument = $this->prophesize(WebspaceBehavior::class)
            ->willImplement(ResourceSegmentBehavior::class);
        $targetNode = $this->prophesize(NodeInterface::class);

        $routeDocument->getTargetDocument()->willReturn($targetDocument);
        $routeDocument->isHistory()->willReturn(false);

        $this->documentInspector->getLocale($routeDocument->reveal())->willReturn('de');
        $this->documentInspector->getNode($targetDocument->reveal())->willReturn($targetNode->reveal());
        $this->documentInspector->getPath($routeDocument->reveal())->willReturn('/cmf/sulu_io/routes/de/test');

        $targetDocument->getWebspaceName()->willReturn('sulu_io');
        $targetDocument->getResourceSegment()->willReturn('/test');
        $this->sessionManager->getRoutePath('sulu_io', 'de', null)->willReturn('/cmf/sulu_io/routes/de')->shouldBeCalled();

        $persistEvent->getNode()->willReturn($routeNode)->shouldBeCalled();
        $persistEvent->getDocument()->willReturn($routeDocument)->shouldBeCalled();

        $this->routeSubscriber->handlePersist($persistEvent->reveal());
    }

    public function testHandlePersistWithChange(): void
    {
        $persistEvent = $this->prophesize(PersistEvent::class);
        $routeDocument = $this->prophesize(RouteBehavior::class);
        $routeNode = $this->prophesize(NodeInterface::class);
        $targetDocument = $this->prophesize(WebspaceBehavior::class)
            ->willImplement(ResourceSegmentBehavior::class);
        $targetNode = $this->prophesize(NodeInterface::class);
        $newRouteDocument = $this->prophesize(RouteBehavior::class);
        $newRouteNode = $this->prophesize(NodeInterface::class);
        $oldRouteDocument = $this->prophesize(RouteBehavior::class);

        $routeDocument->getTargetDocument()->willReturn($targetDocument);
        $routeDocument->isHistory()->willReturn(false);
        $routeDocument->setHistory(true)->shouldBeCalled();

        $routeNode->setProperty('sulu:history', false)->shouldBeCalled();

        $this->documentInspector->getLocale($routeDocument->reveal())->willReturn('de');
        $this->documentInspector->getNode($targetDocument->reveal())->willReturn($targetNode->reveal());
        $this->documentInspector->getPath($routeDocument->reveal())->willReturn('/cmf/sulu_io/routes/de/test');
        $this->documentInspector->getNode($routeDocument)->willReturn($routeNode);
        $this->documentInspector->getNode($newRouteDocument->reveal())->willReturn($newRouteNode->reveal());
        $this->documentInspector->getReferrers($routeDocument)->willReturn([$oldRouteDocument->reveal()]);
        $this->documentInspector->getPath($oldRouteDocument)->willReturn('/cmf/sulu_io/routes/de/old-test');

        $targetDocument->getWebspaceName()->willReturn('sulu_io');
        $targetDocument->getResourceSegment()->willReturn('/test1');
        $this->sessionManager->getRoutePath('sulu_io', 'de', null)->willReturn('/cmf/sulu_io/routes/de');

        $this->documentManager->create('route')->willReturn($newRouteDocument->reveal());
        $newRouteDocument->setTargetDocument($targetDocument->reveal());
        $this->documentManager->persist(
            $newRouteDocument,
            'de',
            ['path' => '/cmf/sulu_io/routes/de/test1', 'auto_create' => true]
        )->shouldBeCalled();

        $this->documentManager->publish($newRouteDocument, 'de')->shouldBeCalled();

        $routeDocument->setTargetDocument($newRouteDocument)->shouldBeCalled();

        $oldRouteDocument->setTargetDocument($newRouteDocument)->shouldBeCalled();
        $oldRouteDocument->setHistory(true)->shouldBeCalled();

        $this->documentManager->persist(
            $oldRouteDocument->reveal(),
            null,
            ['path' => '/cmf/sulu_io/routes/de/old-test']
        )->shouldBeCalled();
        $this->documentManager->publish($oldRouteDocument->reveal(), null)->shouldBeCalled();

        $routeNode->setProperty('sulu:history', true)->shouldBeCalled();
        $routeNode->getReferences('sulu:content')->willReturn([]);

        $persistEvent->getNode()->willReturn($routeNode);
        $persistEvent->getDocument()->willReturn($routeDocument);

        $this->routeSubscriber->handlePersist($persistEvent->reveal());
    }

    public function testHandlePersistWithWrongDocument(): void
    {
        $persistEvent = $this->prophesize(PersistEvent::class);
        $document = new \stdClass();

        $persistEvent->getDocument()->willReturn($document);
        $persistEvent->getNode()->shouldNotBeCalled();

        $this->routeSubscriber->handlePersist($persistEvent->reveal());
    }

    public function testHandlePersistWithWrongTargetDocument(): void
    {
        $persistEvent = $this->prophesize(PersistEvent::class);
        $document = $this->prophesize(RouteBehavior::class);
        $node = $this->prophesize(NodeInterface::class);
        $targetDocument = new \stdClass();

        $document->getTargetDocument()->willReturn($targetDocument);
        $document->isHistory()->willReturn(false);

        $persistEvent->getDocument()->willReturn($document);
        $persistEvent->getNode()->willReturn($node->reveal());

        $this->documentInspector->getNode($targetDocument)->shouldNotBeCalled();

        $this->routeSubscriber->handlePersist($persistEvent->reveal());
    }

    public function testHandlePersistWithHomeDocument(): void
    {
        $persistEvent = $this->prophesize(PersistEvent::class);
        $document = $this->prophesize(RouteBehavior::class);
        $node = $this->prophesize(NodeInterface::class);
        $targetDocument = $this->prophesize(HomeDocument::class);

        $document->getTargetDocument()->willReturn($targetDocument->reveal());
        $document->isHistory()->willReturn(false);

        $persistEvent->getDocument()->willReturn($document);
        $persistEvent->getNode()->willReturn($node->reveal());

        $this->documentInspector->getNode($targetDocument->reveal())->shouldNotBeCalled();

        $this->routeSubscriber->handlePersist($persistEvent->reveal());
    }

    public function testRemoveNoReferrer(): void
    {
        $removeEvent = $this->prophesize(RemoveEvent::class);
        $document = $this->prophesize(RouteBehavior::class);
        $removeEvent->getDocument()->willReturn($document->reveal());

        $this->documentInspector->getReferrers($document->reveal())->willReturn([]);

        $this->routeSubscriber->handleRemove($removeEvent->reveal());

        $this->documentManager->remove(Argument::any())->shouldNotBeCalled();
    }

    public function testRemove(): void
    {
        $removeEvent = $this->prophesize(RemoveEvent::class);
        $routeDocument1 = $this->prophesize(RouteBehavior::class);
        $removeEvent->getDocument()->willReturn($routeDocument1->reveal());

        $routeDocument2 = $this->prophesize(RouteBehavior::class);

        $this->documentInspector->getReferrers($routeDocument1->reveal())->willReturn([$routeDocument2->reveal()]);
        $this->documentInspector->getReferrers($routeDocument2->reveal())->willReturn([]);

        $this->routeSubscriber->handleRemove($removeEvent->reveal());

        $this->documentManager->remove($routeDocument2->reveal())->shouldBeCalled();
    }

    public function testHandleSetNodeOnPersist(): void
    {
        $options = ['path' => '/cmf/sulu_io/routes/de/products/machines'];

        $document = $this->prophesize(RouteBehavior::class);
        $parentNode = $this->prophesize(NodeInterface::class);
        $node = $this->prophesize(NodeInterface::class);

        $node->hasProperty(RouteSubscriber::NODE_HISTORY_FIELD)->willReturn(false);
        $parentNode->getNode(PathHelper::getNodeName($options['path']))->willReturn($node->reveal());

        $this->nodeManager->createPath(PathHelper::getParentPath($options['path']))->willReturn($parentNode->reveal());

        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getOptions()->willReturn($options);
        $event->setParentNode($parentNode->reveal())->shouldBeCalled();
        $event->setNode($node->reveal())->shouldBeCalled();

        $parentNode->hasNode(PathHelper::getNodeName($options['path']))->willReturn(true);

        $this->routeSubscriber->handleSetNodeOnPersist($event->reveal());
    }

    public function testHandleSetNodeOnPersistAlreadyUsed(): void
    {
        $options = ['path' => '/cmf/sulu_io/routes/de/products/machines'];

        $document = $this->prophesize(RouteBehavior::class);
        $parentNode = $this->prophesize(NodeInterface::class);
        $node = $this->prophesize(NodeInterface::class);

        $node->hasProperty(RouteSubscriber::NODE_HISTORY_FIELD)->willReturn(true);
        $parentNode->getNode(PathHelper::getNodeName($options['path']))->willReturn($node->reveal());

        $this->nodeManager->createPath(PathHelper::getParentPath($options['path']))->willReturn($parentNode->reveal());

        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getOptions()->willReturn($options);
        $event->setParentNode($parentNode->reveal())->shouldNotBeCalled();
        $event->setNode($node->reveal())->shouldNotBeCalled();

        $parentNode->hasNode(PathHelper::getNodeName($options['path']))->willReturn(true);

        $this->routeSubscriber->handleSetNodeOnPersist($event->reveal());
    }
}
