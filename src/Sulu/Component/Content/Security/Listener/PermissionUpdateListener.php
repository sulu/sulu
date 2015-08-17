<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Security\Listener;

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Event\PermissionUpdateEvent;

/**
 * This class listens on permission updates, and set the permission on the document described in the event.
 *
 * @deprecated
 */
class PermissionUpdateListener
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    public function __construct(DocumentManagerInterface $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function onPermissionUpdate(PermissionUpdateEvent $event)
    {
        if ($event->getType() !== WebspaceBehavior::class) {
            return;
        }

        $allowedPermissions = [];
        foreach ($event->getPermissions() as $roleName => $permissions) {
            $allowedPermissions[$roleName] = $this->getAllowedPermissions($permissions);
        }

        /** @var BasePageDocument $document */
        $document = $this->documentManager->find($event->getIdentifier());
        $document->setPermissions($allowedPermissions);

        $this->documentManager->persist($document);
        $this->documentManager->flush();
    }

    /**
     * Extracts the keys of the allowed permissions into an own array.
     *
     * @param $permissions
     *
     * @return array
     */
    private function getAllowedPermissions($permissions)
    {
        $allowedPermissions = [];
        foreach ($permissions as $permission => $allowed) {
            if ($allowed) {
                $allowedPermissions[] = $permission;
            }
        }

        return $allowedPermissions;
    }
}
