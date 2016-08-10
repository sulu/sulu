<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WorkflowStageSubscriber implements EventSubscriberInterface
{
    const WORKFLOW_STAGE_FIELD = 'state';
    const PUBLISHED_FIELD = 'published';

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var SessionInterface
     */
    private $defaultSession;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    public function __construct(
        PropertyEncoder $propertyEncoder,
        DocumentInspector $documentInspector,
        SessionInterface $defaultSession,
        SessionInterface $liveSession
    ) {
        $this->propertyEncoder = $propertyEncoder;
        $this->documentInspector = $documentInspector;
        $this->defaultSession = $defaultSession;
        $this->liveSession = $liveSession;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => 'setWorkflowStageOnDocument',
            Events::PERSIST => 'setWorkflowStageToTest',
            Events::PUBLISH => 'setWorkflowStageToPublished',
            Events::UNPUBLISH => 'setWorkflowStageToTestAndResetPublishedDate',
            Events::COPY => 'setWorkflowStageToTestForCopy',
        ];
    }

    /**
     * Sets the workflow properties from the node on the document.
     *
     * @param HydrateEvent $event
     *
     * @throws \Sulu\Component\DocumentManager\Exception\DocumentManagerException
     */
    public function setWorkflowStageOnDocument(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($event)) {
            return;
        }

        $node = $event->getNode();
        $locale = $event->getLocale();

        $document->setWorkflowStage(
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->localizedSystemName(self::WORKFLOW_STAGE_FIELD, $locale),
                WorkflowStage::TEST
            )
        );

        $event->getAccessor()->set(
            self::PUBLISHED_FIELD,
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->localizedSystemName(self::PUBLISHED_FIELD, $locale),
                null
            )
        );
    }

    /**
     * Sets the workflow stage for the passed document to test.
     *
     * @param PersistEvent $event
     */
    public function setWorkflowStageToTest(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($event)) {
            return;
        }

        $this->setWorkflowStage($document, $event->getAccessor(), WorkflowStage::TEST, $event->getLocale(), false);
    }

    /**
     * Sets the workflow stage for the passed document to published.
     *
     * @param PublishEvent $event
     */
    public function setWorkflowStageToPublished(PublishEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($event)) {
            return;
        }

        $this->setWorkflowStage($document, $event->getAccessor(), WorkflowStage::PUBLISHED, $event->getLocale(), true);
    }

    /**
     * Resets the workflowstage to test and the published date to null.
     *
     * @param UnpublishEvent $event
     */
    public function setWorkflowStageToTestAndResetPublishedDate(UnpublishEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($event)) {
            return;
        }

        $locale = $event->getLocale();

        $node = $this->defaultSession->getNode($this->documentInspector->getPath($document));
        $node->setProperty(
            $this->propertyEncoder->localizedSystemName(self::WORKFLOW_STAGE_FIELD, $locale),
            WorkflowStage::TEST
        );
        $node->setProperty($this->propertyEncoder->localizedSystemName(self::PUBLISHED_FIELD, $locale), null);
    }

    /**
     * Sets the workflowstage for the copied node and all its children to test. This is done because newly copied pages
     * shouldn't be automatically published on the website.
     *
     * @param CopyEvent $event
     */
    public function setWorkflowStageToTestForCopy(CopyEvent $event)
    {
        $this->setNodeWorkflowStageToTestForCopy($event->getCopiedNode());
    }

    /**
     * Sets the workflowstage and the published date for the given node and all of its children to test resp. null. This
     * is done for every language in which the given properties exist.
     *
     * @param NodeInterface $node
     */
    private function setNodeWorkflowStageToTestForCopy(NodeInterface $node)
    {
        $workflowStageNameFilter = $this->propertyEncoder->localizedSystemName(self::WORKFLOW_STAGE_FIELD, '*');
        foreach ($node->getProperties($workflowStageNameFilter) as $property) {
            /** @var PropertyInterface $property */
            $property->setValue(WorkflowStage::TEST);
        }

        $publishedNameFilter = $this->propertyEncoder->localizedSystemName(self::PUBLISHED_FIELD, '*');
        foreach ($node->getProperties($publishedNameFilter) as $property) {
            /** @var PropertyInterface $property */
            $property->setValue(null);
        }

        foreach ($node->getNodes() as $node) {
            /** @var NodeInterface $node */
            $this->setNodeWorkflowStageToTestForCopy($node);
        }
    }

    /**
     * Determines if the given document is supported by this subscriber.
     *
     * @param HydrateEvent|PublishEvent|PersistEvent $event
     *
     * @return bool
     */
    private function supports($event)
    {
        return $event->getLocale() && $event->getDocument() instanceof WorkflowStageBehavior;
    }

    /**
     * Sets the workflow properties on the given document.
     *
     * @param WorkflowStageBehavior $document
     * @param DocumentAccessor $accessor
     * @param string $workflowStage
     * @param string $locale
     * @param string $live
     *
     * @throws \Sulu\Component\DocumentManager\Exception\DocumentManagerException
     */
    private function setWorkflowStage(
        WorkflowStageBehavior $document,
        DocumentAccessor $accessor,
        $workflowStage,
        $locale,
        $live
    ) {
        $path = $this->documentInspector->getPath($document);
        $document->setWorkflowStage($workflowStage);

        $updatePublished = !$document->getPublished() && $workflowStage === WorkflowStage::PUBLISHED;
        if ($updatePublished) {
            $accessor->set(self::PUBLISHED_FIELD, new \DateTime());
        }

        $defaultNode = $this->defaultSession->getNode($path);
        $this->setWorkflowStageOnNode($defaultNode, $locale, $workflowStage, $updatePublished);

        if ($live) {
            $liveNode = $this->liveSession->getNode($path);
            $this->setWorkflowStageOnNode($liveNode, $locale, $workflowStage, $updatePublished);
        }
    }

    /**
     * Sets the workflow stage properties on the given node.
     *
     * @param NodeInterface $node
     * @param string $locale
     * @param int $workflowStage
     * @param bool $updatePublished
     */
    private function setWorkflowStageOnNode(NodeInterface $node, $locale, $workflowStage, $updatePublished)
    {
        $node->setProperty(
            $this->propertyEncoder->localizedSystemName(self::WORKFLOW_STAGE_FIELD, $locale),
            $workflowStage
        );

        if ($updatePublished) {
            $node->setProperty(
                $this->propertyEncoder->localizedSystemName(self::PUBLISHED_FIELD, $locale),
                new \DateTime()
            );
        }
    }
}
