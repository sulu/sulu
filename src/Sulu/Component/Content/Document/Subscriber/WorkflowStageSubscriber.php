<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class WorkflowStageSubscriber extends AbstractMappingSubscriber
{
    const WORKFLOW_STAGE_FIELD = 'state';
    const PUBLISHED_FIELD = 'published';

    public function supports($document)
    {
        return $document instanceof WorkflowStageBehavior;
    }

    /**
     * @param AbstractMappingEvent $event
     */
    protected function doHydrate(AbstractMappingEvent $event)
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
    protected function doPersist(PersistEvent $event)
    {
        $locale = $event->getLocale();

        if (!$locale) {
            return;
        }

        $document = $event->getDocument();
        $stage = $document->getWorkflowStage();
        $node = $event->getNode();
        $persistedStage = $this->getWorkflowStage($node, $locale);

        if ($stage == WorkflowStage::PUBLISHED && $stage !== $persistedStage) {
            $this->setPublishedDate($event->getAccessor(), $node, $locale, new \DateTime());
        }

        if ($stage == WorkflowStage::TEST && $stage !== $persistedStage) {
            $this->setPublishedDate($event->getAccessor(), $node, $locale, null);
        }

        $this->setWorkflowStage($node, $stage, $locale);
    }

    private function setWorkflowStage(NodeInterface $node, $stage, $locale)
    {
        $node->setProperty(
            $this->encoder->localizedSystemName(self::WORKFLOW_STAGE_FIELD, $locale),
            (integer) $stage,
            PropertyType::LONG
        );
    }

    private function setPublishedDate(DocumentAccessor $accessor, NodeInterface $node, $locale, \DateTime $date = null)
    {
        $node->setProperty(
            $this->encoder->localizedSystemName(self::PUBLISHED_FIELD, $locale),
            $date,
            PropertyType::DATE
        );
        $accessor->set('published', $date);
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
