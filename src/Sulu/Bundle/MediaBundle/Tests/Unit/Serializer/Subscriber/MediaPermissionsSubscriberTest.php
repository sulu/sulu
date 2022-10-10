<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Api\Media as MediaApiWrapper;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Serializer\Subscriber\MediaPermissionsSubscriber;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class MediaPermissionsSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var MediaPermissionsSubscriber
     */
    private $mediaPermissionsSubscriber;

    /**
     * @var ObjectProphecy<AccessControlManagerInterface>
     */
    private $accessControlManager;

    /**
     * @var ObjectProphecy<TokenStorageInterface>
     */
    private $tokenStorage;

    /**
     * @var ObjectProphecy<TokenInterface>
     */
    private $token;

    /**
     * @var ObjectProphecy<ObjectEvent>
     */
    private $objectEvent;

    /**
     * @var ObjectProphecy<UserInterface>
     */
    private $user;

    /**
     * @var ObjectProphecy<SerializationVisitorInterface>
     */
    private $visitor;

    public function setUp(): void
    {
        $this->user = $this->prophesize(UserInterface::class);
        $this->token = $this->prophesize(TokenInterface::class);
        $this->token->getUser()->willReturn($this->user->reveal());

        $this->accessControlManager = $this->prophesize(AccessControlManagerInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->mediaPermissionsSubscriber = new MediaPermissionsSubscriber(
            $this->accessControlManager->reveal(),
            $this->tokenStorage->reveal()
        );

        $this->visitor = $this->prophesize(SerializationVisitorInterface::class);
        $this->objectEvent = $this->prophesize(ObjectEvent::class);
        $this->objectEvent->getVisitor()->willReturn($this->visitor->reveal());
    }

    public function testOnPostSerialize(): void
    {
        $media = $this->prophesize(Media::class);
        $this->objectEvent->getObject()->willReturn($media->reveal());

        $collection = $this->prophesize(Collection::class);
        $collection->getId()->willReturn(7);
        $collection->getSecurityContext()->willReturn(MediaAdmin::SECURITY_CONTEXT);
        $media->getCollection()->willReturn($collection->reveal());

        $permissions = [3 => ['view' => true]];
        $this->accessControlManager->getPermissions(\get_class($collection->reveal()), 7)->willReturn($permissions);

        $userPermission = ['_permissions' => ['permission' => 'value']];
        $this->accessControlManager->getUserPermissionByArray(null, MediaAdmin::SECURITY_CONTEXT, $permissions, $this->user->reveal())
            ->willReturn($userPermission);

        $this->visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return '_permissions' === $metadata->name;
        }), $userPermission)->shouldBeCalled();

        $this->visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return '_hasPermissions' === $metadata->name;
        }), true)->shouldBeCalled();

        $this->mediaPermissionsSubscriber->onPostSerialize($this->objectEvent->reveal());
    }

    public function testOnPostSerializeWithApiWrapper(): void
    {
        $apiWrapper = $this->prophesize(MediaApiWrapper::class);
        $media = $this->prophesize(Media::class);
        $this->objectEvent->getObject()->willReturn($media->reveal());

        $collection = $this->prophesize(Collection::class);
        $collection->getId()->willReturn(7);
        $collection->getSecurityContext()->willReturn(MediaAdmin::SECURITY_CONTEXT);
        $media->getCollection()->willReturn($collection->reveal());

        $apiWrapper->getEntity()->willReturn($media);
        $this->objectEvent->getObject()->willReturn($apiWrapper);

        $permissions = [3 => ['view' => true]];
        $this->accessControlManager->getPermissions(\get_class($collection->reveal()), 7)->willReturn($permissions);

        $userPermission = ['_permissions' => ['permission' => 'value']];
        $this->accessControlManager->getUserPermissionByArray(null, MediaAdmin::SECURITY_CONTEXT, $permissions, $this->user->reveal())
            ->willReturn($userPermission);

        $this->visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return '_permissions' === $metadata->name;
        }), $userPermission)->shouldBeCalled();

        $this->visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return '_hasPermissions' === $metadata->name;
        }), true)->shouldBeCalled();

        $this->mediaPermissionsSubscriber->onPostSerialize($this->objectEvent->reveal());
    }
}
