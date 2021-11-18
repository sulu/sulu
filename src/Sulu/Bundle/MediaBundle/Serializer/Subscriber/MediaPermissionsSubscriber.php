<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MediaPermissionsSubscriber implements EventSubscriberInterface
{
    /**
     * @var AccessControlManagerInterface
     */
    private $accessControlManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        AccessControlManagerInterface $accessControlManagerInterface,
        TokenStorageInterface $tokenStorage
    ) {
        $this->accessControlManager = $accessControlManagerInterface;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerialize'],
        ];
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $object = $event->getObject();
        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();

        // FIXME This should be removed, once all entities are restructured not using the ApiWrapper, possible BC break
        if ($object instanceof ApiWrapper) {
            $object = $object->getEntity();
        }

        if (!$object instanceof Media) {
            return;
        }

        $collection = $object->getCollection();

        $allPermissions = $this->accessControlManager->getPermissions(
            \get_class($collection),
            (string) $collection->getId()
        );

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }

        $permissions = $this->accessControlManager->getUserPermissionByArray(
            null,
            $collection->getSecurityContext(),
            $allPermissions,
            $user
        );

        $visitor->visitProperty(
            new StaticPropertyMetadata('', '_permissions', $permissions),
            $permissions
        );

        $hasPermissions = !empty($allPermissions);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', '_hasPermissions', $hasPermissions),
            $hasPermissions
        );
    }
}
