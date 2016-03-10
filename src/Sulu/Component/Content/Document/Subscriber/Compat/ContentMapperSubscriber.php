<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber\Compat;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Mapper\ContentEvents;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\Event\ContentNodeDeleteEvent;
use Sulu\Component\Content\Mapper\Event\ContentNodeEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\Util\SuluNodeHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Send the legacy content mapper NODE_PRE/POST_REMOVE events.
 *
 * @deprecated Here only for BC reasons
 */
class ContentMapperSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ContentNodeDeleteEvent[]
     */
    private $deleteEvents;

    /**
     * @var PersistEvent[]
     */
    private $persistEvents = [];

    public function __construct(
        EventDispatcherInterface $dispatcher,
        ContentMapperInterface $mapper,
        SuluNodeHelper $nodeHelper,
        StructureManagerInterface $structureManager
    ) {
        $this->eventDispatcher = $dispatcher;
        $this->nodeHelper = $nodeHelper;
        $this->contentMapper = $mapper;
        $this->structureManager = $structureManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::REMOVE => [
                ['handlePreRemove', 500],
                ['handlePostRemove', -100],
            ],
            Events::PERSIST => 'handlePersist',
            Events::FLUSH => 'handleFlush',
        ];
    }

    /**
     * Dispatches the deprecated pre remove event.
     *
     * @param RemoveEvent $event
     */
    public function handlePreRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $manager = $event->getManager();

        $event = $this->getDeleteEvent($manager->getInspector(), $document);
        $this->deleteEvents[spl_object_hash($document)] = $event;
        $this->eventDispatcher->dispatch(
            ContentEvents::NODE_PRE_DELETE,
            $event
        );
    }

    /**
     * Dispatches the deprected post remove event.
     *
     * @param RemoveEvent $event
     */
    public function handlePostRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $oid = spl_object_hash($document);
        $event = $this->deleteEvents[$oid];

        $this->eventDispatcher->dispatch(
            ContentEvents::NODE_POST_DELETE,
            $event
        );

        unset($this->deleteEvents[$oid]);
    }

    /**
     * Saves all persisted documents to dispatch the deprecated post save event later when flushed.
     *
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        if (!$this->supports($event->getDocument())) {
            return;
        }

        $this->persistEvents[] = $event;
    }

    /**
     * Dispatches the deprecated post save event for every persisted document.
     *
     * @param FlushEvent $event
     */
    public function handleFlush(FlushEvent $event)
    {
        $inspector = $event->getManager()->getInspector();
        foreach ($this->persistEvents as $persistEvent) {
            $document = $persistEvent->getDocument();
            $structure = $this->documentToStructure($inspector, $document);

            $event = new ContentNodeEvent($inspector->getNode($document), $structure);
            $this->eventDispatcher->dispatch(ContentEvents::NODE_POST_SAVE, $event);
        }

        $this->persistEvents = [];
    }

    private function supports($document)
    {
        return $document instanceof StructureBehavior;
    }

    private function getDeleteEvent(DocumentInspector $inspector, $document)
    {
        $webspace = $inspector->getWebspace($document);
        $event = new ContentNodeDeleteEvent(
            $this->contentMapper,
            $this->nodeHelper,
            $inspector->getNode($document),
            $webspace
        );

        return $event;
    }

    /**
     * Return a structure bridge corresponding to the given document.
     *
     * @param StructureBehavior $document
     *
     * @return StructureBridge
     *
     * @deprecated
     */
    private function documentToStructure(DocumentInspector $inspector, StructureBehavior $document)
    {
        if (null === $document) {
            return;
        }

        $structure = $inspector->getStructureMetadata($document);
        $documentAlias = $inspector->getMetadata($document)->getAlias();

        $structureBridge = $this->structureManager->wrapStructure($documentAlias, $structure);
        $structureBridge->setDocument($document);

        return $structureBridge;
    }
}
