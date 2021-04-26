<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use Sulu\Component\Security\Authorization\AccessControl\DoctrineAccessControlProvider;
use Sulu\Component\Security\Authorization\AccessControl\PhpcrAccessControlProvider;
use Sulu\Component\Security\Event\PermissionUpdateEvent;
use Sulu\Component\Security\Event\SecurityEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PhpcrSecuritySubscriber implements EventSubscriberInterface
{
    /**
     * @var PhpcrAccessControlProvider
     */
    private $phpcrAccessControlProvider;

    /**
     * @var DoctrineAccessControlProvider
     */
    private $doctrineAccessControlProvider;

    public function __construct(
        PhpcrAccessControlProvider $phpcrAccessControlProvider,
        DoctrineAccessControlProvider $doctrineAccessControlProvider
    ) {
        $this->phpcrAccessControlProvider = $phpcrAccessControlProvider;
        $this->doctrineAccessControlProvider = $doctrineAccessControlProvider;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::PERMISSION_UPDATE => 'handlePermissionUpdate',
        ];
    }

    public function handlePermissionUpdate(PermissionUpdateEvent $event): void
    {
        $type = $event->getType();
        $identifier = $event->getIdentifier();
        $permissions = $event->getPermissions();

        if (!$this->phpcrAccessControlProvider->supports($type)) {
            return;
        }

        $this->doctrineAccessControlProvider->setPermissions($type, $identifier, $permissions);
    }
}
