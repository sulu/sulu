<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\SegmentBehavior;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SegmentSubscriber implements EventSubscriberInterface
{
    const FIELD = 'segment';

    public static function getSubscribedEvents()
    {
        return [
            Events::METADATA_LOAD => 'handleMetadataLoad',
        ];
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (!$metadata->getReflectionClass()->isSubclassOf(SegmentBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping('segment', [
            'encoding' => 'system_localized',
            'property' => self::FIELD,
            'multiple' => false,
        ]);
    }
}
