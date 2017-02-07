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
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
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
            Events::REMOVE => ['deindexRemovedDocument', 600],
            Events::UNPUBLISH => ['deindexUnpublishedDocument', -1024],
            Events::REMOVE_DRAFT => ['indexDocumentAfterRemoveDraft', -1024],
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
     * Indexes a document after its draft have been removed.
     *
     * @param RemoveDraftEvent $event
     */
    public function indexDocumentAfterRemoveDraft(RemoveDraftEvent $event)
    {
        $document = $event->getDocument();

        if ($document instanceof WorkflowStageBehavior) {
            // Set the workflowstage to test for indexing, because the wrong index will be updated otherwise
            $document->setWorkflowStage(WorkflowStage::TEST);
        }

        $this->indexDocument($document);

        if ($document instanceof WorkflowStageBehavior) {
            // Reset the workflowstage to published, because after removing a draft the document will always be in
            // the published state
            $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        }
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
    public function deindexRemovedDocument(RemoveEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof StructureBehavior) {
            return;
        }

        if (!$document instanceof WorkflowStageBehavior) {
            $this->searchManager->deindex($document);
        } else {
            $workflowStage = $document->getWorkflowStage();

            foreach (WorkflowStage::$stages as $stage) {
                $document->setWorkflowStage($stage);
                $this->searchManager->deindex($document);
            }

            $document->setWorkflowStage($workflowStage);
        }
    }

    /**
     * Deindexes the document from the search index for the website.
     *
     * @param UnpublishEvent $event
     */
    public function deindexUnpublishedDocument(UnpublishEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof StructureBehavior) {
            return;
        }

        $this->searchManager->deindex($document);
    }
}
