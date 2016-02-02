<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Tests\Unit\Authorization;

use Prophecy\Argument;
use Sulu\Component\Security\Authorization\SecurityChecker;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SecurityCheckerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function setUp()
    {
        parent::setUp();

        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->tokenStorage->getToken()->willReturn(true); // stands for a valid token

        $this->authorizationChecker = $this->prophesize(AuthorizationCheckerInterface::class);

        $this->securityChecker = new SecurityChecker(
            $this->tokenStorage->reveal(),
            $this->authorizationChecker->reveal()
        );
    }

    public function testIsGrantedContext()
    {
        $this->authorizationChecker->isGranted(
            ['view'],
            Argument::which('getSecurityContext', 'sulu.media.collection')
        )->willReturn(true);

        $granted = $this->securityChecker->checkPermission('sulu.media.collection', 'view');

        $this->assertTrue($granted);
    }

    public function testIsGrantedObject()
    {
        $object = new \stdClass();

        $this->authorizationChecker->isGranted(
            ['view'],
            $object
        )->willReturn(true);

        $granted = $this->securityChecker->checkPermission($object, 'view');

        $this->assertTrue($granted);
    }

    public function testIsGrantedFalsyValue()
    {
        $object = null;

        // should always return true for falsy values
        $this->assertTrue($this->securityChecker->checkPermission($object, 'view'));
    }

    public function testIsGrantedFail()
    {
        $this->setExpectedException(
            'Symfony\Component\Security\Core\Exception\AccessDeniedException',
            'Permission "view" in security context "sulu.media.collection" not granted'
        );

        $this->authorizationChecker->isGranted(
            ['view'],
            Argument::which('getSecurityContext', 'sulu.media.collection')
        )->willReturn(false);

        $this->securityChecker->checkPermission('sulu.media.collection', 'view');
    }

    public function testIsGrantedWithoutToken()
    {
        $this->tokenStorage->getToken()->willReturn(null);
        $this->authorizationChecker->isGranted(Argument::any(), Argument::any())->willReturn(false);

        $this->assertTrue($this->securityChecker->checkPermission('sulu.media.collection', 'view'));
    }
}
