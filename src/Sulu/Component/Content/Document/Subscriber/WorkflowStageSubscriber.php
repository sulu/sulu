<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use PHPCR\PropertyType;
use PHPCR\NodeInterface;
use Sulu\Component\Content\Document\WorkflowStage;

class WorkflowStageSubscriber extends AbstractMappingSubscriber
{
    const WORKFLOW_STAGE_FIELD = 'state';
    const PUBLISHED_FIELD = 'published';

    public function supports($document)
    {
        return $document instanceof WorkflowStageBehavior;
    }

    /**
     * @param HydrateEvent $event
     */
    public function doHydrate(HydrateEvent $event)
    {
        $locale = $event->getLocale();
        $node = $event->getNode();
        $document = $event->getDocument();

        $workflowStage = $this->getWorkflowStage($node, $locale);
        $document->setWorkflowStage($workflowStage);

        $publishedDate = $event->getNode()->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::PUBLISHED_FIELD, $event->getLocale()),
            null
        );
        $event->getAccessor()->set(
            'published',
            $publishedDate
        );
    }

    /**
     * @param PersistEvent $event
     */
    public function doPersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        $stage = $document->getWorkflowStage();
        $node = $event->getNode();
        $locale = $event->getLocale();
        $persistedStage = $this->getWorkflowStage($node, $locale);

        if ($stage == WorkflowStage::PUBLISHED && $stage !== $persistedStage) {
            $this->setPublishedDate($node, $locale);
        }

        $this->setWorkflowStage($node, $stage, $locale);
    }

    private function setWorkflowStage(NodeInterface $node, $stage, $locale)
    {
        $node->setProperty(
            $this->encoder->localizedSystemName(self::WORKFLOW_STAGE_FIELD, $locale),
            $stage,
            PropertyType::LONG
        );

    }

    private function setPublishedDate(NodeInterface $node, $locale)
    {
        $node->setProperty(
            $this->encoder->localizedSystemName(self::PUBLISHED_FIELD, $locale),
            new \DateTime(),
            PropertyType::DATE
        );
    }

    private function getWorkflowStage(NodeInterface $node, $locale)
    {
        $value = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::WORKFLOW_STAGE_FIELD, $locale),
            null
        );

        return $value;
    }
}

