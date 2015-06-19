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

use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\Content\Document\Behavior\NavigationContextBehavior;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Events;

class NavigationContextSubscriber implements EventSubscriberInterface
{
    const FIELD = 'navContexts';

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::METADATA_LOAD => 'handleMetadataLoad',
        ];
    }

    /**
     * @param MetadataLoadEvent $event
     */
    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (!$metadata->getReflectionClass()->isSubclassOf(NavigationContextBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping('navigationContexts', [
            'encoding' => 'system_localized',
            'property' => self::FIELD,
            'multiple' => true,
        ]);
    }
}
