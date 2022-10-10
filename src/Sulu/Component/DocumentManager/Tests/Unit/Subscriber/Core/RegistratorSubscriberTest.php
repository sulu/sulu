<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit\Subscriber\Core;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Subscriber\Core\RegistratorSubscriber;

class RegistratorSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentRegistry>
     */
    private $registry;

    /**
     * @var RegistratorSubscriber
     */
    private $subscriber;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var \stdClass
     */
    private $document;

    /**
     * @var ObjectProphecy<HydrateEvent>
     */
    private $hydrateEvent;

    /**
     * @var ObjectProphecy<PersistEvent>
     */
    private $persistEvent;

    /**
     * @var ObjectProphecy<RemoveEvent>
     */
    private $removeEvent;

    public function setUp(): void
    {
        $this->registry = $this->prophesize(DocumentRegistry::class);
        $this->subscriber = new RegistratorSubscriber(
            $this->registry->reveal()
        );

        $this->node = $this->prophesize(NodeInterface::class);
        $this->document = new \stdClass();
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->removeEvent = $this->prophesize(RemoveEvent::class);
    }

    /**
     * It should set the document on hydrate if the document for the node to
     * be hydrated is already in the registry.
     */
    public function testDocumentFromRegistry(): void
    {
        $this->hydrateEvent->hasDocument()->willReturn(false);
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getLocale()->willReturn('fr');
        $this->hydrateEvent->getOptions()->willReturn([]);
        $this->registry->hasNode($this->node->reveal(), 'fr')->willReturn(true);
        $this->registry->getDocumentForNode($this->node->reveal(), 'fr')->willReturn($this->document);
        $this->hydrateEvent->setDocument($this->document)->shouldBeCalled();

        $this->subscriber->handleDocumentFromRegistry($this->hydrateEvent->reveal());
    }

    /**
     * It should halt propagation if the document is already in the registry and the "rehydrate" option is false.
     */
    public function testDocumentFromRegistryNoRehydration(): void
    {
        $this->hydrateEvent->hasDocument()->willReturn(false);
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getLocale()->willReturn('fr');
        $this->hydrateEvent->getOptions()->willReturn(
            [
                'rehydrate' => false,
            ]
        );
        $this->hydrateEvent->stopPropagation()->shouldBeCalled();
        $this->registry->hasNode($this->node->reveal(), 'fr')->willReturn(true);
        $this->registry->getDocumentForNode($this->node->reveal(), 'fr')->willReturn($this->document);
        $this->hydrateEvent->setDocument($this->document)->shouldBeCalled();
        $this->subscriber->handleDocumentFromRegistry($this->hydrateEvent->reveal());
    }

    /**
     * It should set the default locale.
     */
    public function testDefaultLocale(): void
    {
        $this->hydrateEvent->getLocale()->willReturn(null);
        $this->registry->getDefaultLocale()->willReturn('de');
        $this->hydrateEvent->setLocale('de')->shouldBeCalled();

        $this->subscriber->handleDefaultLocale($this->hydrateEvent->reveal());
    }

    /**
     * It should stop propagation if the document is already loaded in the requested locale.
     */
    public function testStopPropagation(): void
    {
        $locale = 'de';
        $originalLocale = 'de';

        $this->hydrateEvent->hasDocument()->willReturn(true);
        $this->hydrateEvent->getLocale()->willReturn($locale);
        $this->hydrateEvent->getOptions()->willReturn([]);
        $this->hydrateEvent->getDocument()->willReturn($this->document);
        $this->registry->isHydrated($this->document)->willReturn(true);
        $this->registry->getOriginalLocaleForDocument($this->document)->willReturn($originalLocale);
        $this->hydrateEvent->stopPropagation()->shouldBeCalled();

        $this->subscriber->handleStopPropagationAndResetLocale($this->hydrateEvent->reveal());
    }

    /**
     * It should not stop propagation if the document is loaded with rehydrate option.
     */
    public function testStopPropagationRehydrate(): void
    {
        $locale = 'de';
        $originalLocale = 'de';

        $this->hydrateEvent->hasDocument()->willReturn(true);
        $this->hydrateEvent->getLocale()->willReturn($locale);
        $this->hydrateEvent->getOptions()->willReturn(['rehydrate' => true]);
        $this->hydrateEvent->getDocument()->willReturn($this->document);
        $this->registry->isHydrated($this->document)->willReturn(true);
        $this->registry->getOriginalLocaleForDocument($this->document)->willReturn($originalLocale);
        $this->hydrateEvent->stopPropagation()->shouldNotBeCalled();

        $this->subscriber->handleStopPropagationAndResetLocale($this->hydrateEvent->reveal());
    }

    /**
     * It should set the node to the event on persist if the node for the document
     * being persisted is already in the registry.
     */
    public function testPersistNodeFromRegistry(): void
    {
        $this->persistEvent->hasNode()->willReturn(false);
        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->registry->hasDocument($this->document)->willReturn(true);
        $this->registry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->persistEvent->setNode($this->node->reveal())->shouldBeCalled();
        $this->subscriber->handleNodeFromRegistry($this->persistEvent->reveal());
    }

    /**
     * The node should be available from the event.
     */
    public function testReorderNodeFomRegistry(): void
    {
        $reorderEvent = $this->prophesize(ReorderEvent::class);
        $reorderEvent->hasNode()->willReturn(false);
        $reorderEvent->getDocument()->willReturn($this->document);
        $this->registry->hasDocument($this->document)->willReturn(true);
        $this->registry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $reorderEvent->setNode($this->node->reveal())->shouldBeCalled();
        $this->subscriber->handleNodeFromRegistry($reorderEvent->reveal());
    }

    /**
     * It should return early if the document has already been set.
     */
    public function testDocumentFromRegistryAlreadySet(): void
    {
        $this->hydrateEvent->hasDocument()->willReturn(true)->shouldBeCalled();
        $this->subscriber->handleDocumentFromRegistry($this->hydrateEvent->reveal());
    }

    /**
     * Is should return early if the node is not managed.
     */
    public function testDocumentFromRegistryNoNode(): void
    {
        $this->hydrateEvent->hasDocument()->willReturn(true)->shouldBeCalled();
        $this->registry->hasNode($this->node->reveal(), 'fr')->willReturn(false);
        $this->subscriber->handleDocumentFromRegistry($this->hydrateEvent->reveal());
    }

    /**
     * It should register documents on the HYDRATE event.
     */
    public function testHandleRegisterHydrate(): void
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document);
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getLocale()->willReturn('fr');
        $this->registry->hasNode($this->node->reveal(), 'fr')->willReturn(false);
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr')->shouldBeCalled();

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should not register documents on the HYDRATE event when there is already a document.
     */
    public function testHandleRegisterHydrateAlreadyExisting(): void
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document);
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getLocale()->willReturn('fr');

        $this->registry->hasNode($this->node->reveal(), 'fr')->willReturn(true);
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr')->shouldNotBeCalled();

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should register documents on the PERSIST event.
     */
    public function testHandleRegisterPersist(): void
    {
        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->getLocale()->willReturn('fr');
        $this->registry->hasNode($this->node->reveal(), 'fr')->willReturn(false);
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr')->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should not register on PERSIST when there is already a document.
     */
    public function testHandleRegisterPersistAlreadyExists(): void
    {
        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->getLocale()->willReturn('fr');

        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr')->shouldNotBeCalled();
        $this->registry->hasNode($this->node->reveal(), 'fr')->willReturn(true);

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should deregister the document on the REMOVE event.
     */
    public function testHandleRemove(): void
    {
        $this->removeEvent->getDocument()->willReturn($this->document);
        $this->registry->deregisterDocument($this->document)->shouldBeCalled();
        $this->subscriber->handleRemove($this->removeEvent->reveal());
    }
}
