<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\EventListener\AuhenticationFailureListener;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class AuhenticationFailureListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PasswordHasherFactoryInterface>
     */
    private $passwordHasher;

    /**
     * @var ObjectProphecy<UserRepositoryInterface>
     */
    private $userRepository;

    public function setUp(): void
    {
        $this->passwordHasher = $this->prophesize(PasswordHasherFactoryInterface::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
    }

    public function testLoginFailureListener(): void
    {
        $failureEvent = $this->createLoginFailureEvent('admin');

        $authFailureListener = $this->createAuhenticationFailureListener();

        $authFailureListener->onLoginFailure($failureEvent);

        $this->passwordHasher->getPasswordHasher(Argument::cetera())
            ->shouldBeCalled();
    }

    private function createAuhenticationFailureListener(): AuhenticationFailureListener
    {
        $user = $this->prophesize(User::class);
        $this->userRepository->createNew()->willReturn($user->reveal());

        $hasher = $this->prophesize(PasswordHasherInterface::class);
        $hasher->hash(Argument::type('string'));

        $this->passwordHasher->getPasswordHasher($user->reveal())
            ->willReturn($hasher->reveal());

        return new AuhenticationFailureListener($this->passwordHasher->reveal(), $this->userRepository->reveal());
    }

    private function createLoginFailureEvent(string $firewall): LoginFailureEvent
    {
        $request = Request::create('/admin/login', 'POST');
        $request->request->add(['username' => 'tester', 'password' => 'test']);
        $authenticator = $this->prophesize(AuthenticatorInterface::class);

        return new LoginFailureEvent(
            new BadCredentialsException('Bad credentials.', 0, new UserNotFoundException('User "tester" not found')),
            $authenticator->reveal(),
            $request,
            null,
            $firewall
        );
    }
}
