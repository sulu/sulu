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

class WorkflowStageSubscriber extends AbstractMappingSubscriber
{
    const FIELD = 'state';

    public function supports($document)
    {
        return $document instanceof WorkflowStageBehavior;
    }

    /**
     * @param HydrateEvent $event
     */
    public function doHydrate(HydrateEvent $event)
    {
        $value = $event->getNode()->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::FIELD, $event->getLocale()),
            null
        );
        $event->getDocument()->setWorkflowStage($value);
    }

    /**
     * @param PersistEvent $event
     */
    public function doPersist(PersistEvent $event)
    {
        $event->getNode()->setProperty(
            $this->encoder->localizedSystemName(self::FIELD, $event->getLocale()),
            $event->getDocument()->getWorkflowStage(),
            PropertyType::LONG
        );
    }
}

