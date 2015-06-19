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
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;

class WorkflowStageSubscriber extends AbstractMappingSubscriber
{
    const WORKFLOW_STAGE_FIELD = 'state';
    const PUBLISHED_FIELD = 'published';

    public function supports($document)
    {
        return $document instanceof WorkflowStageBehavior;
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::METADATA_LOAD => 'handleMetadataLoad',
        );
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectedClass()->isSubclassOf(WorkflowStageBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping('workflowStage', array(
            'encoding' => 'system_localized',
            'property' => self::WORKFLOW_STAGE_FIELD,
        ));
        $metadata->addFieldMapping('published', array(
            'encoding' => 'system_localized',
            'property' => self::PUBLISHED_FIELD,
        ));
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
            $event->getAccessor()->set(self::PUBLISHED_FIELD, new \DateTime());
        }

        if ($stage == WorkflowStage::TEST && $stage !== $persistedStage) {
            $event->getAccessor()->set(self::PUBLISHED_FIELD, null);
        }

        $event->getAccessor()->set(self::WORKFLOW_STAGE_FIELD, $stage);
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

