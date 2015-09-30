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

use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\GenericSerializationVisitor;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SecuredEntitySubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SecuredEntitySubscriber
     */
    private $securedEntitySubscriber;

    /**
     * @var AccessControlManagerInterface
     */
    private $accessControlManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * @var UserInterface
     */
    /**
     * @var ObjectEvent
     */
    private $objectEvent;

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @var GenericSerializationVisitor
     */
    private $visitor;

    public function setUp()
    {
        $this->user = $this->prophesize(UserInterface::class);
        $this->token = $this->prophesize(TokenInterface::class);
        $this->token->getUser()->willReturn($this->user);

        $this->accessControlManager = $this->prophesize(AccessControlManagerInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->securedEntitySubscriber = new SecuredEntitySubscriber(
            $this->accessControlManager->reveal(),
            $this->tokenStorage->reveal()
        );

        $this->visitor = $this->prophesize(GenericSerializationVisitor::class);
        $this->objectEvent = $this->prophesize(ObjectEvent::class);
        $this->objectEvent->getVisitor()->willReturn($this->visitor);
    }

    public function testOnPostSerialize()
    {
        $entity = $this->prophesize(SecuredEntityInterface::class);
        $entity->getId()->willReturn(7);
        $entity->getSecurityContext()->willReturn('sulu.example');
        $this->objectEvent->getObject()->willReturn($entity);

        $securityCondition = new SecurityCondition('sulu.example', null, get_class($entity->reveal()), 7);

        $permission = ['_permissions' => ['permission' => 'value']];
        $this->accessControlManager->getUserPermissions($securityCondition, $this->user->reveal())->willReturn(
            $permission
        );

        $this->visitor->addData('_permissions', $permission)->shouldBeCalled();

        $this->securedEntitySubscriber->onPostSerialize($this->objectEvent->reveal());
    }

    public function testOnPostSerializeWithApiWrapper()
    {
        $apiWrapper = $this->prophesize(ApiWrapper::class);
        $entity = $this->prophesize(SecuredEntityInterface::class);
        $entity->getId()->willReturn(7);
        $entity->getSecurityContext()->willReturn('sulu.example');
        $apiWrapper->getEntity()->willReturn($entity);
        $this->objectEvent->getObject()->willReturn($apiWrapper);

        $securityCondition = new SecurityCondition('sulu.example', null, get_class($entity->reveal()), 7);

        $permission = ['_permissions' => ['permission' => 'value']];
        $this->accessControlManager->getUserPermissions($securityCondition, $this->user->reveal())->willReturn(
            $permission
        );

        $this->visitor->addData('_permissions', $permission)->shouldBeCalled();

        $this->securedEntitySubscriber->onPostSerialize($this->objectEvent->reveal());
    }
}
