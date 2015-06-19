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

use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\Content\Document\Behavior\RouteBehavior;
use Sulu\Component\DocumentManager\Events;

/**
 * Behavior for route (sulu:path) documents
 */
class RouteSubscriber extends AbstractMappingSubscriber
{
    const DOCUMENT_TARGET_FIELD = 'content';

    public static function getSubscribedEvents()
    {
        return array(
            Events::METADATA_LOAD => 'handleMetadataLoad',
        );
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectedClass()->isSubclassOf(RouteBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping(self::DOCUMENT_TARGET_FIELD, array(
            'encoding' => 'system_localized',
            'property' => self::DOCUMENT_TARGET_FIELD,
        ));
    }
}
