<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Bundle\ContentBundle\Admin\ContentAdmin;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Appends additional data to content-serialization.
 */
class SecuritySubscriber implements EventSubscriberInterface
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
        AccessControlManagerInterface $accessControlManager,
        TokenStorageInterface $tokenStorage = null
    ) {
        $this->accessControlManager = $accessControlManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    /**
     * Adds the permissions for the current user to the serialization.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $document = $event->getObject();

        if (!($document instanceof SecurityBehavior
            && $document instanceof LocaleBehavior
            && $document instanceof WebspaceBehavior
            && $this->tokenStorage !== null
            && $this->tokenStorage->getToken() !== null
            && $this->tokenStorage->getToken()->getUser() instanceof UserInterface)
        ) {
            return;
        }

        /** @var JsonSerializationVisitor $visitor */
        $visitor = $event->getVisitor();

        $visitor->addData(
            '_permissions',
            $this->accessControlManager->getUserPermissionByArray(
                $document->getLocale(),
                ContentAdmin::SECURITY_CONTEXT_PREFIX . $document->getWebspaceName(),
                $document->getPermissions(),
                $this->tokenStorage->getToken()->getUser()
            )
        );
    }
}
