<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Core;

use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Event\ClearEvent;

/**
 * Responsible for registering and deregistering documents and PHPCR nodes
 * with the Document Registry
 */
class RegistratorSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @param DocumentRegistry $documentRegistry
     */
    public function __construct(
        DocumentRegistry $documentRegistry
    )
    {
        $this->documentRegistry = $documentRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::HYDRATE => array(
                array('handleDocumentfromRegistry', 510),
                array('handleHydrate', 490),
            ),
            Events::PERSIST => array('handlePersist', 450),
            Events::REMOVE => array('handleRemove', 490),
            Events::CLEAR => array('handleClear', 500),
        );
    }

    /**
     * If the document for the node to be hydrated is already in the registry
     *
     * @param HydrateEvent
     */
    public function handleDocumentFromRegistry(HydrateEvent $event)
    {
        if ($event->hasDocument()) {
            return;
        }

        $node = $event->getNode();

        if (!$this->documentRegistry->hasNode($node)) {
            return;
        }

        $document = $this->documentRegistry->getDocumentForNode($node);
        $event->setDocument($document);
    }

    /**
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $this->handleRegister($event);
    }

    /**
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $this->handleRegister($event);
    }

    /**
     * @param RemoveEvent
     */
    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();
        $this->documentRegistry->deregisterDocument($document);
    }

    public function handleClear(ClearEvent $event)
    {
        $this->documentRegistry->clear();
    }

    private function handleRegister(Event $event)
    {
        $document = $event->getDocument();
        $node = $event->getNode();
        $this->documentRegistry->registerDocument($document, $node, $event->getLocale());
    }
}
