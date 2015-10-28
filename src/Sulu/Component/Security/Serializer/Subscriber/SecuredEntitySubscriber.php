<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This subscriber adds the security information for the current user to the serialization representation of entites
 * implementing the SecuredEntityInterface.
 */
class SecuredEntitySubscriber implements EventSubscriberInterface
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
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerialize'],
        ];
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $object = $event->getObject();

        // FIXME This should be removed, once all entities are restructured not using the ApiWrapper, possible BC break
        if ($object instanceof ApiWrapper) {
            $object = $object->getEntity();
        }

        if (!$object instanceof SecuredEntityInterface) {
            return;
        }

        $event->getVisitor()->addData(
            '_permissions',
            $this->accessControlManager->getUserPermissions(
                new SecurityCondition($object->getSecurityContext(), null, get_class($object), $object->getId()),
                $this->tokenStorage->getToken()->getUser()
            )
        );
    }
}
