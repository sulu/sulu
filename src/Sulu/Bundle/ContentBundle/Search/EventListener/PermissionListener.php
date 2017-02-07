<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\EventListener;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Event\PermissionUpdateEvent;

/**
 * Removes a document from the index, as soon as it gets secured.
 */
class PermissionListener
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

    public function onPermissionUpdate(PermissionUpdateEvent $permissionUpdateEvent)
    {
        if ($permissionUpdateEvent->getType() !== SecurityBehavior::class) {
            return;
        }

        $document = $this->documentManager->find($permissionUpdateEvent->getIdentifier());
        $this->searchManager->deindex($document);
    }
}
