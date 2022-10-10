<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Comonent\DocumentManager\tests\Unit\Subscriber\Phpcr;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\RefreshEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\RefreshSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RefreshSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    private $eventDispatcher;

    /**
     * @var ObjectProphecy<DocumentRegistry>
     */
    private $documentRegistry;

    /**
     * @var RefreshSubscriber
     */
    private $refreshSubscriber;

    public function setUp(): void
    {
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);

        $this->refreshSubscriber = new RefreshSubscriber(
            $this->eventDispatcher->reveal(),
            $this->documentRegistry->reveal()
        );
    }

    public function testRefreshDocument(): void
    {
        $document = new \stdClass();
        $node = $this->prophesize(NodeInterface::class);

        $refreshEvent = $this->prophesize(RefreshEvent::class);
        $refreshEvent->getDocument()->willReturn($document);
        $this->documentRegistry->getNodeForDocument($document)->willReturn($node->reveal());
        $node->revert()->shouldBeCalled();
        $this->documentRegistry->getLocaleForDocument($document)->willReturn('fr');

        $this->eventDispatcher->dispatch(Argument::that(function(HydrateEvent $event) use ($node) {
            return $node->reveal() === $event->getNode()
                && 'fr' === $event->getLocale();
        }), Events::HYDRATE)
            ->willReturnArgument(0);

        $this->refreshSubscriber->refreshDocument($refreshEvent->reveal());
    }

    public function testRefreshDocumentForDeleteDraft(): void
    {
        $document = new \stdClass();
        $node = $this->prophesize(NodeInterface::class);

        $removeDraftEvent = $this->prophesize(RemoveDraftEvent::class);
        $removeDraftEvent->getNode()->willReturn($node->reveal());
        $removeDraftEvent->getLocale()->willReturn('de');
        $removeDraftEvent->getDocument()->willReturn($document);

        $hydrateEvent = new HydrateEvent($node->reveal(), 'de', ['rehydrate' => true]);
        $hydrateEvent->setDocument($document);

        $this->eventDispatcher->dispatch($hydrateEvent, Events::HYDRATE)->shouldBeCalled();

        $this->refreshSubscriber->refreshDocumentForDeleteDraft($removeDraftEvent->reveal());
    }
}
