<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Document\Subscriber;

use Sulu\Component\CustomUrl\Document\CustomUrlBehavior;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles document-manager events for custom-urls.
 */
class CustomUrlSubscriber implements EventSubscriberInterface
{
    const TITLE_FIELD_NAME = 'title';

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::METADATA_LOAD => 'handleMetadataLoad',
        ];
    }

    /**
     * Load the class data, mapping the custom-url fields.
     *
     * @param MetadataLoadEvent $event
     */
    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        if (!$event->getMetadata()->getReflectionClass()->isSubclassOf(CustomUrlBehavior::class)) {
            return;
        }

        $metadata = $event->getMetadata();
        $metadata->addFieldMapping(
            self::TITLE_FIELD_NAME,
            [
                'property' => self::TITLE_FIELD_NAME,
            ]
        );
    }
}
