<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Tests\Unit\Authorization;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Security\Authorization\SecurityChecker;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SecurityCheckerTest extends TestCase
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var ObjectProphecy<TokenStorageInterface>
     */
    private $tokenStorage;

    /**
     * @var ObjectProphecy<AuthorizationCheckerInterface>
     */
    private $authorizationChecker;

    public function setUp(): void
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

    public function testIsGrantedContext(): void
    {
        $this->authorizationChecker->isGranted(
            'view',
            Argument::which('getSecurityContext', 'sulu.media.collection')
        )->willReturn(true);

        $granted = $this->securityChecker->checkPermission('sulu.media.collection', 'view');

        $this->assertTrue($granted);
    }

    public function testIsGrantedObject(): void
    {
        $object = new \stdClass();

        $this->authorizationChecker->isGranted(
            'view',
            $object
        )->willReturn(true);

        $granted = $this->securityChecker->checkPermission($object, 'view');

        $this->assertTrue($granted);
    }

    public function testIsGrantedFalsyValue(): void
    {
        $object = null;

        // should always return true for falsy values
        $this->assertTrue($this->securityChecker->checkPermission($object, 'view'));
    }

    public function testIsGrantedFail(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Permission "view" in security context "sulu.media.collection" not granted');

        $this->authorizationChecker->isGranted(
            'view',
            Argument::which('getSecurityContext', 'sulu.media.collection')
        )->willReturn(false);

        $this->securityChecker->checkPermission('sulu.media.collection', 'view');
    }

    public function testIsGrantedWithoutToken(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Permission "view" in security context "sulu.media.collection" not granted');

        $this->tokenStorage->getToken()->willReturn(null);
        $this->authorizationChecker->isGranted(Argument::any(), Argument::any())->willReturn(false);

        $this->assertFalse($this->securityChecker->checkPermission('sulu.media.collection', 'view'));
    }

    public function testIsGrantedWithoutTokenAllowed(): void
    {
        $this->tokenStorage->getToken()->willReturn(null);
        $this->authorizationChecker->isGranted(Argument::any(), Argument::any())->willReturn(true);

        $this->assertTrue($this->securityChecker->checkPermission('sulu.media.collection', 'view'));
    }
}
