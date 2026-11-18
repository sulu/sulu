<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Webspace\Infrastructure\JMS\Serializer;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Webspace;
use Sulu\Webspace\Infrastructure\Sulu\Admin\WebspaceSettingAdmin;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Adds the permissions of the settings to the webspaces which define a settings form, the tab of the settings is shown
 * by them.
 *
 * @internal no backwards compatibility promise is given for this class it could be removed or changed at any time
 */
final class WebspaceSettingSerializeEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AccessControlManagerInterface $accessControlManager,
        private ?TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * @return array<array{event: string, format: string, method: string, class: class-string}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
                'class' => Webspace::class,
            ],
        ];
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $webspace = $event->getObject();
        $visitor = $event->getVisitor();

        if (!$webspace instanceof Webspace
            || null === $webspace->getWebspaceSettingsForm()
            || !$visitor instanceof SerializationVisitorInterface
        ) {
            return;
        }

        $user = $this->tokenStorage?->getToken()?->getUser();

        $permissions = $this->accessControlManager->getUserPermissions(
            new SecurityCondition(WebspaceSettingAdmin::getSecurityContext($webspace->getKey())),
            $user instanceof UserInterface ? $user : null,
        );

        $permissions = $event->getContext()->getNavigator()->accept($permissions);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'settingsPermissions', $permissions),
            $permissions,
        );
    }
}
