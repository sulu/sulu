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

use Sulu\Component\Content\Document\Behavior\AuthorBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles authors and authored.
 */
class AuthorSubscriber implements EventSubscriberInterface
{
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
     * Adds the authors and authored to the metadata for persisting.
     *
     * @param MetadataLoadEvent $event
     */
    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (!$metadata->getReflectionClass()->isSubclassOf(AuthorBehavior::class)) {
            return;
        }

        $encoding = 'system';
        if ($metadata->getReflectionClass()->isSubclassOf(LocalizedAuthorBehavior::class)) {
            $encoding = 'system_localized';
        }

        $metadata->addFieldMapping('authors', ['encoding' => $encoding, 'property' => 'authors']);
        $metadata->addFieldMapping('authored', ['encoding' => $encoding, 'property' => 'authored']);
    }
}
