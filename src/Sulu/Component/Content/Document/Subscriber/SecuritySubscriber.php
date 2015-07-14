<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles the security information on each node
 */
class SecuritySubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::PERSIST => 'handlePersist',
        );
    }

    /**
     * Shows if the given document is supported by this subscriber
     * @param $document
     * @return bool
     */
    public function supports($document) {
        return $document instanceof SecurityBehavior;
    }

    /**
     * Adds the security information to the node
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        /** @var SecurityBehavior $document */
        $document = $event->getDocument();

        if (!$this->supports($document) || !$document->getPermissions()) {
            return;
        }

        $node = $event->getNode();

        foreach ($document->getPermissions() as $roleName => $permission) {
            $roleName = str_replace('_', '-', strtolower(substr($roleName, 5))); // TODO extract this functionality
            $node->setProperty('sec:' . $roleName, $this->getAllowedPermissions($permission)); // TODO use PropertyEncoder, once it is refactored
        }
    }

    /**
     * Extracts the keys of the allowed permissions into an own array
     * @param $permissions
     * @return array
     */
    private function getAllowedPermissions($permissions) {
        $allowedPermissions = array();
        foreach ($permissions as $permission => $allowed) {
            if ($allowed) {
                $allowedPermissions[] = $permission;
            }
        }

        return $allowedPermissions;
    }
}
