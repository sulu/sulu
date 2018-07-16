<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping;

use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Maps the UUID.
 */
class UuidSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => 'handleUuid',
            Events::PERSIST => ['handleUuid', 0],
        ];
    }

    /**
     * @param AbstractMappingEvent $event
     *
     * @throws DocumentManagerException
     */
    public function handleUuid(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof UuidBehavior) {
            return;
        }

        $node = $event->getNode();

        $accessor = $event->getAccessor();
        $accessor->set(
            'uuid',
            $node->getIdentifier()
        );
    }
}
