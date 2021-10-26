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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Removes a document from the index, as soon as it gets secured.
 */
class PermissionListener implements EventSubscriberInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    public function __construct(DocumentManagerInterface $documentManager, SearchManagerInterface $searchManager)
    {
        $this->documentManager = $documentManager;
        $this->searchManager = $searchManager;
    }

    public static function getSubscribedEvents(): array
    {
        return ['sulu_security.permission_update' => 'onPermissionUpdate'];
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
