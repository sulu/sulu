<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Search\EventListener;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Event\PermissionUpdateEvent;
use Sulu\Component\Security\Event\SecurityEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Removes a document from the index, as soon as it gets secured.
 */
class PermissionListener implements EventSubscriberInterface
{
    public function __construct(
        private DocumentManagerInterface $documentManager,
        private SearchManagerInterface $searchManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [SecurityEvents::PERMISSION_UPDATE => 'onPermissionUpdate'];
    }

    public function onPermissionUpdate(PermissionUpdateEvent $permissionUpdateEvent)
    {
        if (SecurityBehavior::class !== $permissionUpdateEvent->getType()) {
            return;
        }

        $document = $this->documentManager->find($permissionUpdateEvent->getIdentifier());
        $this->searchManager->deindex($document);
    }
}
