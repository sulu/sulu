<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\EventSubscriber;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listen to sulu node save event and index the document.
 */
class StructureSubscriber implements EventSubscriberInterface
{
    /**
     * @var SearchManagerInterface
     */
    protected $searchManager;

    /**
     * @param SearchManagerInterface $searchManager
     */
    public function __construct(SearchManagerInterface $searchManager)
    {
        $this->searchManager = $searchManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => ['indexPersistedDocument', -10],
            Events::PUBLISH => ['indexPublishedDocument', -256],
            Events::REMOVE => ['handlePreRemove', 600],
        ];
    }

    /**
     * Indexes a persisted document.
     *
     * @param PersistEvent $event
     */
    public function indexPersistedDocument(PersistEvent $event)
    {
        $this->indexDocument($event->getDocument());
    }

    /**
     * Indexes a published document.
     *
     * @param PublishEvent $event
     */
    public function indexPublishedDocument(PublishEvent $event)
    {
        $this->indexDocument($event->getDocument());
    }

    /**
     * Index document in search implementation depending
     * on the publish state.
     *
     * @param object $document
     */
    private function indexDocument($document)
    {
        if (!$document instanceof StructureBehavior) {
            return;
        }

        if ($document instanceof SecurityBehavior && !empty($document->getPermissions())) {
            return;
        }

        $this->searchManager->index($document);
    }

    /**
     * Schedules a document to be deindexed.
     *
     * @param RemoveEvent $event
     */
    public function handlePreRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof StructureBehavior) {
            return;
        }

        $this->searchManager->deindex($document);
    }
}
